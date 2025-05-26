<?php

namespace App\Events;

use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantRoleChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceParticipant $participant;

    /**
     * Create a new event instance.
     *
     * @param SpaceParticipant $participant
     */
    public function __construct(SpaceParticipant $participant)
    {
        $this->participant = $participant;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('space.' . $this->participant->space_id);
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'participant.role_changed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        // Ensure the participant model is loaded with necessary relations if any
        // For now, we send basic participant info. Adjust as needed for UI updates.
        return [
            'participant_id' => $this->participant->id,
            'user_id' => $this->participant->user_id,
            'role' => $this->participant->role->value, // Assuming role is an enum
            'name' => $this->participant->user->name, // Example: if you need to update name
            // Add other participant attributes that might change or are needed by the UI
        ];
    }
}