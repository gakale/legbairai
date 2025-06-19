<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User; // Modèle User de l'application
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\Users\FollowUserAction;
use Gbairai\Core\Actions\Users\UnfollowUserAction;
use App\Http\Resources\UserResource as ApiUserResource; // Alias
use App\Http\Resources\SpaceResource as ApiSpaceResource; // Alias pour la ressource Space
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
        \Log::debug('Tentative de follow', [
            'follower' => $request->user()->id,
            'user_to_follow' => $user->id ?? null,
            'user_exists' => $user->exists
        ]);
        
        abort_unless($user->exists, 404, 'Utilisateur non trouvé');
        
        try {
            $follow = $followUserAction->execute($request->user(), $user);
            return response()->json(['message' => 'Follow réussi', 'data' => $follow]);
        } catch (\Exception $e) {
            \Log::error('Erreur follow', ['error' => $e->getMessage()]);
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function unfollow(Request $request, User $user, UnfollowUserAction $unfollowUserAction)
    {
        abort_unless($user->exists, 404, 'Utilisateur non trouvé');
        
        try {
            $unfollowUserAction->execute($request->user(), $user);
            return response()->json(['message' => 'Vous ne suivez plus cet utilisateur']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Récupérer les spaces créés par un utilisateur
     */
    public function getUserSpaces(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'status' => ['sometimes', 'string', 'in:scheduled,live,ended'],
        ]);

        $page = $validatedData['page'] ?? 1;
        $perPage = $validatedData['per_page'] ?? 20;
        $status = $validatedData['status'] ?? null;

        $query = $user->hostedSpaces()
            ->with(['host'])
            ->withCount(['participants' => function ($query) {
                $query->whereNull('left_at');
            }])
            ->orderBy('created_at', 'desc');

        // Filtrer par statut si spécifié
        if ($status) {
            $query->where('status', $status);
        }

        $spaces = $query->paginate($perPage, ['*'], 'page', $page);

        return ApiSpaceResource::collection($spaces);
    }

    /**
     * Récupérer les spaces où un utilisateur a participé
     */
    public function getParticipatedSpaces(Request $request, User $user)
    {
        $validatedData = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $page = $validatedData['page'] ?? 1;
        $perPage = $validatedData['per_page'] ?? 20;

        // Récupérer les spaces où l'utilisateur a participé
        $spaceIds = $user->spaceParticipations()
            ->distinct()
            ->pluck('space_id');

        $spaces = Space::whereIn('id', $spaceIds)
            ->with(['host'])
            ->withCount(['participants' => function ($query) {
                $query->whereNull('left_at');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return ApiSpaceResource::collection($spaces);
    }
}