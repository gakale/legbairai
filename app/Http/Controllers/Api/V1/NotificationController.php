<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\DatabaseNotification; // Pour le typage

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Récupérer les notifications non lues et lues, paginées
        $notifications = $user->notifications()
                              ->orderBy('created_at', 'desc')
                              ->paginate($request->input('per_page', 15));
        // Ou seulement les non lues: $user->unreadNotifications()->paginate(...);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // S'assurer que la notification appartient à l'utilisateur authentifié
        if ($notification->notifiable_id !== $user->id || $notification->notifiable_type !== get_class($user)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.', 'notification' => $notification]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}