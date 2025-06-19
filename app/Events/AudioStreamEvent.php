<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
// Assuming App\Models\User exists. If it's Gbairai\Core\Contracts\UserContract or similar, adjust accordingly.
use App\Models\User;

class AudioStreamEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $spaceId;
    public array $signalData; // To carry WebRTC signaling data
    public string $userId; // User sending the signal

    /**
     * Create a new event instance.
     *
     * @param string $spaceId
     * @param array $signalData
     * @param string $userId
     */
    public function __construct(string $spaceId, array $signalData, string $userId)
    {
        $this->spaceId = $spaceId;
        $this->signalData = $signalData;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        // Use PresenceChannel to leverage existing participant info and auth
        return [
            new PresenceChannel('space.' . $this->spaceId),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'audio.signal'; // Naming it for signaling, e.g., offer, answer, candidate
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        // This data will be received by clients listening on the PresenceChannel
        return [
            'from' => $this->userId, // Renommer pour correspondre au client
            'signal' => $this->signalData, // The actual WebRTC signal (offer, answer, ICE candidate)
            'user_id' => $this->userId, // Garder pour compatibilité
        ];
    }
}
