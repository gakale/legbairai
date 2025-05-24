<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Facades\Gate;

/**
 * Class DeleteSpaceAction
 *
 * Deletes a Space.
 */
class DeleteSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user attempting to delete the space.
     * @param Space $space The space to delete.
     * @return bool True on success, false otherwise (ou void).
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user is not authorized.
     * @throws \Throwable On other errors.
     */
    public function execute(UserContract $user, Space $space): bool
    {
        Gate::forUser($user)->authorize('delete', $space);

        if ($space->status === \Gbairai\Core\Enums\SpaceStatus::LIVE) {
            // Idéalement, appeler une EndSpaceAction ici, qui pourrait ensuite permettre la suppression.
            // Pour simplifier pour l'instant, on pourrait interdire la suppression d'un space live.
            // throw new \RuntimeException("Cannot delete a live space. Please end it first.");
            // Alternative: $endSpaceAction->execute($user, $space); (si on injecte EndSpaceAction)
        }

        $deleted = $space->delete();

        if ($deleted) {
            // event(new SpaceDeleted($space->id, $user->getId()));
        }

        return (bool) $deleted;
    }
}
