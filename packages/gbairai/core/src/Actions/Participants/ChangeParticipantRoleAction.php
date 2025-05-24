<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Participants;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceParticipantRole;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Class ChangeParticipantRoleAction
 *
 * Allows an authorized user (host/co-host) to change the role of another participant.
 */
class ChangeParticipantRoleAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $actor The user performing the role change (host/co-host).
     * @param Space $space The space where the change occurs.
     * @param UserContract $targetUser The user whose role is being changed.
     * @param SpaceParticipantRole $newRole The new role to assign.
     * @return SpaceParticipant The updated participant record.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RuntimeException
     */
    public function execute(
        UserContract $actor,
        Space $space,
        UserContract $targetUser,
        SpaceParticipantRole $newRole
    ): SpaceParticipant {
        // 1. Autorisation (ex: SpacePolicy@manageParticipants ou une méthode plus spécifique)
        // L'acteur (host/co-host) doit avoir le droit de gérer les participants.
        Gate::forUser($actor)->authorize('manageParticipants', $space);

        /** @var SpaceParticipant|null $targetParticipant */
        $targetParticipant = $space->participants()
            ->where('user_id', $targetUser->getId())
            ->whereNull('left_at') // Le participant doit être actif
            ->first();

        if (!$targetParticipant) {
            throw new RuntimeException("L'utilisateur cible '{$targetUser->getUsername()}' n'est pas un participant actif de ce Space.");
        }

        // 2. Logique métier et restrictions
        // L'hôte principal ne peut pas voir son rôle changé par cette action.
        if ($space->host_user_id === $targetUser->getId()) {
            throw new RuntimeException("Le rôle de l'hôte principal ne peut pas être modifié via cette action.");
        }

        // Un co-hôte ne peut pas changer le rôle d'un autre co-hôte ou de l'hôte.
        // (Cette logique pourrait être dans la Policy ou ici)
        if ($actor->getId() !== $space->host_user_id && $targetParticipant->role === SpaceParticipantRole::CO_HOST) {
             throw new RuntimeException("Un co-hôte ne peut pas modifier le rôle d'un autre co-hôte.");
        }
        // De même, un co-hôte ne peut pas se promouvoir/dégrader lui-même via cette action
        if ($actor->getId() === $targetUser->getId() && $targetParticipant->role === SpaceParticipantRole::CO_HOST) {
            // throw new RuntimeException("Vous ne pouvez pas modifier votre propre rôle de co-hôte de cette manière.");
        }


        // Si on passe à Intervenant, on pourrait vouloir désactiver "main levée"
        if ($newRole === SpaceParticipantRole::SPEAKER) {
            $targetParticipant->has_raised_hand = false;
            // $targetParticipant->is_muted_by_host = false; // Potentiellement démuter par l'hôte
        }

        // Si on repasse à Auditeur, on pourrait vouloir le muter
        if ($newRole === SpaceParticipantRole::LISTENER) {
            $targetParticipant->is_muted_by_host = true;
            $targetParticipant->is_self_muted = true; // Peut-être
        }

        // La promotion en CO_HOST devrait avoir des règles strictes
        // (ex: seul l'hôte principal peut promouvoir en CO_HOST)
        if ($newRole === SpaceParticipantRole::CO_HOST) {
            if ($actor->getId() !== $space->host_user_id) {
                throw new RuntimeException("Seul l'hôte principal peut désigner des co-hôtes.");
            }
            // Limite du nombre de co-hôtes ?
        }

        $targetParticipant->role = $newRole;
        $targetParticipant->save();

        // Logique post-changement de rôle
        // - Déclencher un événement ParticipantRoleChanged
        //   event(new ParticipantRoleChanged($targetParticipant, $oldRole, $actor));
        // - Mettre à jour l'UI des participants en temps réel.

        return $targetParticipant;
    }
}