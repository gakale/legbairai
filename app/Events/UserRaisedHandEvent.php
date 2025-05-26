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

class UserRaisedHandEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceParticipant $participant;
    public bool $newHandStatus;

    /**
     * Create a new event instance.
     *
     * @param SpaceParticipant $participant
     * @param bool $newHandStatus
     */
    public function __construct(SpaceParticipant $participant, bool $newHandStatus)
    {
        $this->participant = $participant;
        $this->newHandStatus = $newHandStatus;
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
        return 'participant.hand_status';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'participant_id' => $this->participant->id,
            'user_id' => $this->participant->user_id,
            'has_raised_hand' => $this->newHandStatus,
        ];
    }
}