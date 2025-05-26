<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use Gbairai\Core\Enums\DonationStatus;
use Gbairai\Core\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log; // Pour logger les événements et erreurs
use Illuminate\Support\Facades\Http; // Pour appeler l'API de vérification Paystack
use App\Events\DonationSucceededEvent; // Nous allons créer cet événement

class PaystackWebhookController extends Controller
{
    /**
     * Handle incoming Paystack webhooks.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request): Response
    {
        // 1. Vérifier la signature du webhook (IMPORTANT pour la sécurité)
        $paystackSecretKey = config('paystack.secret_key');
        if (!$this->isValidSignature($request, $paystackSecretKey)) {
            Log::warning('Paystack Webhook: Signature invalide.');
            return response('Signature invalide', 400);
        }

        // 2. Récupérer les données de l'événement
        $eventPayload = $request->all();
        $eventType = $eventPayload['event'] ?? null;

        Log::info('Paystack Webhook Reçu:', ['type' => $eventType, 'payload' => $eventPayload]);

        // 3. Traiter les événements pertinents
        // Pour les dons, l'événement le plus important est 'charge.success'
        if ($eventType === 'charge.success') {
            return $this->handleChargeSuccess($eventPayload['data']);
        }

        // Vous pouvez ajouter des gestionnaires pour d'autres événements Paystack ici
        // if ($eventType === 'transfer.success') { ... }
        // if ($eventType === 'subscription.create') { ... }

        // Toujours retourner une réponse 200 OK à Paystack pour accuser réception,
        // même si on ne traite pas cet événement spécifique, pour éviter les renvois.
        return response('Webhook reçu et traité (ou ignoré).', 200);
    }

    /**
     * Vérifie la signature du webhook Paystack.
     *
     * @param Request $request
     * @param string $secretKey
     * @return bool
     */
    protected function isValidSignature(Request $request, string $secretKey): bool
    {
        if (empty($secretKey)) {
            Log::error('Paystack Webhook: Clé secrète non configurée pour la vérification de la signature.');
            return false; // Ne pas traiter si la clé n'est pas là
        }

        $payload = $request->getContent(); // Obtenir le corps brut de la requête
        $paystackSignature = $request->header('x-paystack-signature');

        if (!$paystackSignature) {
            return false; // Pas de signature, ne pas traiter
        }

        $calculatedSignature = hash_hmac('sha512', $payload, $secretKey);

        return hash_equals($calculatedSignature, $paystackSignature);
    }

    /**
     * Gère l'événement 'charge.success'.
     *
     * @param array $chargeData Données de la charge depuis le payload du webhook.
     * @return Response
     */
    protected function handleChargeSuccess(array $chargeData): Response
    {
        $transactionReference = $chargeData['reference'] ?? null;
        $paystackTransactionId = $chargeData['id'] ?? null; // L'ID de la transaction Paystack
        $statusFromWebhook = $chargeData['status'] ?? null; // Devrait être 'success'
        $amountFromWebhook = $chargeData['amount'] ?? null; // Montant en sous-unités
        $currencyFromWebhook = $chargeData['currency'] ?? null;

        if (!$transactionReference || $statusFromWebhook !== 'success') {
            Log::warning('Paystack Webhook (charge.success): Référence ou statut manquant/incorrect.', ['data' => $chargeData]);
            return response('Données de charge invalides.', 400);
        }

        // ÉTAPE CRUCIALE : Toujours vérifier la transaction directement auprès de Paystack
        $verificationResponse = Http::withToken(config('paystack.secret_key'))
            ->acceptJson()
            ->get(rtrim(config('paystack.payment_url'), '/') . "/transaction/verify/{$transactionReference}");

        if (!$verificationResponse->successful() || !isset($verificationResponse->json()['status']) || $verificationResponse->json()['status'] !== true) {
            Log::error('Paystack Webhook (charge.success): Échec de la vérification de la transaction.', [
                'reference' => $transactionReference,
                'verification_response' => $verificationResponse->body()
            ]);
            // Ne pas traiter le don si la vérification échoue
            return response('Échec de la vérification de la transaction.', 400);
        }

        $verifiedData = $verificationResponse->json()['data'];
        $verifiedStatus = $verifiedData['status'] ?? null;
        $verifiedAmount = $verifiedData['amount'] ?? null;
        $verifiedCurrency = $verifiedData['currency'] ?? null;

        // Trouver la transaction de don locale par la référence Paystack
        /** @var Donation|null $donation */
        $donation = Donation::where('paystack_reference', $transactionReference)->first();

        if (!$donation) {
            Log::error('Paystack Webhook (charge.success): Don local non trouvé pour la référence.', ['reference' => $transactionReference]);
            return response('Don local non trouvé.', 404); // Ou 200 pour que Paystack ne réessaie pas si la réf est vraiment inconnue.
        }

        // Vérifier si le don est déjà marqué comme réussi pour éviter les traitements multiples
        if ($donation->status === DonationStatus::SUCCESSFUL) {
            Log::info('Paystack Webhook (charge.success): Don déjà marqué comme réussi.', ['donation_id' => $donation->id]);
            return response('Don déjà traité.', 200);
        }

        // Double vérification du statut, du montant et de la devise
        if (
            $verifiedStatus === 'success' &&
            (int) $verifiedAmount === $donation->amount_subunit &&
            strtoupper($verifiedCurrency) === strtoupper($donation->currency)
        ) {
            // Mettre à jour le don local
            $donation->status = DonationStatus::SUCCESSFUL;
            $donation->paystack_transaction_id = (string) ($verifiedData['id'] ?? $paystackTransactionId); // Utiliser l'ID de transaction de la vérification
            $donation->metadata = array_merge($donation->metadata ?? [], ['webhook_charge_data' => $chargeData, 'verification_data' => $verifiedData]);
            $donation->save();

            Log::info('Paystack Webhook (charge.success): Don mis à jour avec succès.', ['donation_id' => $donation->id]);

            // Déclencher un événement pour notifier l'application (ex: pour le temps réel)
            DonationSucceededEvent::dispatch($donation->load(['donor', 'recipient', 'space']));

            return response('Don traité avec succès.', 200);
        } else {
            $donation->status = DonationStatus::FAILED;
            $donation->metadata = array_merge($donation->metadata ?? [], ['webhook_charge_data' => $chargeData, 'verification_data' => $verifiedData, 'failure_reason' => 'Mismatch or failed verification']);
            $donation->save();
            Log::error('Paystack Webhook (charge.success): Non-concordance du montant/devise ou statut de vérification incorrect.', [
                'donation_id' => $donation->id,
                'expected_amount' => $donation->amount_subunit, 'verified_amount' => $verifiedAmount,
                'expected_currency' => $donation->currency, 'verified_currency' => $verifiedCurrency,
                'verified_status' => $verifiedStatus,
            ]);
            return response('Non-concordance des données de transaction.', 400);
        }
    }
}