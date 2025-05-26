<?php

namespace App\Http\Controllers;

use App\Events\UserJoinedSpaceEvent;
use App\Events\UserLeftSpaceEvent;
use App\Events\SpaceStartedEvent;
use App\Events\UserRaisedHandEvent;
use App\Events\ParticipantRoleChangedEvent;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Gbairai\Core\Actions\Participants\RaiseHandAction;
use Gbairai\Core\Actions\Participants\ChangeParticipantRoleAction;
use Gbairai\Core\Enums\SpaceParticipantRole;

class RealtimeTestController extends Controller
{
    /**
     * Affiche la page de test en temps réel
     */
    public function showRealtimeTest()
    {
        return view('realtime-test');
    }

    /**
     * Affiche la page de test des participants en temps réel
     */
    public function showParticipantsTest()
    {
        // Récupérer les 5 premiers espaces pour faciliter les tests
        $spaces = Space::take(5)->get(['id', 'title', 'host_user_id']);
        
        return view('space-participants-test', compact('spaces'));
    }

    /**
     * Déclenche l'événement SpaceStartedEvent pour un space spécifique
     */
    public function triggerSpaceStarted($spaceId)
    {
        try {
            $space = Space::find($spaceId);
            
            if (!$space) {
                return response()->json(['error' => 'Space non trouvé'], 404);
            }
            
            SpaceStartedEvent::dispatch($space);
            
            return response()->json([
                'success' => true,
                'message' => "Événement SpaceStartedEvent déclenché pour le space {$space->title}"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Simule un utilisateur qui rejoint un space
     */
    public function triggerUserJoined($spaceId)
    {
        try {
            $space = Space::find($spaceId);
            
            if (!$space) {
                return response()->json(['error' => 'Space non trouvé'], 404);
            }
            
            // Vérifier d'abord si nous avons un participant qui a quitté et peut être réutilisé
            $leftParticipant = SpaceParticipant::where('space_id', $space->id)
                ->whereNotNull('left_at')
                ->first();
                
            if ($leftParticipant) {
                // Réinitialiser le participant qui avait quitté
                $leftParticipant->left_at = null;
                $leftParticipant->save();
                
                // Déclencher l'événement
                UserJoinedSpaceEvent::dispatch($leftParticipant);
                
                return response()->json([
                    'success' => true,
                    'message' => "Utilisateur est revenu dans le space {$space->title}",
                    'participant' => [
                        'id' => $leftParticipant->id,
                        'user_id' => $leftParticipant->user_id,
                        'role' => $leftParticipant->role
                    ]
                ]);
            }
            
            // Trouver un utilisateur qui n'est pas déjà participant dans ce space
            $existingParticipantUserIds = SpaceParticipant::where('space_id', $space->id)
                ->pluck('user_id')
                ->toArray();
            
            $availableUser = User::whereNotIn('id', $existingParticipantUserIds)->first();
            
            if (!$availableUser) {
                return response()->json([
                    'success' => false, 
                    'error' => 'Tous les utilisateurs sont déjà participants ou ont déjà été participants'
                ], 400);
            }
            
            // Créer un nouveau participant
            $participant = new SpaceParticipant();
            $participant->id = Str::uuid()->toString();
            $participant->space_id = $space->id;
            $participant->user_id = $availableUser->id;
            $participant->role = 'listener';
            $participant->has_raised_hand = false;
            $participant->is_muted_by_host = false;
            $participant->save();
            
            // Déclencher l'événement
            UserJoinedSpaceEvent::dispatch($participant);
            
            return response()->json([
                'success' => true,
                'message' => "Utilisateur a rejoint le space {$space->title}",
                'participant' => [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                    'role' => $participant->role
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Simule un utilisateur qui quitte un space
     */
    public function triggerUserLeft($spaceId)
    {
        try {
            $space = Space::find($spaceId);
            
            if (!$space) {
                return response()->json(['error' => 'Space non trouvé'], 404);
            }
            
            // Trouver un participant qui n'est pas l'hôte pour le faire quitter
            $participant = SpaceParticipant::where('space_id', $space->id)
                ->where('user_id', '!=', $space->host_user_id)
                ->whereNull('left_at')
                ->first();
            
            if (!$participant) {
                return response()->json(['error' => 'Aucun participant à faire quitter'], 404);
            }
            
            // Marquer le participant comme ayant quitté
            $participant->left_at = now();
            $participant->save();
            
            // Déclencher l'événement
            UserLeftSpaceEvent::dispatch($participant);
            
            return response()->json([
                'success' => true,
                'message' => "Utilisateur a quitté le space {$space->title}",
                'participant' => [
                    'id' => $participant->id,
                    'user_id' => $participant->user_id,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Simule un utilisateur qui lève la main dans un space
     */
    public function triggerRaiseHand(Request $request, $spaceId, $participantId)
    {
        try {
            $space = Space::findOrFail($spaceId);
            $participant = SpaceParticipant::where('id', $participantId)->where('space_id', $space->id)->firstOrFail();
            
            // For testing, we'll toggle the hand status. 
            // In a real app, the desired status would come from the request.
            $newHandStatus = !$participant->has_raised_hand;

            // L'acteur est le participant lui-même ou l'hôte
            // Pour ce test, on considère que le participant agit pour lui-même
            $actor = $participant->user;

            // Mise à jour manuelle du statut de la main pour le test
            $participant->has_raised_hand = $newHandStatus;
            $participant->save();
            
            // Déclencher l'événement manuellement
            UserRaisedHandEvent::dispatch($participant, $newHandStatus);

            return response()->json([
                'success' => true,
                'message' => "Main de {$participant->user->name} changée à " . ($newHandStatus ? 'levée' : 'baissée') . " dans le space {$space->title}",
                'participant_id' => $participant->id,
                'has_raised_hand' => $newHandStatus
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Space ou Participant non trouvé'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    /**
     * Simule un changement de rôle pour un participant dans un space
     */
    public function triggerChangeRole(Request $request, $spaceId, $participantId)
    {
        $validated = $request->validate([
            'new_role' => 'required|string|in:' . implode(',', SpaceParticipantRole::values()),
        ]);

        try {
            $space = Space::findOrFail($spaceId);
            $participant = SpaceParticipant::where('id', $participantId)->where('space_id', $space->id)->firstOrFail();
            $newRole = SpaceParticipantRole::from($validated['new_role']);

            // Pour ce test, on suppose que l'hôte du space est l'acteur
            $actor = $space->host;
            if (!$actor) {
                 // Fallback si l'hôte n'est pas chargé ou n'existe pas, prendre le premier admin par exemple
                $actor = User::where('is_admin', true)->first(); 
                if (!$actor) throw new \RuntimeException("Aucun acteur (hôte ou admin) disponible pour changer le rôle.");
            }

            // Mise à jour manuelle du rôle pour le test
            $oldRole = $participant->role;
            $participant->role = $newRole;
            $participant->save();
            
            // Déclencher l'événement manuellement
            ParticipantRoleChangedEvent::dispatch($participant);

            return response()->json([
                'success' => true,
                'message' => "Rôle de {$participant->user->name} changé à {$newRole->value} dans le space {$space->title}",
                'participant_id' => $participant->id,
                'new_role' => $newRole->value
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Space ou Participant non trouvé'], 404);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
