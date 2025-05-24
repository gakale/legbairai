<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Class EndSpaceAction
 *
 * Ends a LIVE Space, calculates its duration, and sets its status to ENDED.
 */
class EndSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user attempting to end the space (host or co-host).
     * @param Space $space The space to end.
     * @return Space The ended Space.
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user is not authorized.
     * @throws RuntimeException If the space cannot be ended (e.g., not live).
     */
    public function execute(UserContract $user, Space $space): Space
    {
        // 1. Autorisation via Policy
        // La policy 'end' devrait vérifier si l'utilisateur est l'hôte (ou co-hôte)
        // et si le space est actuellement LIVE.
        Gate::forUser($user)->authorize('end', $space);

        // 2. Vérification de l'état actuel du Space
        if ($space->status !== SpaceStatus::LIVE) {
            // Peut-être que le space a déjà été terminé par un autre processus/co-hôte
            // ou n'a jamais démarré.
            // Si le statut est déjà ENDED, on pourrait simplement retourner le space.
            if ($space->status === SpaceStatus::ENDED) {
                return $space; // Déjà terminé, aucune action nécessaire.
            }
            throw new RuntimeException("Le Space '{$space->title}' n'est pas en direct et ne peut pas être terminé.");
        }

        if ($space->started_at === null) {
            // Cela ne devrait pas arriver si le space est LIVE, mais c'est une protection.
            throw new RuntimeException("L'heure de début du Space '{$space->title}' est manquante. Impossible de calculer la durée.");
        }

        // 3. Mise à jour du Space
        $endedAt = Carbon::now();
        $durationSeconds = $endedAt->diffInSeconds($space->started_at);

        $space->status = SpaceStatus::ENDED;
        $space->ended_at = $endedAt;
        $space->duration_seconds = $durationSeconds;

        $space->save();

        // 4. Logique post-fin
        // - Supprimer tous les participants actifs du Space (ou marquer leur 'left_at').
        //   (Cela pourrait être une autre action: RemoveAllParticipantsAction ou un listener d'événement)
        //
        // - Si le Space était enregistré, finaliser l'enregistrement et le rendre disponible.
        //   (Tâche asynchrone via Queues)
        //
        // - Envoyer des notifications (ex: aux participants pour dire que le Space est terminé,
        //   ou au créateur avec un résumé).
        //
        // - Déclencher un événement SpaceEnded
        //   event(new SpaceEnded($space, $user));

        return $space;
    }
}
