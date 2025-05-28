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
            throw new \RuntimeException('Vous ne pouvez pas vous désuivre vous-même.');
        }

        $follow = Follow::where([
            'follower_user_id' => $follower->getId(),
            'following_user_id' => $userToUnfollow->getId()
        ])->first();

        if (!$follow) {
            throw new \RuntimeException('Vous ne suivez pas cet utilisateur.');
        }

        return $follow->delete();
    }
}