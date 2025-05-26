<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\Donations\InitiateDonationAction;
use App\Models\User; // Modèle User de l'application
use Gbairai\Core\Models\Space; // Modèle Space du package
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class DonationApiController extends Controller
{
    public function initialize(Request $request, InitiateDonationAction $initiateDonationAction): JsonResponse
    {
        $validatedData = $request->validate([
            'recipient_user_id' => ['required', 'uuid', Rule::exists('users', 'id')],
            'space_id' => ['nullable', 'uuid', Rule::exists('spaces', 'id')],
            'amount' => ['required', 'integer', 'min:100'], // Montant en sous-unité (ex: kobo). Paystack a un minimum.
            'currency' => ['sometimes', 'string', 'size:3'], // Ex: NGN, USD. Si non fourni, l'action utilisera le défaut.
            'callback_url' => ['nullable', 'url'], // Optionnel, pour la redirection après paiement
        ]);

        /** @var \App\Models\User $donor */
        $donor = Auth::user();
        /** @var \App\Models\User $recipient */
        $recipient = User::findOrFail($validatedData['recipient_user_id']);
        $space = isset($validatedData['space_id']) ? Space::find($validatedData['space_id']) : null;

        try {
            $paystackResponse = $initiateDonationAction->execute(
                $donor,
                $recipient,
                (int) $validatedData['amount'],
                $validatedData['currency'] ?? null,
                $space,
                [ 'source' => 'gbairai_web_donation' ], // Exemple de metadata
                $validatedData['callback_url'] ?? null
            );

            return response()->json([
                'message' => 'Transaction de don initialisée.',
                'data' => $paystackResponse,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Erreur de validation.', 'errors' => $e->errors()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Donation Initialization Error: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Une erreur interne est survenue lors de l\'initialisation du don.'], 500);
        }
    }
}