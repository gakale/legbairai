<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Participants;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Support\Carbon;
use RuntimeException;
use App\Events\UserLeftSpaceEvent;
// Pas besoin de Gate ici, car si on est participant, on devrait pouvoir quitter.
// Sauf si l'hôte ne peut pas quitter sans terminer le space (règle spéciale).

/**
 * Class LeaveSpaceAction
 *
 * Allows a user to leave a Space they are part of.
 */
class LeaveSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user leaving the space.
     * @param Space $space The space to leave.
     * @return SpaceParticipant|null The updated participant record, or null if not found.
     * @throws RuntimeException
     */
    public function execute(UserContract $user, Space $space): ?SpaceParticipant
    {
        /** @var SpaceParticipant|null $participant */
        $participant = $space->participants()
            ->where('user_id', $user->getId())
            ->whereNull('left_at') // S'assurer qu'ils n'ont pas déjà quitté
            ->first();

        if (!$participant) {
            // L'utilisateur n'est pas (ou plus) un participant actif de ce space.
            // On pourrait ne rien faire ou lever une exception discrète.
            return null;
        }

        // Règle spéciale: L'hôte principal ne peut pas "quitter" sans que le Space se termine.
        // Cette logique est gérée par la règle "Fermeture automatique si l'hôte principal quitte".
        // Donc, si l'hôte appelle cette action, cela devrait déclencher la fin du Space.
        // Pour l'instant, cette action ne gère que le départ d'un participant non-hôte.
        // Une "EndSpaceIfHostLeavesAction" serait plus appropriée pour l'hôte.
        if ($space->host_user_id === $user->getId()) {
             throw new RuntimeException("L'hôte principal ne peut pas quitter le Space de cette manière. Utilisez l'action pour terminer le Space.");
             // Ou appeler EndSpaceAction ici :
             // app(EndSpaceAction::class)->execute($user, $space);
             // return $participant; // Et retourner le participant qui est maintenant parti car le space est terminé.
        }


        $participant->left_at = Carbon::now();
        // Potentiellement réinitialiser certains états
        $participant->has_raised_hand = false;
        // Si c'était un speaker, on pourrait le repasser en listener ou gérer son droit de parole
        // $participant->role = SpaceParticipantRole::LISTENER; // Si on veut qu'il redevienne listener en quittant

        $participant->save();

        // Logique post-départ
        // - Déclencher un événement UserLeftSpace
        UserLeftSpaceEvent::dispatch($participant); // Déclencher l'événement
        // - Mettre à jour le compteur de participants en temps réel.
        // - Si c'était le dernier co-hôte ou intervenant, des logiques spécifiques pourraient s'appliquer.

        return $participant;
    }
}