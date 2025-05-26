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

    // Tenter de récupérer le Space
    $space = Space::find($spaceId);

    if (!$space) {
        return false; // Le Space n'existe pas, l'abonnement échoue
    }

    // Vérifier si l'utilisateur est l'hôte du Space
    if ($user->getId() === $space->host_user_id) {
        // Retourner des données sur l'utilisateur si c'est un canal de présence (pas le cas ici)
        // return ['id' => $user->id, 'name' => $user->username];
        return true; // L'hôte est toujours autorisé
    }

    // Vérifier si l'utilisateur est un participant actif du Space
    $isParticipant = $space->participants()
        ->where('user_id', $user->getId())
        ->whereNull('left_at') // Doit être actif (pas quitté)
        ->exists();

    if ($isParticipant) {
        // return ['id' => $user->id, 'name' => $user->username]; // Pour canal de présence
        return true; // Le participant actif est autorisé
    }

    // Optionnellement, vérifier si l'utilisateur a une permission générale de "voir" ce space,
    // même s'il n'est pas encore participant (ex: pour recevoir une notif que le space a démarré avant de rejoindre).
    // Cela dépend de votre logique. Pour des événements comme "user.joined", il est plus logique
    // que seuls ceux qui sont déjà "concernés" (participants) reçoivent.
    // if ($user->can('view', $space)) {
    //     return true;
    // }

    return false; // Par défaut, non autorisé
});


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id; // Comparaison de chaînes pour les UUIDs
});