<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log; // Optionnel: pour logger les cas anormaux
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
        Gate::forUser($user)->authorize('end', $space);

        // 2. Vérification de l'état actuel du Space
        if ($space->status !== SpaceStatus::LIVE) {
            if ($space->status === SpaceStatus::ENDED) {
                return $space; // Déjà terminé, aucune action nécessaire.
            }
            throw new RuntimeException("Le Space '{$space->title}' n'est pas en direct et ne peut pas être terminé.");
        }

        if ($space->started_at === null) {
            throw new RuntimeException("L'heure de début du Space '{$space->title}' est manquante. Impossible de calculer la durée.");
        }

        // 3. Mise à jour du Space
        $endedAt = Carbon::now();
        
        $durationSeconds = 0;
        if ($endedAt->isAfter($space->started_at)) {
            $durationSeconds = $endedAt->diffInSeconds($space->started_at);
        } elseif ($endedAt->equalTo($space->started_at)) {
            $durationSeconds = 0;
        } else {
            // Cas où ended_at serait avant started_at (anormal)
            Log::warning("Anomalie de temps pour Space ID: {$space->id}. 'ended_at' ({$endedAt->toIso8601String()}) est avant 'started_at' ({$space->started_at->toIso8601String()}). Durée mise à 0.");
            $durationSeconds = 0; 
        }

        $space->status = SpaceStatus::ENDED;
        $space->ended_at = $endedAt;
        // Assurer que duration_seconds est bien un entier et assigner
        $space->duration_seconds = (int) $durationSeconds;

        $space->save();

        // 4. Logique post-fin
        // event(new SpaceEnded($space, $user));

        return $space;
    }
}