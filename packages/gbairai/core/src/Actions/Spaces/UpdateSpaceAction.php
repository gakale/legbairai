<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Spaces;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Enums\SpaceType;
use Gbairai\Core\Models\Space;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate; // Important pour vérifier les Policies

/**
 * Class UpdateSpaceAction
 *
 * Updates an existing Space.
 */
class UpdateSpaceAction
{
    /**
     * Execute the action.
     *
     * @param UserContract $user The user attempting to update the space.
     * @param Space $space The space to update.
     * @param array<string, mixed> $validatedData Data for updating the space.
     *        Assumed to be pre-validated and correctly typed (e.g., from a FormRequest).
     *        'type' should be a SpaceType Enum instance if provided.
     * @return Space The updated Space.
     * @throws \Illuminate\Auth\Access\AuthorizationException If the user is not authorized.
     * @throws \Throwable If an error occurs during saving.
     */
    public function execute(UserContract $user, Space $space, array $validatedData): Space
    {
        // 1. Autorisation via Policy
        Gate::forUser($user)->authorize('update', $space);

        // 2. Logique de mise à jour
        $fillableData = Arr::only($validatedData, [
            'title',
            'description',
            // 'type',
            'ticket_price',
            'currency',
            'max_participants',
            'is_recording_enabled_by_host',
            'scheduled_at',
            'cover_image_url',
        ]);

        if (array_key_exists('is_recording_enabled_by_host', $fillableData)) {
            Gate::forUser($user)->authorize('manageRecording', $space);
        }

        $space->fill($fillableData);

        if (isset($fillableData['scheduled_at']) && $space->status !== \Gbairai\Core\Enums\SpaceStatus::LIVE && $space->status !== \Gbairai\Core\Enums\SpaceStatus::ENDED) {
            $space->status = \Gbairai\Core\Enums\SpaceStatus::SCHEDULED;
        } elseif (isset($fillableData['scheduled_at']) && $fillableData['scheduled_at'] === null && $space->status === \Gbairai\Core\Enums\SpaceStatus::SCHEDULED) {
            // Pour l'instant, on le laisse tel quel
        }

        $space->save();

        // event(new SpaceUpdated($space));
        return $space;
    }
}
