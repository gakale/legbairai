<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User; // Modèle User de l'application
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\Users\FollowUserAction;
use Gbairai\Core\Actions\Users\UnfollowUserAction;
use App\Http\Resources\UserResource as ApiUserResource; // Alias
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserApiController extends Controller
{
    public function show(Request $request, User $user): ApiUserResource // $user est l'utilisateur cible du profil
    {
        /** @var User $currentUser */
        $currentUser = $request->user(); // L'utilisateur authentifié qui regarde le profil

        // Charger les compteurs et si l'utilisateur actuel suit l'utilisateur du profil
        $user->loadCount(['followers', 'followings']);

        // Ajouter une propriété temporaire pour indiquer si l'utilisateur actuel suit cet utilisateur.
        // Ceci sera récupéré par UserResource.
        if ($currentUser) {
            $user->is_followed_by_current_user = $currentUser->isFollowing($user);
        } else {
            $user->is_followed_by_current_user = false;
        }

        return new ApiUserResource($user);
    }

    public function follow(Request $request, User $user, FollowUserAction $followUserAction)
    {
        /** @var User $follower */
        $follower = Auth::user();

        // L'action lèvera une exception si on essaie de se suivre soi-même ou si on suit déjà.
        $followUserAction->execute($follower, $userToFollow);

        return response()->json(['message' => "Vous suivez maintenant {$userToFollow->username}."], 201);
    }

    public function unfollow(Request $request, User $user, UnfollowUserAction $unfollowUserAction)
    {
        /** @var User $follower */
        $follower = Auth::user();

        if ($unfollowUserAction->execute($follower, $userToUnfollow)) {
            return response()->json(['message' => "Vous ne suivez plus {$userToUnfollow->username}."]);
        }
        // Si on arrive ici, c'est que l'utilisateur ne suivait pas déjà cette personne (ou s'est désuivi lui-même).
        return response()->noContent(); // Ou un message d'erreur/info si vous préférez
    }
}