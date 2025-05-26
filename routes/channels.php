<?php

use Illuminate\Support\Facades\Broadcast;
use Gbairai\Core\Models\Space; // Importer le modèle Space

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Canal privé pour les événements d'un Space spécifique
Broadcast::channel('space.{spaceId}', function ($user, $spaceId) {
    /** @var \App\Models\User $user L'utilisateur authentifié qui tente de s'abonner */
    /** @var string $spaceId L'ID du Space (UUID) depuis le nom du canal */

    $space = Space::find($spaceId);

    if (!$space) {
        return false;
    }

    // L'utilisateur doit être l'hôte ou un participant actif pour rejoindre le canal de présence
    $isHost = $user->getId() === $space->host_user_id;

    $participantRecord = $space->participants()
        ->where('user_id', $user->getId())
        ->whereNull('left_at')
        ->first();

    $isParticipant = $participantRecord !== null;

    if ($isHost || $isParticipant) {
        // Pour un PresenceChannel, retourner un tableau avec les infos de l'utilisateur
        // Ces informations seront disponibles pour les autres membres du canal via .here(), .joining(), .leaving()
        return [
            'id' => $user->getId(), // Utiliser getId() pour la cohérence du contrat
            'username' => $user->getUsername(), // Utiliser getUsername()
            'avatar_url' => $user->avatar_url, // Accès direct si c'est un attribut public
            'role' => $isHost ? 'host' : ($participantRecord ? $participantRecord->role->value : 'listener'), // Rôle dans ce space
            // Vous pouvez ajouter d'autres infos pertinentes ici (ex: is_muted_by_host, has_raised_hand initial)
            // 'is_muted_by_host' => $participantRecord ? $participantRecord->is_muted_by_host : true,
            // 'has_raised_hand' => $participantRecord ? $participantRecord->has_raised_hand : false,
        ];
    }

    return false; // Non autorisé
}, ['guards' => ['web', 'sanctum']]); // Spécifier les guards est une bonne pratique



Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    // Pour les tests, autoriser l'accès même si l'utilisateur n'est pas authentifié
    if (request()->hasHeader('X-Test-Auth') && request()->header('X-Test-Auth') === 'true') {
        return true;
    }
    
    // Pour les tests via notre page de test
    if (request()->is('realtime-test/*')) {
        return true;
    }
    
    // Vérification normale pour les utilisateurs authentifiés
    return (string) $user->id === (string) $id; // Comparaison de chaînes pour les UUIDs
});