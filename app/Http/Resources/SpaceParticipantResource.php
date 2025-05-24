<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceParticipantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Gbairai\Core\Models\SpaceParticipant $this */
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'role' => $this->role->value,
            'role_label' => $this->role->label(),
            'joined_at' => $this->joined_at?->toIso8601String(),
            'is_muted_by_host' => $this->is_muted_by_host,
            'is_self_muted' => $this->is_self_muted,
            'has_raised_hand' => $this->has_raised_hand,
            // Ne pas exposer left_at sauf si c'est pertinent pour l'UI
        ];
    }
}