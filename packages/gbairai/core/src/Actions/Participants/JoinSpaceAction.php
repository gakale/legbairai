<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Participants;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceParticipantRole;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use RuntimeException;

/**
 * Class JoinSpaceAction
 *
 * Allows a user to join a Space.
 */
class JoinSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user joining the space.
     * @param Space $space The space to join.
     * @param SpaceParticipantRole $role The initial role (default: LISTENER).
     * @return SpaceParticipant The created participant record.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws RuntimeException
     */
    public function execute(
        UserContract $user,
        Space $space,
        SpaceParticipantRole $role = SpaceParticipantRole::LISTENER
    ): SpaceParticipant {
        // 1. Autorisation via Policy (SpacePolicy@join)
        Gate::forUser($user)->authorize('join', $space);

        // 2. Vérifications métier supplémentaires
        if ($space->status !== SpaceStatus::LIVE) {
            throw new RuntimeException("Le Space '{$space->title}' n'est pas en direct. Impossible de le rejoindre.");
        }

        // Vérifier si l'utilisateur est déjà participant
        $existingParticipant = $space->participants()
            ->where('user_id', $user->getId())
            ->first();

        if ($existingParticipant) {
            // Si l'utilisateur est déjà là (peut-être a-t-il quitté puis rejoint)
            // On pourrait le réactiver ou simplement retourner sa participation existante.
            // Pour l'instant, on considère que s'il est là, il est là.
            // Si left_at est défini, on pourrait le remettre à null.
            if ($existingParticipant->left_at !== null) {
                $existingParticipant->left_at = null;
                $existingParticipant->joined_at = Carbon::now(); // Mettre à jour l'heure de "rejoint"
                $existingParticipant->role = $role; // Mettre à jour le rôle au cas où
                $existingParticipant->save();
            }
            return $existingParticipant;
        }

        // Vérifier le nombre maximum de participants (si défini)
        if ($space->max_participants !== null && $space->participants()->whereNull('left_at')->count() >= $space->max_participants) {
            throw new RuntimeException("Le Space '{$space->title}' a atteint son nombre maximum de participants.");
        }

        // Vérifier la règle de participation unique (un utilisateur ne peut être actif que dans un seul space à la fois)
        // Cette règle est complexe et nécessite de vérifier si l'utilisateur est 'speaker' ou 'co-host'
        // dans d'autres spaces 'LIVE'.
        // Nous l'implémenterons plus en détail plus tard. Pour l'instant, on met un placeholder.
        // if ($this->isUserActiveInAnotherSpace($user, $space->id)) {
        //     throw new RuntimeException("Vous participez déjà activement à un autre Space.");
        // }

        // 3. Création du participant
        /** @var SpaceParticipant $participant */
        $participant = app(config('gbairai-core.models.space_participant'))->create([
            'space_id' => $space->id,
            'user_id' => $user->getId(),
            'role' => $role,
            'joined_at' => Carbon::now(),
            'is_muted_by_host' => true, // Par défaut, muté par l'hôte
            'is_self_muted' => true,    // Par défaut, auto-muté
            'has_raised_hand' => false,
        ]);

        // 4. Logique post- ज्वाइन
        // - Déclencher un événement UserJoinedSpace
        //   event(new UserJoinedSpace($participant));
        // - Mettre à jour le compteur de participants en temps réel (via WebSockets)

        return $participant;
    }

    // Placeholder pour la règle de participation unique
    // protected function isUserActiveInAnotherSpace(UserContract $user, string $currentSpaceId): bool
    // {
    //     return SpaceParticipant::where('user_id', $user->getId())
    //         ->where('space_id', '!=', $currentSpaceId)
    //         ->whereIn('role', [SpaceParticipantRole::SPEAKER->value, SpaceParticipantRole::CO_HOST->value])
    //         ->whereHas('space', function ($query) {
    //             $query->where('status', SpaceStatus::LIVE->value);
    //         })
    //         ->whereNull('left_at') // S'assurer qu'ils n'ont pas quitté ces autres spaces
    //         ->exists();
    // }
}