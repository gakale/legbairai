<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Donations;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\DonationStatus;
use Gbairai\Core\Models\Donation;
use Gbairai\Core\Models\Space; // Optionnel, si le don est lié à un Space
use Illuminate\Support\Facades\Http; // Client HTTP de Laravel
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Class InitiateDonationAction
 *
 * Initiates a donation transaction with Paystack and records it locally.
 */
class InitiateDonationAction
{
    protected string $paystackSecretKey;
    protected string $paystackPaymentUrl;
    protected string $defaultCurrency;

    public function __construct()
    {
        $this->paystackSecretKey = config('paystack.secret_key');
        $this->paystackPaymentUrl = rtrim(config('paystack.payment_url', 'https://api.paystack.co'), '/');
        $this->defaultCurrency = config('paystack.currency', 'XOF'); // Devise par défaut

        if (empty($this->paystackSecretKey)) {
            throw new RuntimeException('La clé secrète Paystack n\'est pas configurée.');
        }
    }

    /**
     * Execute the action.
     *
     * @param UserContract $donor The user making the donation.
     * @param UserContract $recipient The user receiving the donation.
     * @param int $amountInSubunits The amount in the smallest currency unit (e.g., kobo, cents).
     * @param string|null $currency The currency code (e.g., NGN, USD). Si null, utilise la devise par défaut.
     * @param Space|null $space The space where the donation is made (optional).
     * @param array<string, mixed> $metadata Additional metadata for Paystack and local storage.
     * @param string|null $callbackUrl URL de rappel après le paiement.
     * @return array{access_code: string, reference: string, authorization_url: string, donation_id: string}
     * @throws ValidationException
     * @throws RuntimeException
     */
    public function execute(
        UserContract $donor,
        UserContract $recipient,
        int $amountInSubunits,
        ?string $currency = null,
        ?Space $space = null,
        array $metadata = [],
        ?string $callbackUrl = null
    ): array {
        $currency = $currency ?: $this->defaultCurrency;

        // 1. Validation simple des entrées (plus de validation pourrait être dans un FormRequest en amont)
        if ($amountInSubunits <= 0) { // Paystack requiert un montant minimum (ex: 100 kobo)
            throw ValidationException::withMessages(['amount' => 'Le montant du don doit être positif.']);
        }
        if ($donor->getId() === $recipient->getId()) {
            throw new RuntimeException("Un utilisateur ne peut pas se faire un don à lui-même.");
        }

        // 2. Générer une référence unique pour notre système et pour Paystack
        //    Cette référence sera utilisée pour vérifier la transaction plus tard.
        $localReference = 'gbairai_don_' . Str::uuid()->toString();

        // 3. Préparer les données pour l'API Paystack
        $payload = [
            'email' => $donor->getEmail(), // L'email du donneur est requis par Paystack
            'amount' => $amountInSubunits,
            'currency' => $currency,
            'reference' => $localReference,
            'metadata' => array_merge($metadata, [
                'donor_user_id' => $donor->getId(),
                'recipient_user_id' => $recipient->getId(),
                'space_id' => $space?->id,
                'custom_fields' => [ // Exemple de champs personnalisés
                    ['display_name' => "Donateur", "variable_name" => "donor_name", "value" => $donor->getUsername()],
                    ['display_name' => "Bénéficiaire", "variable_name" => "recipient_name", "value" => $recipient->getUsername()],
                ]
            ]),
        ];

        if ($callbackUrl) {
            $payload['callback_url'] = $callbackUrl;
        }
        // Vous pouvez ajouter d'autres champs comme 'channels' pour limiter les moyens de paiement

        // 4. Appeler l'API d'initialisation de Paystack
        $response = Http::withToken($this->paystackSecretKey)
            ->acceptJson()
            ->post("{$this->paystackPaymentUrl}/transaction/initialize", $payload);

        if (!$response->successful() || !isset($response->json()['status']) || $response->json()['status'] !== true) {
            $errorMessage = $response->json()['message'] ?? 'Erreur lors de l\'initialisation de la transaction avec Paystack.';
            // Logguer la réponse complète de Paystack pour le débogage
            \Illuminate\Support\Facades\Log::error('Paystack Initialization Failed:', [
                'payload' => $payload,
                'response_status' => $response->status(),
                'response_body' => $response->body(),
            ]);
            throw new RuntimeException($errorMessage);
        }

        $paystackData = $response->json()['data']; // Contient access_code, authorization_url, reference

        // 5. Créer un enregistrement de don localement avec le statut "pending"
        /** @var Donation $donation */
        $donation = \Gbairai\Core\Models\Donation::create([
            'donor_user_id' => $donor->getId(),
            'recipient_user_id' => $recipient->getId(),
            'space_id' => $space?->id,
            'amount_subunit' => $amountInSubunits,
            'currency' => $currency,
            'paystack_reference' => $paystackData['reference'], // Utiliser la référence retournée par Paystack (devrait être la même que $localReference)
            'status' => DonationStatus::PENDING,
            'metadata' => $payload['metadata'], // Stocker les métadonnées que nous avons envoyées
        ]);

        return [
            'access_code' => $paystackData['access_code'],
            'reference' => $paystackData['reference'],
            'authorization_url' => $paystackData['authorization_url'], // Utile si on utilise la méthode Redirect
            'donation_id' => $donation->id, // ID de notre enregistrement de don local
        ];
    }
}