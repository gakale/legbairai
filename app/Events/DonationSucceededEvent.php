<?php

namespace App\Events;

use Gbairai\Core\Models\Donation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Resources\DonationResource; // Nous allons créer cette ressource

class DonationSucceededEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Donation $donation;

    /**
     * Create a new event instance.
     */
    public function __construct(Donation $donation)
    {
        // Charger les relations nécessaires pour la ressource
        $this->donation = $donation->loadMissing(['donor', 'recipient', 'space']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * Diffuser au créateur (bénéficiaire) et potentiellement à tous dans le Space.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        // Canal privé du bénéficiaire
        $channels[] = new PrivateChannel('App.Models.User.' . $this->donation->recipient_user_id);

        // Si le don est lié à un Space et que le Space est encore actif (LIVE)
        if ($this->donation->space_id && $this->donation->space?->status === \Gbairai\Core\Enums\SpaceStatus::LIVE) {
            $channels[] = new PresenceChannel('space.' . $this->donation->space_id);
        }
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'donation.succeeded';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'donation' => (new DonationResource($this->donation))->toArray(request()),
            // On pourrait ajouter un message plus direct ici si nécessaire pour l'UI
            // 'message' => "{$this->donation->donor->username} a fait un don de {$this->donation->amount} {$this->donation->currency}!",
        ];
    }
}