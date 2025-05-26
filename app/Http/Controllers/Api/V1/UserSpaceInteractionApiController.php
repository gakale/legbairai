<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Gbairai\Core\Models\Space;
use Illuminate\Http\Request;
use Gbairai\Core\Actions\Participants\JoinSpaceAction;
use Gbairai\Core\Actions\Participants\LeaveSpaceAction;
use Gbairai\Core\Actions\Participants\RaiseHandAction;
use App\Http\Resources\SpaceParticipantResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Gbairai\Core\Actions\Messages\SendSpaceMessageAction; // Importer
use App\Http\Resources\SpaceMessageResource; 
use Gbairai\Core\Models\SpaceMessage; // Importer
use Gbairai\Core\Actions\Messages\PinSpaceMessageAction; // Importer

class UserSpaceInteractionApiController extends Controller
{
    public function join(Request $request, Space $space, JoinSpaceAction $joinSpaceAction): JsonResponse
    {
        $participant = $joinSpaceAction->execute(Auth::user(), $space);
        return response()->json(new SpaceParticipantResource($participant->load('user')), 201);
    }

    public function leave(Request $request, Space $space, LeaveSpaceAction $leaveSpaceAction): JsonResponse
    {
        $participant = $leaveSpaceAction->execute(Auth::user(), $space);
        if ($participant) {
            return response()->json(['message' => 'Vous avez quitté le Space.', 'participant_id' => $participant->id]);
        }
        return response()->json(['message' => 'Vous n\'étiez pas ou plus dans ce Space.'], 404);
    }

    public function raiseHand(Request $request, Space $space, RaiseHandAction $raiseHandAction): JsonResponse
    {
        $participant = $raiseHandAction->execute(Auth::user(), $space);
        return response()->json([
            'message' => 'Votre main est levée.',
            'participant' => new SpaceParticipantResource($participant->load('user'))
        ]);
    }

    public function muteParticipant(
        Request $request,
        Space $space,
        User $participantUser, // Model binding pour l'utilisateur cible
        MuteParticipantByHostAction $muteAction
    ): JsonResponse {
        $participant = $muteAction->execute(Auth::user(), $space, $participantUser);
        return response()->json(new SpaceParticipantResource($participant->load('user')));
    }

    public function unmuteParticipant(
        Request $request,
        Space $space,
        User $participantUser, // Model binding
        UnmuteParticipantByHostAction $unmuteAction
    ): JsonResponse {
        $participant = $unmuteAction->execute(Auth::user(), $space, $participantUser);
        return response()->json(new SpaceParticipantResource($participant->load('user')));
    }

    public function changeRole(
        Request $request, // On aura besoin des données du corps de la requête pour le nouveau rôle
        Space $space,
        User $participantUser,
        ChangeParticipantRoleAction $changeRoleAction
    ): JsonResponse {
        $validated = $request->validate([
            'role' => ['required', new \Illuminate\Validation\Rules\Enum(SpaceParticipantRole::class)],
        ]);

        $newRole = SpaceParticipantRole::from($validated['role']);

        $participant = $changeRoleAction->execute(Auth::user(), $space, $participantUser, $newRole);
        return response()->json(new SpaceParticipantResource($participant->load('user')));
    }

    public function sendMessage(
        Request $request, // Laravel Request pour récupérer le contenu
        Space $space,
        SendSpaceMessageAction $sendMessageAction
    ): JsonResponse {
        // La validation du contenu est dans SendSpaceMessageAction
        // L'autorisation est aussi gérée dans SendSpaceMessageAction

        $validatedData = $request->validate([
            'content' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $message = $sendMessageAction->execute(
            Auth::user(),
            $space,
            $validatedData['content']
        );

        // L'événement NewSpaceMessageEvent est déjà déclenché par l'action.
        // Le client recevra le message via WebSocket.
        // On retourne le message créé en réponse à la requête POST pour confirmation.
        return response()->json(new SpaceMessageResource($message), 201);
    }
    public function togglePinMessage(
        Request $request,
        SpaceMessage $spaceMessage, // Model binding pour le message
        PinSpaceMessageAction $pinMessageAction
    ): JsonResponse {
        $validatedData = $request->validate([
            'pin' => ['required', 'boolean'], // true pour épingler, false pour détacher
        ]);

        // L'autorisation est gérée dans PinSpaceMessageAction
        $updatedMessage = $pinMessageAction->execute(
            Auth::user(),
            $spaceMessage,
            $validatedData['pin']
        );

        // L'événement est déjà déclenché par l'action.
        return response()->json(new SpaceMessageResource($updatedMessage));
    }
}