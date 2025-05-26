<?php

declare(strict_types=1);

namespace Gbairai\Core\Concerns;

use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceMessage;
use Gbairai\Core\Models\SpaceParticipant;
use Gbairai\Core\Models\AudioClip; // Importer pour les clips audio
use Illuminate\Database\Eloquent\Relations\HasMany;
use Gbairai\Core\Contracts\UserContract; // Importer
use Gbairai\Core\Models\Follow; // Importer
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importer


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
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(
            config('gbairai-core.models.user'), // Le modèle User
            config('gbairai-core.table_names.follows', 'follows'), // La table de jonction
            'following_user_id', // Clé étrangère sur la table de jonction pointant vers cet utilisateur (celui qui est suivi)
            'follower_user_id'   // Clé étrangère sur la table de jonction pointant vers l'autre utilisateur (celui qui suit)
        )->withTimestamps(); // Pour récupérer created_at/updated_at de la table de jonction
    }

    /**
     * Les utilisateurs que cet utilisateur suit (ses "followings").
     */
    public function followings(): BelongsToMany
    {
        return $this->belongsToMany(
            config('gbairai-core.models.user'), // Le modèle User
            config('gbairai-core.table_names.follows', 'follows'), // La table de jonction
            'follower_user_id',   // Clé étrangère sur la table de jonction pointant vers cet utilisateur (celui qui suit)
            'following_user_id'  // Clé étrangère sur la table de jonction pointant vers l'autre utilisateur (celui qui est suivi)
        )->withTimestamps();
    }

    /**
     * Vérifie si cet utilisateur suit un autre utilisateur donné.
     */
    public function isFollowing(UserContract $userToFollow): bool
    {
        if ($this->relationLoaded('followings')) {
            return $this->followings->contains($userToFollow);
        }
        return $this->followings()->where(config('gbairai-core.table_names.follows') . '.following_user_id', $userToFollow->getId())->exists();
    }
    
    /**
     * Get the audio clips created by this user.
     */
    public function createdAudioClips(): HasMany
    {
        return $this->hasMany(config('gbairai-core.models.audio_clip'), 'creator_user_id');
    }
}
