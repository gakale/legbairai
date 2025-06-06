<?php

namespace App\Http\Controllers;

use App\Events\UserJoinedSpaceEvent;
use App\Events\UserLeftSpaceEvent;
use App\Events\SpaceStartedEvent;
use App\Events\UserRaisedHandEvent;
use App\Events\ParticipantRoleChangedEvent;
use App\Events\ParticipantMutedStatusChangedEvent;
use App\Events\NewSpaceMessageEvent;
use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Broadcast;
use App\Notifications\NewFollowerNotification;
use Gbairai\Core\Models\SpaceMessage;
use Gbairai\Core\Actions\Participants\RaiseHandAction;
use Gbairai\Core\Actions\Participants\ChangeParticipantRoleAction;
use Gbairai\Core\Actions\Participants\MuteParticipantByHostAction;
use Gbairai\Core\Actions\Participants\UnmuteParticipantByHostAction;
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
     * Affiche la page de test des notifications
     */
    public function showNotificationsTest()
    {
        // Récupérer quelques utilisateurs pour les tests
        $users = User::latest()->take(5)->get();
        
        return view('notifications-test', compact('users'));
    }
    
    /**
     * Affiche la page de test du feed des espaces
     */
    public function showSpacesFeedTest()
    {
        return view('spaces-feed-test');
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
    
    /**
     * Simule l'envoi d'un message dans un espace (sans authentification pour les tests)
     */
    public function testSendMessage(Request $request, $spaceId)
    {
        $validated = $request->validate([
            'content' => 'required|string|min:1|max:1000',
            'sender_name' => 'nullable|string|max:255'
        ]);

        try {
            $space = Space::findOrFail($spaceId);
            
            // Créer un message de test
            $message = new \Gbairai\Core\Models\SpaceMessage();
            $message->id = Str::uuid()->toString();
            $message->space_id = $space->id;
            
            // Utiliser l'ID de l'hôte de l'espace comme expéditeur par défaut
            $senderId = $space->host_user_id;
            $sender = User::find($senderId);
            
            if (!$sender) {
                // Fallback : utiliser le premier utilisateur disponible
                $sender = User::first();
                if (!$sender) {
                    throw new \RuntimeException("Aucun utilisateur disponible pour envoyer le message.");
                }
            }
            
            $message->user_id = $sender->id;
            $message->content = $validated['content'];
            $message->save();
            
            // Nous n'avons pas besoin de charger la relation utilisateur car nous allons construire manuellement les données
            
            // Déclencher l'événement
            // Pour les tests, nous diffusons directement sur un canal public
            $messageData = [
                'message' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'created_at' => $message->created_at->toIso8601String(),
                    'created_at_formatted' => $message->created_at->format('H:i'),
                    'sender' => [
                        'id' => $sender->id,
                        'name' => $validated['sender_name'] ?? $sender->name,
                        'username' => $sender->username ?? $sender->name
                    ]
                ]
            ];
            
            // Utiliser directement l'API de Pusher
            $pusher = Broadcast::driver()->getPusher();
            $pusher->trigger(
                'test-space.' . $space->id,
                'message.new',
                $messageData
            );
            
            return response()->json([
                'success' => true,
                'message' => "Message envoyé dans l'espace {$space->title}",
                'data' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'sender' => [
                        'id' => $sender->id,
                        'name' => $sender->name
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Espace non trouvé'], 404);
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
     * Simule l'épinglage ou le détachement d'un message dans un espace (sans authentification pour les tests)
     */
    public function testTogglePinMessage(Request $request, $messageId)
    {
        $validated = $request->validate([
            'pin' => 'required|boolean',
        ]);

        try {
            // Récupérer le message
            $message = SpaceMessage::findOrFail($messageId);
            $space = Space::findOrFail($message->space_id);
            
            // Mettre à jour le statut d'épinglage
            if ($validated['pin']) {
                // Si on épingle ce message, détacher tous les autres messages épinglés dans cet espace
                SpaceMessage::where('space_id', $space->id)
                    ->where('id', '!=', $message->id)
                    ->where('is_pinned', true)
                    ->update(['is_pinned' => false]);
            }
            
            // Mettre à jour le statut du message cible
            $message->is_pinned = $validated['pin'];
            $message->save();
            
            // Charger les relations nécessaires pour l'événement
            $message->load('user');
            
            // Diffuser l'événement
            $messageData = [
                'message' => [
                    'id' => $message->id,
                    'content' => $message->content,
                    'is_pinned' => $message->is_pinned,
                    'created_at' => $message->created_at->toIso8601String(),
                    'created_at_formatted' => $message->created_at->format('H:i'),
                    'sender' => $message->user ? [
                        'id' => $message->user->id,
                        'name' => $message->user->name,
                        'username' => $message->user->username ?? $message->user->name
                    ] : null
                ]
            ];
            
            // Utiliser directement l'API de Pusher
            $pusher = Broadcast::driver()->getPusher();
            $pusher->trigger(
                'test-space.' . $space->id,
                'message.pinned_status_changed',
                $messageData
            );
            
            return response()->json([
                'success' => true,
                'message' => $validated['pin'] ? "Message épinglé avec succès" : "Message détaché avec succès",
                'data' => $messageData['message']
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Message ou espace non trouvé'], 404);
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
     * Simule le mute d'un participant par l'hôte (sans authentification pour les tests)
     * Cette méthode contourne les vérifications de sécurité pour les besoins de test
     */
    public function testMuteParticipant(Request $request, $spaceId, $participantId)
    {
        try {
            // Récupérer l'espace et le participant
            $space = Space::findOrFail($spaceId);
            $participant = SpaceParticipant::findOrFail($participantId);
            
            // Vérifier que le participant appartient bien à cet espace
            if ($participant->space_id !== $space->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce participant n\'appartient pas à cet espace'
                ], 400);
            }
            
            // Pour les tests, mettre directement à jour le statut du participant
            // plutôt que d'utiliser l'action qui a des vérifications de sécurité
            $participant->is_muted_by_host = true;
            $participant->save();
            
            // Déclencher l'événement manuellement
            ParticipantMutedStatusChangedEvent::dispatch($participant);
            
            // Diffuser manuellement l'événement pour les tests
            $participantData = [
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'is_muted_by_host' => $participant->is_muted_by_host
            ];
            
            // Utiliser directement l'API de Pusher
            $pusher = Broadcast::driver()->getPusher();
            $pusher->trigger(
                'test-space.' . $space->id,
                'participant.muted_status_changed',
                $participantData
            );
            
            return response()->json([
                'success' => true,
                'message' => "Participant muté avec succès",
                'data' => $participantData
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Espace ou participant non trouvé'], 404);
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
     * Simule le unmute d'un participant par l'hôte (sans authentification pour les tests)
     * Cette méthode contourne les vérifications de sécurité pour les besoins de test
     */
    public function testUnmuteParticipant(Request $request, $spaceId, $participantId)
    {
        try {
            // Récupérer l'espace et le participant
            $space = Space::findOrFail($spaceId);
            $participant = SpaceParticipant::findOrFail($participantId);
            
            // Vérifier que le participant appartient bien à cet espace
            if ($participant->space_id !== $space->id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Ce participant n\'appartient pas à cet espace'
                ], 400);
            }
            
            // Pour les tests, mettre directement à jour le statut du participant
            // plutôt que d'utiliser l'action qui a des vérifications de sécurité
            $participant->is_muted_by_host = false;
            $participant->save();
            
            // Déclencher l'événement manuellement
            ParticipantMutedStatusChangedEvent::dispatch($participant);
            
            // Diffuser manuellement l'événement pour les tests
            $participantData = [
                'participant_id' => $participant->id,
                'user_id' => $participant->user_id,
                'is_muted_by_host' => $participant->is_muted_by_host
            ];
            
            // Utiliser directement l'API de Pusher
            $pusher = Broadcast::driver()->getPusher();
            $pusher->trigger(
                'test-space.' . $space->id,
                'participant.muted_status_changed',
                $participantData
            );
            
            return response()->json([
                'success' => true,
                'message' => "Participant démuté avec succès",
                'data' => $participantData
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Espace ou participant non trouvé'], 404);
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
     * Simule l'envoi d'une notification de nouvel abonné (sans authentification pour les tests)
     */
    public function testSendFollowerNotification(Request $request)
    {
        try {
            $validated = $request->validate([
                'follower_id' => 'required|exists:users,id',
                'target_id' => 'required|exists:users,id',
            ]);
            
            // Récupérer les utilisateurs
            $follower = User::findOrFail($validated['follower_id']);
            $target = User::findOrFail($validated['target_id']);
            
            // Envoyer la notification directement
            $target->notify(new NewFollowerNotification($follower));
            
            return response()->json([
                'success' => true,
                'message' => "Notification de nouvel abonné envoyée avec succès",
                'data' => [
                    'follower' => [
                        'id' => $follower->id,
                        'name' => $follower->name
                    ],
                    'target' => [
                        'id' => $target->id,
                        'name' => $target->name
                    ]
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'error' => 'Utilisateur non trouvé'], 404);
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
