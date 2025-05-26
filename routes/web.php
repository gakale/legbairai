<?php

use Illuminate\Support\Facades\Route;
use Gbairai\Core\Http\Requests\StoreSpaceRequest;
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Events\SpaceStartedEvent;
use Gbairai\Core\Models\Space;
use App\Http\Controllers\RealtimeTestController;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Route de test pour la création d'un Space
 * 
 * Pour tester avec Postman ou un client HTTP similaire :
 * POST /test-create-space
 * Content-Type: application/json
 * Body: {
 *   "title": "Mon premier Space",
 *   "description": "Description du Space",
 *   "type": "public_free",
 *   "is_recording_enabled_by_host": true,
 *   "scheduled_at": "2025-06-01 15:00:00"
 * }
 */
// Désactivons la vérification CSRF pour cette route de test
Route::middleware('web')->withoutMiddleware(['csrf'])->post('/test-create-space', function (StoreSpaceRequest $request, CreateSpaceAction $createSpaceAction) {
    if (!auth()->user()) { // Pour ce test, connectons un utilisateur factice
        $user = User::first(); // Prenez un utilisateur existant
        if (!$user) {
            return response()->json(['error' => 'Créez un utilisateur pour tester'], 403);
        }
        auth()->login($user);
    }

    try {
        $space = $createSpaceAction->execute(auth()->user(), $request->validated());
        return response()->json([
            'message' => 'Space créé avec succès',
            'space' => $space->load('host')
        ]); // Charger la relation pour voir
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Throwable $e) {
        Log::error('Error creating space: ' . $e->getMessage(), ['exception' => $e]);
        return response()->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
    }
});

Route::middleware(['web'])->group(function () {
    // Routes pour les tests en temps réel (déclenchement d'événements)
    Route::prefix('realtime-test')->name('realtime.test.')->group(function () {
        Route::get('/', [RealtimeTestController::class, 'showRealtimeTest'])->name('show');
        Route::get('/participants', [RealtimeTestController::class, 'showParticipantsTest'])->name('participants.show');
        Route::post('/space/{spaceId}/start', [RealtimeTestController::class, 'triggerSpaceStarted'])->name('trigger.space.started');
        Route::post('/space/{spaceId}/join', [RealtimeTestController::class, 'triggerUserJoined'])->name('trigger.user.joined');
        Route::post('/space/{spaceId}/leave', [RealtimeTestController::class, 'triggerUserLeft'])->name('trigger.user.left');
        Route::post('/space/{spaceId}/participant/{participantId}/raise-hand', [RealtimeTestController::class, 'triggerRaiseHand'])->name('trigger.raise.hand');
        Route::post('/space/{spaceId}/participant/{participantId}/change-role', [RealtimeTestController::class, 'triggerChangeRole'])->name('trigger.change.role');
    });
});
