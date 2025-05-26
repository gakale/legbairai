<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Participants;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceParticipantRole;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
//use Illuminate\Support\Facades\Gate; // Pour une potentielle policy
use RuntimeException;
use App\Events\UserRaisedHandEvent; // Importer l'événement


/**
 * Class RaiseHandAction
 *
 * Allows a listener in a Space to raise their hand to request speaking rights.
 */
class RaiseHandAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user raising their hand.
     * @param Space $space The space in which the user is a participant.
     * @return SpaceParticipant The updated participant record.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RuntimeException
     */
    public function execute(UserContract $user, Space $space): SpaceParticipant
    {
        // 1. Vérifier si l'utilisateur peut lever la main dans ce space
        //    (Ex: le space est LIVE, l'utilisateur est un participant actif et est un auditeur)
        //    Une policy sur SpaceParticipant pourrait gérer cela: SpaceParticipantPolicy@raiseHand
        //    Gate::forUser($user)->authorize('raiseHand', $participantRecord);

        /** @var SpaceParticipant|null $participant */
        $participant = $space->participants()
            ->where('user_id', $user->getId())
            ->whereNull('left_at')
            ->first();

        if (!$participant) {
            throw new RuntimeException("Vous n'êtes pas un participant actif de ce Space.");
        }

        if ($space->status !== SpaceStatus::LIVE) {
            throw new RuntimeException("Vous ne pouvez lever la main que dans un Space en direct.");
        }

        // Seuls les auditeurs peuvent lever la main. Les intervenants et co-hôtes parlent déjà.
        if ($participant->role !== SpaceParticipantRole::LISTENER) {
            throw new RuntimeException("Seuls les auditeurs peuvent lever la main. Votre rôle actuel est : " . $participant->role->label());
        }

        if ($participant->has_raised_hand) {
            // Main déjà levée, on pourrait la baisser ou juste ne rien faire
            // Pour l'instant, on ne change rien si déjà levée.
            // Ou on pourrait permettre de baisser la main via la même action (toggle)
            // $participant->has_raised_hand = false; // Pour baisser la main
            // $participant->save();
            // return $participant;
            throw new RuntimeException("Votre main est déjà levée.");
        }

        // 2. Mettre à jour le statut de la main levée
        $participant->has_raised_hand = true;
        $participant->save();

        // 3. Logique post-"main levée"
        // - Déclencher un événement UserRaisedHand
        UserRaisedHandEvent::dispatch($participant, $newHandStatus);
        // - Notifier l'hôte et les co-hôtes (via WebSockets).

        return $participant;
    }
}