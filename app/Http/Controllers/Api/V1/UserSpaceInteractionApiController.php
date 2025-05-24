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
}