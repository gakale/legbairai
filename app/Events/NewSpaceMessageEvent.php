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
use App\Http\Resources\SpaceMessageResource; // Nous allons créer cette ressource

class NewSpaceMessageEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceMessage $message;

    /**
     * Create a new event instance.
     */
    public function __construct(SpaceMessage $message)
    {
        // S'assurer que l'utilisateur (expéditeur) est chargé pour la ressource
        $this->message = $message->loadMissing('user');
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
        return 'message.new';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => (new SpaceMessageResource($this->message))->toArray(request()),
        ];
    }
}