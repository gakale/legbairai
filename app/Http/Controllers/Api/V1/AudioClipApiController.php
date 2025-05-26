<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Gbairai\Core\Models\AudioClip;
use Gbairai\Core\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\AudioClips\CreateAudioClipAction;
use Illuminate\Http\JsonResponse;
use App\Models\User;

class AudioClipApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created audio clip in storage.
     *
     * @param Request $request
     * @param Space $space Le Space parent pour ce clip
     * @param CreateAudioClipAction $createAudioClipAction
     * @return JsonResponse
     */
    public function store(Request $request, Space $space, CreateAudioClipAction $createAudioClipAction): JsonResponse
    {
        // L'autorisation est gérée dans CreateAudioClipAction
        // La validation des données est aussi dans l'action, mais on peut pré-valider ici si besoin.
        
        $data = $request->validate([ // Validation de base ici pour la requête HTTP
            'title' => ['nullable', 'string', 'max:255'],
            'clip_url' => ['required', 'url', 'max:2048'],
            'start_time_in_space' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:300'],
        ]);

        $audioClip = $createAudioClipAction->execute(Auth::user(), $space, $data);

        return response()->json(new \App\Http\Resources\AudioClipResource($audioClip), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    
    /**
     * Route de test pour créer un clip audio sans authentification.
     * 
     * @param Request $request
     * @param Space $space
     * @param CreateAudioClipAction $createAudioClipAction
     * @return JsonResponse
     */
    public function testCreateClip(Request $request, Space $space, CreateAudioClipAction $createAudioClipAction): JsonResponse
    {
        // Validation des données
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'clip_url' => ['required', 'url', 'max:2048'],
            'start_time_in_space' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:300'],
        ]);
        
        // Pour le test, on utilise le premier utilisateur (ou l'hôte du space)
        // Dans un environnement de production, cela ne devrait jamais être fait ainsi
        $testUser = $space->host;
        if (!$testUser) {
            $testUser = User::first();
            if (!$testUser) {
                return response()->json(['error' => 'Aucun utilisateur disponible pour le test'], 500);
            }
        }
        
        try {
            $audioClip = $createAudioClipAction->execute($testUser, $space, $data);
            return response()->json(new \App\Http\Resources\AudioClipResource($audioClip), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
