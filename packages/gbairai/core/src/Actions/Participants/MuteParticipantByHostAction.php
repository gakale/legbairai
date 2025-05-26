<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Participants;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use App\Events\ParticipantMutedStatusChangedEvent; // Importer


class MuteParticipantByHostAction
{
    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RuntimeException
     */
    public function execute(UserContract $actor, Space $space, UserContract $targetUser): SpaceParticipant
    {
        Gate::forUser($actor)->authorize('manageParticipants', $space); // Hôte ou co-hôte

        /** @var SpaceParticipant|null $targetParticipant */
        $targetParticipant = $space->participants()
            ->where('user_id', $targetUser->getId())
            ->whereNull('left_at')
            ->first();

        if (!$targetParticipant) {
            throw new RuntimeException("L'utilisateur cible '{$targetUser->getUsername()}' n'est pas un participant actif de ce Space.");
        }

        // On ne peut pas se muter soi-même via cette action (l'hôte/co-hôte a ses propres contrôles)
        // Ni muter l'hôte principal si l'acteur est un co-hôte
        if ($targetUser->getId() === $actor->getId()) {
            throw new RuntimeException("Vous ne pouvez pas vous muter via cette action de modération.");
        }
        if ($targetUser->getId() === $space->host_user_id && $actor->getId() !== $space->host_user_id) {
            throw new RuntimeException("Un co-hôte ne peut pas muter l'hôte principal.");
        }


        if ($targetParticipant->is_muted_by_host) {
            // Déjà muté par l'hôte, aucune action.
            return $targetParticipant;
        }

        $targetParticipant->is_muted_by_host = true;
        $targetParticipant->save();

        ParticipantMutedStatusChangedEvent::dispatch($targetParticipant); // Déclencher l'événement
        return $targetParticipant;
    }
}