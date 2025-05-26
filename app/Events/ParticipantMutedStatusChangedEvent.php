<?php

namespace App\Events;

use Gbairai\Core\Models\SpaceParticipant;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel; // Diffuser sur le canal de présence du Space
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantMutedStatusChangedEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SpaceParticipant $participant; // Le participant dont le statut de mute a changé
    // Nous envoyons tout l'objet participant car son état a changé.
    // Alternativement, on pourrait juste envoyer participant_id et le nouvel état de is_muted_by_host.

    /**
     * Create a new event instance.
     */
    public function __construct(SpaceParticipant $participant)
    {
        $this->participant = $participant;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('space.' . $this->participant->space_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'participant.muted_status_changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Envoyer les informations nécessaires pour mettre à jour l'UI client
        return [
            'participant_id' => $this->participant->id,
            'user_id' => $this->participant->user_id,
            'is_muted_by_host' => $this->participant->is_muted_by_host,
            // On pourrait aussi envoyer le username pour faciliter l'affichage côté client,
            // mais le client devrait déjà avoir les infos du participant via le PresenceChannel.
            // 'username' => $this->participant->user->username, // Nécessiterait ->load('user')
        ];
    }
}