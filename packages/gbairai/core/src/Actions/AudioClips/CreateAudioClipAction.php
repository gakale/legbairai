<?php

declare(strict_types=1);

namespace Gbairai\Core\Actions\AudioClips;

use Gbairai\Core\Contracts\UserContract;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\AudioClip;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;

class CreateAudioClipAction
{
    /**
     * Creates a new audio clip metadata record.
     *
     * @param UserContract $creator The user creating the clip.
     * @param Space $space The space from which the clip originates.
     * @param array<string, mixed> $data Data for the clip.
     *        Expected keys: 'clip_url', 'start_time_in_space', 'duration_seconds', 'title' (optional).
     * @return AudioClip The created audio clip.
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function execute(UserContract $creator, Space $space, array $data): AudioClip
    {
        // 1. Autorisation: Seul l'hôte du Space (ou un utilisateur avec une permission spécifique) peut créer un clip.
        //    Pour l'instant, on va dire que seul l'hôte peut.
        if ($space->host_user_id !== $creator->getId()) {
            // Gate::forUser($creator)->authorize('createClip', $space); // Si vous voulez une policy
            throw new AuthorizationException("Seul l'hôte du Space peut créer des clips.");
        }

        // 2. Validation des données
        $validatedData = Validator::make($data, [
            'title' => ['nullable', 'string', 'max:255'],
            'clip_url' => ['required', 'url', 'max:2048'], // URL vers le fichier audio
            'start_time_in_space' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['required', 'integer', 'min:1', 'max:300'], // Ex: max 5 minutes (300s)
        ])->validate();

        // 3. Vérifier que le clip ne dépasse pas la durée de l'enregistrement du Space (si disponible)
        //    Cette logique est plus avancée et nécessite l'accès à SpaceRecording.
        //    Pour l'instant, on la laisse de côté.
        //    if ($space->recording && ($validatedData['start_time_in_space'] + $validatedData['duration_seconds']) > $space->recording->duration_seconds) {
        //        throw ValidationException::withMessages(['duration_seconds' => 'La durée du clip dépasse la durée de l\'enregistrement du Space.']);
        //    }

        // Créer une instance du modèle AudioClip en utilisant le nom complet de la classe
        $audioClip = new \Gbairai\Core\Models\AudioClip([
            'space_id' => $space->id,
            'creator_user_id' => $creator->getId(),
            'title' => $validatedData['title'] ?? 'Clip de ' . $space->title,
            'clip_url' => $validatedData['clip_url'],
            'start_time_in_space' => $validatedData['start_time_in_space'],
            'duration_seconds' => $validatedData['duration_seconds'],
            'views_count' => 0,
        ]);
        $audioClip->save();

        // event(new AudioClipCreated($audioClip));

        return $audioClip;
    }
}
