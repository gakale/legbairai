<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\Users;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Follow;
use RuntimeException;

class FollowUserAction
{
    /**
     * @throws RuntimeException
     */
    public function execute(UserContract $follower, UserContract $userToFollow): Follow
    {
        if ($follower->getId() === $userToFollow->getId()) {
            throw new RuntimeException("Vous ne pouvez pas vous suivre vous-même.");
        }

        if ($follower->isFollowing($userToFollow)) {
            // Peut-être retourner le Follow existant ou lever une exception/message.
            // Pour l'instant, on considère que c'est une erreur de tenter de suivre à nouveau.
            throw new RuntimeException("Vous suivez déjà cet utilisateur.");
        }

        /** @var Follow $follow */
        $follow = app(config('gbairai-core.models.follow'))->create([
            'follower_user_id' => $follower->getId(),
            'following_user_id' => $userToFollow->getId(),
        ]);

        // event(new UserStartedFollowing($follower, $userToFollow));
        // Notification pour $userToFollow
        // $userToFollow->notify(new NewFollowerNotification($follower));

        return $follow;
    }
}