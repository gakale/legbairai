<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User; // Le modèle User de l'application
use Gbairai\Core\Models\Space;
use Illuminate\Auth\Access\HandlesAuthorization; // Laravel 10 et versions antérieures
// use Illuminate\Auth\Access\Response; // Pour Laravel 11+ si vous voulez des réponses plus granulaires

class SpacePolicy
{
    // Pour Laravel 10 et versions antérieures, décommentez la ligne suivante :
    // use HandlesAuthorization;

    /**
     * Perform pre-authorization checks.
     *
     * Si un administrateur doit pouvoir tout faire, vous pouvez le gérer ici.
     *
     * @param  \App\Models\User  $user
     * @param  string  $ability
     * @return bool|void
     */
    public function before(User $user, string $ability)
    {
        // Example: if ($user->isAdmin()) {
        // return true;
        // }
        // Laissez vide pour l'instant si pas de rôle admin global.
    }

    /**
     * Determine whether the user can view any models.
     *
     * Qui peut voir la liste des Spaces (par exemple, dans un index) ?
     * Généralement tout le monde, mais pourrait être restreint.
     */
    public function viewAny(?User $user): bool // User peut être null pour les invités
    {
        return true; // Pour l'instant, tout le monde peut lister les spaces
    }

    /**
     * Determine whether the user can view the model.
     *
     * Qui peut voir les détails d'un Space spécifique ?
     */
    public function view(?User $user, Space $space): bool
    {
        // En mode développement, permettre l'accès à tous les espaces pour faciliter les tests
        if (app()->environment('local')) {
            return true;
        }
        
        // Logique pour les Spaces publics - toujours accessible
        if ($space->type->isPublic()) { // Méthode isPublic() sur l'enum SpaceType
            return true;
        }

        // Si l'utilisateur n'est pas connecté, il ne peut pas voir les espaces privés
        if (!$user) {
            return false;
        }
        
        // Si l'utilisateur est l'hôte
        if ($user->getId() === $space->host_user_id) {
            return true;
        }

        // Si le Space est privé sur invitation et que l'utilisateur est invité
        if ($space->hasParticipant($user)) {
            return true;
        }

        // Si le Space est privé pour abonnés et que l'utilisateur est abonné au créateur
        // TODO: Implémenter la logique d'abonnement quand cette fonctionnalité sera disponible
        // if ($user->isSubscribedTo($space->host)) {
        //     return true;
        // }

        return false; // Par défaut, refuser si aucune condition n'est remplie pour les spaces non publics
    }

    /**
     * Determine whether the user can create models.
     *
     * Qui peut créer un Space ?
     */
    public function create(User $user): bool
    {
        // Pour l'instant, tout utilisateur authentifié peut créer un Space.
        // Vous pourriez ajouter des conditions (ex: utilisateur vérifié, utilisateur premium).
        // return $user->is_verified;
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * Qui peut modifier un Space ? Généralement l'hôte ou les co-hôtes.
     */
    public function update(User $user, Space $space): bool
    {
        if ($user->getId() === $space->host_user_id) {
            return true;
        }

        // TODO: Ajouter la logique pour les co-hôtes
        // if ($space->isCoHost($user)) {
        // return true;
        // }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * Qui peut supprimer un Space ? Généralement l'hôte.
     */
    public function delete(User $user, Space $space): bool
    {
        return $user->getId() === $space->host_user_id;
    }

    /**
     * Determine whether the user can restore the model. (Si SoftDeletes est utilisé)
     */
    // public function restore(User $user, Space $space): bool
    // {
    // return $user->getId() === $space->host_user_id;
    // }

    /**
     * Determine whether the user can permanently delete the model. (Si SoftDeletes est utilisé)
     */
    // public function forceDelete(User $user, Space $space): bool
    // {
    // return $user->getId() === $space->host_user_id;
    // }


    // --- Méthodes d'autorisation personnalisées pour les actions spécifiques aux Spaces ---

    /**
     * Determine if the user can start a scheduled space.
     */
    public function start(User $user, Space $space): bool
    {
        return $this->update($user, $space) && $space->status === \Gbairai\Core\Enums\SpaceStatus::SCHEDULED;
    }

    /**
     * Determine if the user can end a live space.
     */
    public function end(User $user, Space $space): bool
    {
        return $this->update($user, $space) && $space->status === \Gbairai\Core\Enums\SpaceStatus::LIVE;
    }

    /**
     * Determine if the user can join a space.
     * (Doit être affinée pour les spaces payants, sur abo, pleins, bannis etc.)
     */
    public function join(?User $user, Space $space): bool // User peut être null si on veut vérifier pour un invité
    {
        if (!$user) return false; // Doit être authentifié pour rejoindre

        if ($space->status !== \Gbairai\Core\Enums\SpaceStatus::LIVE) {
            return false; // Ne peut pas rejoindre un space non live
        }

        // Vérifier si l'utilisateur est l'hôte
        if ($space->host_user_id === $user->getId()) {
            return true; // L'hôte peut toujours (re)joindre son propre space (sa participation est spéciale)
        }


        // TODO: Vérifier si le space est plein (max_participants)
        // $currentParticipantsCount = $space->participants()->whereNull('left_at')->count();
        // if ($space->max_participants !== null && $currentParticipantsCount >= $space->max_participants) {
        //     // Vérifier si l'utilisateur est déjà un participant qui revient
        //     $isReturningParticipant = $space->participants()->where('user_id', $user->getId())->exists();
        //     if (!$isReturningParticipant) {
        //          return false; // Plein et nouvel utilisateur
        //     }
        // }


        // TODO: Gérer les spaces payants (billets) ou sur abonnement
        // if ($space->type === \Gbairai\Core\Enums\SpaceType::PUBLIC_PAID && !$user->hasTicketFor($space)) {
        //     return false;
        // }
        // if ($space->type === \Gbairai\Core\Enums\SpaceType::PRIVATE_SUBSCRIBER && !$user->isSubscribedTo($space->host)) {
        //     return false;
        // }

        // Pour l'instant, si le space est live et que l'utilisateur n'est pas l'hôte, il peut tenter de rejoindre
        // (d'autres règles métier dans l'action peuvent l'empêcher).
        return true;
    }

    /**
     * Determine if the user can speak in a space.
     * (Cette logique sera plus complexe et dépendra du rôle du participant, s'il a levé la main, etc.)
     */
    public function speak(User $user, Space $space): bool
    {
        if ($user->getId() === $space->host_user_id) {
            return true;
        }
        // TODO: Vérifier si l'utilisateur est co-hôte
        // TODO: Vérifier si l'utilisateur est un participant avec droit de parole activé
        return false;
    }

    /**
     * Determine if the user (host/co-host) can manage participants (mute, grant speak).
     */
    public function manageParticipants(User $user, Space $space): bool
    {
        if ($user->getId() === $space->host_user_id) {
            return true; // L'hôte peut toujours gérer
        }
    
        // Vérifier si l'utilisateur est un co-hôte actif dans ce space
        /** @var \Gbairai\Core\Models\SpaceParticipant|null $participantRecord */
        $participantRecord = $space->participants()
            ->where('user_id', $user->getId())
            ->where('role', \Gbairai\Core\Enums\SpaceParticipantRole::CO_HOST)
            ->whereNull('left_at')
            ->first();
    
        return $participantRecord !== null;
    }

    /**
     * Determine if the user (host/co-host) can pin a message in the space.
     */
    public function pinMessage(User $user, Space $space): bool
    {
        return $this->manageParticipants($user, $space);
    }

     /**
     * Determine if the user can enable/disable recording for a space they host.
     */
    public function manageRecording(User $user, Space $space): bool
    {
        // Seul l'hôte peut gérer l'enregistrement, et potentiellement seulement s'il est premium.
        return $user->getId() === $space->host_user_id && $user->is_premium;
    }
}