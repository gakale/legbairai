<?php

use Illuminate\Support\Facades\Route;
use Gbairai\Core\Http\Requests\StoreSpaceRequest;
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/**
 * Route de test pour la création d'un Space
 * 
 * Pour tester avec Postman ou un client HTTP similaire :
 * POST /api/test-create-space
 * Content-Type: application/json
 * Body: {
 *   "title": "Mon premier Space",
 *   "description": "Description du Space",
 *   "type": "public_free",
 *   "is_recording_enabled_by_host": true,
 *   "scheduled_at": "2025-06-01 15:00:00"
 * }
 */
Route::post('/test-create-space', function (StoreSpaceRequest $request, CreateSpaceAction $createSpaceAction) {
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

// Route RESTful pour la création d'un Space
Route::post('/spaces', function (StoreSpaceRequest $request, CreateSpaceAction $createSpaceAction) {
    $user = auth()->user();
    if (!$user) {
        return response()->json(['error' => 'Non authentifié'], 401);
    }
    // La Policy est automatiquement vérifiée via StoreSpaceRequest::authorize()
    $space = $createSpaceAction->execute($user, $request->validated());
    return response()->json([
        'message' => 'Space créé avec succès',
        'space' => $space->load('host')
    ], 201);
});
