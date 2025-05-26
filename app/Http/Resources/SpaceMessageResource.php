<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Gbairai\Core\Models\SpaceMessage $this */
        return [
            'id' => $this->id,
            'space_id' => $this->space_id,
            'content' => $this->content,
            'is_pinned' => $this->is_pinned,
            'sender' => new UserResource($this->whenLoaded('user')), // Utilise notre UserResource existante
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_formatted' => $this->created_at->format('H:i'), // Exemple de formatage
        ];
    }
}