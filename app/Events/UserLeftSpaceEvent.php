<?php

namespace App\Events;

use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant; // Le participant qui quitte
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
// Pas besoin de ressource ici, on envoie juste l'ID de l'utilisateur et du participant

class UserLeftSpaceEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Space $space;
    public string $userId; // ID de l'utilisateur qui a quitté
    public string $participantId; // ID de l'enregistrement SpaceParticipant

    /**
     * Create a new event instance.
     */
    public function __construct(SpaceParticipant $participant)
    {
        $this->space = $participant->space;
        $this->userId = $participant->user_id;
        $this->participantId = $participant->id;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('space.' . $this->space->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.left';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'participant_id' => $this->participantId, // Utile pour le client pour supprimer l'élément de l'UI
        ];
    }
}