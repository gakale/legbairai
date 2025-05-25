<?php

use Illuminate\Support\Facades\Route;
use Gbairai\Core\Http\Requests\StoreSpaceRequest;
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use App\Events\SpaceStartedEvent;
use Gbairai\Core\Models\Space;

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

// Route pour la page de test en temps réel
Route::get('/realtime-test', function () {
    return view('realtime-test');
});

// Route pour déclencher manuellement l'événement SpaceStartedEvent (pour les tests)
Route::get('/trigger-space-event/{spaceId}', function ($spaceId) {
    $space = Space::find($spaceId);
    
    if (!$space) {
        return response()->json(['error' => 'Space non trouvé'], 404);
    }
    
    SpaceStartedEvent::dispatch($space);
    
    return response()->json([
        'success' => true,
        'message' => "Événement SpaceStartedEvent déclenché pour le space {$space->title}"
    ]);
});
