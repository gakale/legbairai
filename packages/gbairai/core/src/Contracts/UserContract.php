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
 * @property string|null $phone_number
 * @property string|null $avatar_url
 * @property string|null $cover_photo_url
 * @property string|null $bio
 * @property bool $is_verified
 * @property bool $is_premium
 * @property string|null $paystack_customer_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\Space[] $hostedSpaces
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\SpaceParticipant[] $spaceParticipations
 * @property-read \Illuminate\Database\Eloquent\Collection|\Gbairai\Core\Models\SpaceMessage[] $spaceMessages
 * // Ajoutez d'autres relations ici
 */
interface UserContract
{
    public function getId(): string; // UUID

    public function getUsername(): string;

    public function getEmail(): string;

    // Relations attendues par le package gbairai-core
    public function hostedSpaces(): HasMany;

    public function spaceParticipations(): HasMany;

    public function spaceMessages(): HasMany;

    // Ajoutez d'autres méthodes de relation ici si nécessaire pour le package
    // public function donationsMade(): HasMany;
    // public function donationsReceived(): HasMany;
    // public function tickets(): HasMany;
    public function followedUsers(): BelongsToMany; // Ceux que cet utilisateur suit
    public function followers(): BelongsToMany; // Ceux qui suivent cet utilisateur
    // public function subscriptionsMade(): HasMany; // Abonnements que l'utilisateur a pris
    // public function subscriptionsReceived(): HasMany; // Abonnements que les créateurs reçoivent
    // public function notifications(): HasMany;
    public function isFollowing(UserContract $userToFollow): bool;

}
