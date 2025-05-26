<?php

declare(strict_types=1);

namespace Gbairai\Core\Contracts;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Interface UserContract
 *
 * @property string $id UUID
 * @property string $username
 * @property string $email
 * // ... autres propriétés ...
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\Space[] $hostedSpaces
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\SpaceParticipant[] $spaceParticipations
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\SpaceMessage[] $spaceMessages
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\User[] $followers // Correct
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\User[] $followings // MODIFIÉ ICI
 * @property-read int|null $followers_count
 * @property-read int|null $followings_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\AudioClip[] $createdAudioClips
 */
interface UserContract
{
    public function getId(): string;
    public function getUsername(): string;
    public function getEmail(): string;

    // Relations attendues par le package gbairai-core
    public function hostedSpaces(): HasMany;
    public function spaceParticipations(): HasMany;
    public function spaceMessages(): HasMany;

    // Relations de suivi
    public function followers(): BelongsToMany; // Ceux qui suivent cet utilisateur (Correct)
    public function followings(): BelongsToMany; // MODIFIÉ ICI : Ceux que cet utilisateur suit
    
    // Relations avec les clips audio
    public function createdAudioClips(): HasMany;

    public function isFollowing(UserContract $userToFollow): bool;
}