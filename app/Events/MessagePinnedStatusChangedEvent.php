<?php

namespace App\Events;

use Gbairai\Core\Models\SpaceMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\SpaceMessageResource; // Notre API Resource

class MessagePinnedStatusChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(SpaceMessage $message)
    {
        // L'utilisateur (expéditeur) devrait déjà être chargé par PinSpaceMessageAction
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('space.' . $this->message->space_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.pinned_status_changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // On envoie le message entier car son statut 'is_pinned' a changé,
        // et si d'autres messages ont été détachés, le client pourrait avoir besoin de les mettre à jour aussi.
        // Une alternative serait d'envoyer seulement l'ID du message et son nouvel état is_pinned.
        return [
            'message' => (new SpaceMessageResource($this->message))->toArray(request()),
            // Si on détachait d'autres messages, on pourrait envoyer un signal pour rafraîchir la liste
            // ou envoyer la liste des ID des messages détachés.
            // Pour l'instant, on envoie le message principal affecté.
        ];
    }
}