<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use App\Events\SpaceStartedEvent;
use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceStatus;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use RuntimeException; // Pour les exceptions métier

/**
 * Class StartSpaceAction
 *
 * Starts a scheduled or draft Space, setting its status to LIVE.
 */
class StartSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user attempting to start the space (host or co-host).
     * @param Space $space The space to start.
     * @return Space The started Space.
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user is not authorized.
     * @throws RuntimeException If the space cannot be started (e.g., wrong status, already started).
     */
    public function execute(UserContract $user, Space $space): Space
    {
        // 1. Autorisation via Policy
        // La policy 'start' devrait vérifier si l'utilisateur est l'hôte (ou co-hôte)
        // et si le space est dans un état qui permet de le démarrer (ex: SCHEDULED).
        Gate::forUser($user)->authorize('start', $space);

        // 2. Vérification de l'état actuel du Space
        if ($space->status === SpaceStatus::LIVE) {
            throw new RuntimeException("Le Space '{$space->title}' est déjà en direct.");
        }

        if ($space->status === SpaceStatus::ENDED || $space->status === SpaceStatus::CANCELLED) {
            throw new RuntimeException("Le Space '{$space->title}' est terminé ou annulé et ne peut pas être démarré.");
        }

        // 3. Mise à jour du Space
        $space->status = SpaceStatus::LIVE;
        $space->started_at = Carbon::now(); // Enregistrer l'heure de début
        $space->ended_at = null; // S'assurer que ended_at est nul s'il avait été défini par erreur
        $space->duration_seconds = null; // Réinitialiser la durée

        $space->save();

        // 4. Logique post-démarrage
        // - Ajouter l'hôte/starter comme participant actif si ce n'est pas déjà fait.
        //   (Cela pourrait être géré par une autre action AddParticipantToSpaceAction)
        //
        // - Envoyer des notifications aux followers du créateur.
        //   (Utiliser le système de Queues de Laravel pour cela)
        //   Notification::send($space->host->followers, new SpaceStartedNotification($space));
        
        // Déclencher l'événement SpaceStarted pour le broadcasting en temps réel
        SpaceStartedEvent::dispatch($space);

        return $space;
    }
}
