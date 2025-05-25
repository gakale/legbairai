<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Users;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Follow;
use RuntimeException;

class UnfollowUserAction
{
    /**
     * @throws RuntimeException
     */
    public function execute(UserContract $follower, UserContract $userToUnfollow): bool
    {
        if ($follower->getId() === $userToUnfollow->getId()) {
            // On ne peut pas se "désuivre" soi-même, mais la relation n'existerait pas.
            return false;
        }

        $followInstance = app(config('gbairai-core.models.follow'))
            ::where('follower_user_id', $follower->getId())
            ->where('following_user_id', $userToUnfollow->getId())
            ->first();

        if (!$followInstance) {
            // L'utilisateur ne suivait pas déjà cette personne.
            // On pourrait lever une exception ou simplement retourner false/true.
            return false; // Ou true si on considère que l'état désiré est atteint.
        }

        $deleted = $followInstance->delete();

        // event(new UserStoppedFollowing($follower, $userToUnfollow));

        return (bool) $deleted;
    }
}