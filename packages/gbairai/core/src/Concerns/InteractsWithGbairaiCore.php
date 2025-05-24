<?php

declare(strict_types=1);

namespace Gbairai\Core\Concerns;

use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceMessage;
use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait InteractsWithGbairaiCore
{
    /**
     * Get the spaces hosted by this user.
     */
    public function hostedSpaces(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.space'), 'host_user_id');
    }

    /**
     * Get the space participations for this user.
     */
    public function spaceParticipations(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.space_participant'), 'user_id');
    }

    /**
     * Get the space messages sent by this user.
     */
    public function spaceMessages(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.space_message'), 'user_id');
    }

    // TODO: Ajouter d'autres relations au fur et à mesure
    // donationsMade, donationsReceived, tickets, follows, subscriptions, notifications
}
