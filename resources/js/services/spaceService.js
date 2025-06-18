<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Gbairai\Core\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Resources\SpaceResource as ApiSpaceResource; // Alias pour éviter conflit
use Gbairai\Core\Actions\Spaces\CreateSpaceAction;
use Gbairai\Core\Actions\Spaces\StartSpaceAction;
use Gbairai\Core\Actions\Spaces\EndSpaceAction;
use Gbairai\Core\Http\Requests\StoreSpaceRequest as CoreStoreSpaceRequest; // Request du package
use Gbairai\Core\Http\Requests\UpdateSpaceRequest as CoreUpdateSpaceRequest; // Importer

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\Spaces\UpdateSpaceAction; // Importer
use Illuminate\Http\Response; // Pour le code 204 No Content
use Illuminate\Database\Eloquent\Builder;
use App\Events\AudioStreamEvent; // Import the new event

class SpaceApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * Implémente un algorithme de tri avancé pour afficher les espaces pertinents
     * en fonction du statut (LIVE/SCHEDULED), des utilisateurs suivis et de la popularité
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user(); // Utilisateur authentifié (peut être null)

        $perPage = (int) $request->input('per_page', 15);

        // Récupérer les valeurs des énumérations sous forme de chaînes pour les utiliser dans les requêtes SQL
        $liveStatus = \Gbairai\Core\Enums\SpaceStatus::LIVE->value;
        $scheduledStatus = \Gbairai\Core\Enums\SpaceStatus::SCHEDULED->value;
        
        $spacesQuery = Space::query()
            ->with(['host', 'participants' => function ($query) { // Charger participants actifs pour le comptage
                $query->whereNull('left_at');
            }])
            ->withCount(['participants as active_participants_count' => function ($query) {
                $query->whereNull('left_at');
            }])
            ->whereIn('status', [ // On ne montre que les spaces live ou programmés
                $liveStatus,
                $scheduledStatus
            ]);

        // 1. Prioriser les Spaces LIVE
        // 2. Ensuite, parmi les LIVE, ceux des créateurs suivis (si utilisateur connecté)
        // 3. Ensuite, les autres LIVE par popularité (nombre de participants)
        // 4. Ensuite, les Spaces SCHEDULED, ceux des créateurs suivis en premier
        // 5. Ensuite, les autres SCHEDULED par date de programmation la plus proche

        // Construction de l'ordre
        $spacesQuery->orderByRaw("
            CASE
                WHEN status = ? THEN 1 -- Spaces LIVE en premier
                ELSE 2                 -- Spaces SCHEDULED ensuite
            END ASC,
            CASE
                WHEN status = ? THEN (SELECT MAX(p.joined_at) FROM space_participants p WHERE p.space_id = spaces.id AND p.left_at IS NULL) -- Pour trier les LIVE par activité récente si pas de following
                ELSE spaces.scheduled_at -- Pour trier les SCHEDULED par date
            END DESC
        ", [$liveStatus, $liveStatus]);


        if ($user) {
            $followingUserIds = $user->followings()->pluck('users.id'); // Récupère les IDs des utilisateurs que $user suit

            if ($followingUserIds->isNotEmpty()) {
                // Ajoute un critère pour booster les spaces des utilisateurs suivis
                // Cela se combine avec le tri par statut LIVE/SCHEDULED
                $spacesQuery->orderByRaw("
                    CASE
                        WHEN host_user_id IN (?) THEN 0 -- Priorité pour les spaces des suivis
                        ELSE 1
                    END ASC
                ", [$followingUserIds->implode(',')]);
            }
        }

        // Tri secondaire pour les LIVE : par nombre de participants actifs (décroissant)
        // Tri secondaire pour les SCHEDULED : par date de programmation (croissant - le plus proche en premier)
        
        // Pour PostgreSQL, on ne peut pas utiliser un alias de colonne défini dans la clause SELECT
        // directement dans la clause ORDER BY du même niveau de requête
        // On doit donc répéter la sous-requête pour le comptage
        $spacesQuery->orderByRaw("
            CASE
                WHEN status = ? THEN (SELECT COUNT(*) FROM space_participants WHERE space_participants.space_id = spaces.id AND space_participants.left_at IS NULL) -- Pour les LIVE
                ELSE NULL
            END DESC NULLS LAST,
            CASE
                WHEN status = ? THEN scheduled_at -- Pour les SCHEDULED
                ELSE NULL
            END ASC NULLS LAST
        ", [$liveStatus, $scheduledStatus]);

        $spaces = $spacesQuery->paginate($perPage);

        return ApiSpaceResource::collection($spaces);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CoreStoreSpaceRequest $request, CreateSpaceAction $createSpaceAction): JsonResponse
    {
        // CoreStoreSpaceRequest gère l'autorisation et la validation.
        // $request->user() est l'utilisateur authentifié.
        // L'action CreateSpaceAction attend l'hôte comme premier argument.
        // Ici, on suppose que l'utilisateur authentifié est l'hôte.
        // Si un admin peut créer pour un autre, il faudrait passer host_user_id dans validated data.
        $space = $createSpaceAction->execute(Auth::user(), $request->validated());

        return response()->json(new ApiSpaceResource($space->load('host')), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Space $space): ApiSpaceResource // Model binding
    {
        try {
            // La policy 'view' sera appliquée automatiquement par Laravel
            $this->authorize('view', $space);
            
            // Charger les participants actifs pour l'affichage
            $space->load(['host', 'participants' => function ($query) {
                $query->whereNull('left_at')->with('user'); // Charger l'utilisateur du participant
            }]);
            // TODO: Ajouter aussi le comptage des participants si nécessaire dans SpaceResource
            
            return new ApiSpaceResource($space);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            // Si nous sommes en environnement de développement, permettre l'accès pour faciliter les tests
            if (app()->environment('local')) {
                \Illuminate\Support\Facades\Log::warning('Accès autorisé en mode développement malgré l\'erreur d\'autorisation', [
                    'space_id' => $space->id,
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'error' => $e->getMessage()
                ]);
                
                // Charger les participants actifs pour l'affichage
                $space->load(['host', 'participants' => function ($query) {
                    $query->whereNull('left_at')->with('user'); // Charger l'utilisateur du participant
                }]);
                
                return new ApiSpaceResource($space);
            }
            
            // En production, renvoyer l'erreur d'autorisation
            throw $e;
        }
    }

    /**
     * Start a scheduled space.
     */
    public function start(Request $request, Space $space, StartSpaceAction $startSpaceAction): JsonResponse
    {
        $startedSpace = $startSpaceAction->execute(Auth::user(), $space);
        return response()->json(new ApiSpaceResource($startedSpace->load('host')));
    }

    /**
     * End a live space.
     */
    public function end(Request $request, Space $space, EndSpaceAction $endSpaceAction): JsonResponse
    {
        $endedSpace = $endSpaceAction->execute(Auth::user(), $space);
        return response()->json(new ApiSpaceResource($endedSpace->load('host')));
    }

    public function update(CoreUpdateSpaceRequest $request, Space $space, UpdateSpaceAction $updateSpaceAction): JsonResponse
    {
        // CoreUpdateSpaceRequest gère l'autorisation pour 'update' et la validation des données.
        // L'action UpdateSpaceAction gère aussi une vérification de policy 'update' et 'manageRecording'.
        $updatedSpace = $updateSpaceAction->execute(Auth::user(), $space, $request->validated());

        return response()->json(new ApiSpaceResource($updatedSpace->load('host')));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Space $space, DeleteSpaceAction $deleteSpaceAction): Response
    {
        // L'autorisation est gérée à l'intérieur de DeleteSpaceAction via Gate::authorize('delete', $space)
        $deleteSpaceAction->execute(Auth::user(), $space);

        return response()->noContent(); // Code 204: Succès, pas de contenu à retourner
    }

    /**
     * Handle audio signaling for WebRTC.
     */
    public function handleAudioSignal(Request $request, Space $space): JsonResponse
    {
        // Authorize that the user is a participant of the space
        // You might have a more specific policy or check, e.g., 'participate'
        // For simplicity, checking if the user is part of the space's participants
        // This assumes 'participants' is a loaded relationship or can be queried.
        // Adjust authorization as per your application's logic.
        $this->authorize('view', $space); // Re-use 'view' or define a more granular policy like 'signalInSpace'

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Validate the incoming signal data
        $validated = $request->validate([
            'signalData' => 'required|array',
        ]);

        // Dispatch the event
        broadcast(new AudioStreamEvent($space->id, $validated['signalData'], $user->id))->toOthers();

        return response()->json(['message' => 'Signal processed']);
    }
}