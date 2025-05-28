<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Gbairai\Core\Models\Space;
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

class SpaceApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index']);
    }

    /**
     * Display a listing of the resource.
     * Implémente un algorithme de tri avancé pour afficher les espaces pertinents
     * en fonction du statut (LIVE/SCHEDULED), des utilisateurs suivis et de la popularité
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->input('per_page', 15);
        
        // Solution temporaire - requête simplifiée
        $spaces = Space::query()
            ->with(['host'])
            ->paginate($perPage);
        
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
        // La policy 'view' sera appliquée automatiquement par Laravel si vous utilisez $this->authorize()
        // ou si le middleware Authorize est appliqué à la route.
        // Pour l'instant, on suppose que si la route est atteinte, la policy a été vérifiée en amont
        // ou que la visibilité est publique.
        $this->authorize('view', $space); // Explicite pour être sûr

        // Charger les participants actifs pour l'affichage
        $space->load(['host', 'participants' => function ($query) {
            $query->whereNull('left_at')->with('user'); // Charger l'utilisateur du participant
        }]);
        // TODO: Ajouter aussi le comptage des participants si nécessaire dans SpaceResource

        return new ApiSpaceResource($space);
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
}