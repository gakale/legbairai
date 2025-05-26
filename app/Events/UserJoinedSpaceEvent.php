<?php

namespace App\Events;

use Gbairai\Core\Models\Space;
use Gbairai\Core\Models\SpaceParticipant; // Utiliser SpaceParticipant pour avoir le contexte complet
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel; // On pourrait utiliser PresenceChannel plus tard
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\SpaceParticipantResource; // Notre API Resource

class UserJoinedSpaceEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceParticipant $participant;
    public Space $space;

    /**
     * Create a new event instance.
     */
    public function __construct(SpaceParticipant $participant)
    {
        $this->participant = $participant->loadMissing('user'); // S'assurer que l'utilisateur est chargé
        $this->space = $participant->space; // Le space est déjà lié au participant
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Canal privé spécifique au Space. Seuls les utilisateurs autorisés (participants du Space)
        // pourront s'y abonner et recevoir cet événement.
        return [
            new PrivateChannel('space.' . $this->space->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.joined'; // Nom clair pour le client
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'participant' => (new SpaceParticipantResource($this->participant))->toArray(request()),
        ];
    }
}