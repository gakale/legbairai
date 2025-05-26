<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SpaceApiController;
use App\Http\Controllers\Api\V1\UserSpaceInteractionApiController;

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

// Routes publiques (sans authentification)
Route::group(['prefix' => 'v1', 'middleware' => ['api']], function () {
    Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login'])->name('api.login');
    Route::post('/register', [App\Http\Controllers\Api\V1\AuthController::class, 'register'])->name('api.register');
    
    // Route pour obtenir le cookie CSRF (nécessaire pour les requêtes SPA)
    Route::get('/csrf-cookie', function() {
        return response()->json(['message' => 'CSRF cookie set']);
    });
});

Route::middleware(['api', 'auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user()->loadCount(['hostedSpaces', /* autres relations si besoin */]));
    })->name('api.user.me');
    Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout'])->name('api.logout');

    // --- Routes pour les Spaces ---
    Route::prefix('spaces')->name('api.v1.spaces.')->group(function () {
        Route::get('/', [SpaceApiController::class, 'index'])->name('index'); // Lister les Spaces
        Route::post('/', [SpaceApiController::class, 'store'])->name('store'); // Créer un Space
        Route::get('/{space}', [SpaceApiController::class, 'show'])->name('show'); // Détails d'un Space
        Route::put('/{space}', [SpaceApiController::class, 'update'])->name('update'); // Mettre à jour un Space
        Route::delete('/{space}', [SpaceApiController::class, 'destroy'])->name('destroy'); // Supprimer un Space

        // Actions spécifiques sur un Space (par l'hôte/co-hôte)
        Route::post('/{space}/start', [SpaceApiController::class, 'start'])->name('start');
        Route::post('/{space}/end', [SpaceApiController::class, 'end'])->name('end');

        // Interactions des utilisateurs avec un Space
        Route::post('/{space}/join', [UserSpaceInteractionApiController::class, 'join'])->name('join');
        Route::post('/{space}/leave', [UserSpaceInteractionApiController::class, 'leave'])->name('leave');
        Route::post('/{space}/raise-hand', [UserSpaceInteractionApiController::class, 'raiseHand'])->name('raiseHand');

        // Actions de modération par l'hôte/co-hôte sur les participants
        Route::post('/{space}/participants/{participantUser}/mute', [UserSpaceInteractionApiController::class, 'muteParticipant'])->name('participants.mute');
        Route::post('/{space}/participants/{participantUser}/unmute', [UserSpaceInteractionApiController::class, 'unmuteParticipant'])->name('participants.unmute');

        // Envoi de messages dans un Space
        Route::post('/{space}/message', [UserSpaceInteractionApiController::class, 'sendMessage'])->name('message');
    });

    // --- Routes pour les Utilisateurs (Profils, Suivi) ---
    Route::prefix('v1/users')->name('api.v1.users.')->group(function () {
        Route::post('/{user}/follow', [App\Http\Controllers\Api\V1\UserApiController::class, 'follow'])->name('follow');
        Route::delete('/{user}/unfollow', [App\Http\Controllers\Api\V1\UserApiController::class, 'unfollow'])->name('unfollow');
    });
});
Route::get('/v1/users/{user}', [UserApiController::class, 'show'])->name('api.v1.users.show.public');
