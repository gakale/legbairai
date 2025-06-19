<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\SpaceApiController;
use App\Http\Controllers\Api\V1\UserSpaceInteractionApiController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AudioClipApiController;
use App\Http\Controllers\Api\V1\DonationApiController;
use App\Http\Controllers\Api\V1\UserApiController; // Assurez-vous que le namespace complet est utilisé si AuthController est dans le même dossier
use App\Http\Controllers\Api\V1\AuthController; // Explicitement pour login/register/logout
use App\Http\Controllers\Webhook\PaystackWebhookController;


// Routes publiques (sans authentification)
// Le groupe 'api' est appliqué automatiquement aux fichiers dans routes/api.php par RouteServiceProvider
Route::prefix('v1')->group(function () { // Le middleware 'api' est déjà appliqué
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');
    Route::post('/register', [AuthController::class, 'register'])->name('api.v1.register');
    
    // Routes pour les Spaces accessibles sans authentification
    Route::prefix('spaces')->name('api.v1.spaces.')->group(function () {
        Route::get('/', [SpaceApiController::class, 'index'])->name('index'); // Liste des espaces
        Route::get('/{space}', [SpaceApiController::class, 'show'])->name('show'); // Détails d'un espace
    });
    
    // Cette route n'est plus nécessaire avec Sanctum si EnsureFrontendRequestsAreStateful est utilisé
    // Route::get('/csrf-cookie', function() {
    //     return response()->json(['message' => 'CSRF cookie set']);
    // });
});
Route::post('/v1/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () { // Ajout du name() ici pour préfixer tous les noms de route enfants
    Route::get('/user', function (Request $request) {
        return new \App\Http\Resources\UserResource($request->user()->loadCount(['hostedSpaces']));
    })->name('user.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // --- Routes pour les Spaces ---
    Route::prefix('spaces')->name('spaces.')->group(function () { // Les noms seront api.v1.spaces.index etc.
        // Ces routes sont maintenant dans le groupe public
        // Route::get('/', [SpaceApiController::class, 'index'])->name('index');
        Route::post('/', [SpaceApiController::class, 'store'])->name('store');
        // Route::get('/{space}', [SpaceApiController::class, 'show'])->name('show');
        Route::put('/{space}', [SpaceApiController::class, 'update'])->name('update');
        Route::delete('/{space}', [SpaceApiController::class, 'destroy'])->name('destroy');
        Route::post('/{space}/start', [SpaceApiController::class, 'start'])->name('start');
        Route::post('/{space}/end', [SpaceApiController::class, 'end'])->name('end');
        Route::post('/{space}/join', [UserSpaceInteractionApiController::class, 'join'])->name('join');
        Route::post('/{space}/leave', [UserSpaceInteractionApiController::class, 'leave'])->name('leave');
        Route::post('/{space}/raise-hand', [UserSpaceInteractionApiController::class, 'raiseHand'])->name('raiseHand');
        Route::post('/{space}/participants/{participantUser}/mute', [UserSpaceInteractionApiController::class, 'muteParticipant'])->name('participants.mute');
        Route::post('/{space}/participants/{participantUser}/unmute', [UserSpaceInteractionApiController::class, 'unmuteParticipant'])->name('participants.unmute');
        Route::post('/{space}/participants/{participantUser}/role', [UserSpaceInteractionApiController::class, 'changeRole'])->name('participants.role'); // Ajout de cette route
        Route::post('/{space}/messages', [UserSpaceInteractionApiController::class, 'sendMessage'])->name('messages.store'); // Corrigé le nom de la méthode et de la route
        Route::get('/{space}/messages', [UserSpaceInteractionApiController::class, 'getMessages'])->name('messages.index'); // Nouvelle route pour récupérer les messages
        Route::post('/messages/{spaceMessage}/toggle-pin', [UserSpaceInteractionApiController::class, 'togglePinMessage'])->name('messages.togglePin');
        Route::post('/{space}/clips', [AudioClipApiController::class, 'store'])->name('clips.store');
        Route::post('/{space}/audio-signal', [SpaceApiController::class, 'handleAudioSignal'])->name('audio.signal'); // Route for WebRTC signaling
    });

    // --- Routes pour les Utilisateurs (Profils, Suivi) ---
    Route::prefix('users')->name('users.')->group(function () {
        // Récupérer les spaces créés par un utilisateur
        Route::get('/{user}/spaces', [UserApiController::class, 'getUserSpaces'])->name('spaces');
        
        // Récupérer les spaces où un utilisateur a participé
        Route::get('/{user}/participated-spaces', [UserApiController::class, 'getParticipatedSpaces'])->name('participated-spaces');
        
        // Routes de suivi existantes
        Route::post('/{user}/follow', [UserApiController::class, 'follow'])->name('follow');
        Route::delete('/{user}/unfollow', [UserApiController::class, 'unfollow'])->name('unfollow');
    });

    // --- Routes pour les Notifications ---
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
    });

    // --- Routes pour les Dons ---
    Route::prefix('donations')->name('donations.')->group(function () {
        Route::post('/initialize', [DonationApiController::class, 'initialize'])->name('initialize');
    });
});

// Route publique pour voir les profils (en dehors du middleware auth:sanctum)
// Assurez-vous que le préfixe est cohérent.
Route::get('/v1/users/{user}', [UserApiController::class, 'show'])->name('api.v1.users.show.public');

// Route publique pour accéder à la liste des espaces
Route::get('/v1/spaces', [SpaceApiController::class, 'index'])->withoutMiddleware(['auth:sanctum'])->name('api.v1.spaces.index.public');