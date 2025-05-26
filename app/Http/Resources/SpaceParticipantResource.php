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
            'role' => $this->role, // Changé car role est une chaîne simple, pas un Enum
            'joined_at' => $this->joined_at?->toIso8601String(),
            'is_muted_by_host' => $this->is_muted_by_host ?? false,
            'is_self_muted' => $this->is_self_muted ?? false,
            'has_raised_hand' => $this->has_raised_hand ?? false,
            'user_id' => $this->user_id, // Ajouté pour faciliter l'identification
            // Ne pas exposer left_at sauf si c'est pertinent pour l'UI
        ];
    }
}