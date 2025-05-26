<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AudioClipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Gbairai\Core\Models\AudioClip $this */
        return [
            'id' => $this->id,
            'title' => $this->title,
            'clip_url' => $this->clip_url,
            'start_time_in_space' => $this->start_time_in_space,
            'duration_seconds' => $this->duration_seconds,
            'views_count' => $this->views_count,
            'creator' => $this->whenLoaded('creator', function() {
                return [
                    'id' => $this->creator->getId(),
                    'username' => $this->creator->getUsername(),
                ];
            }),
            'space' => $this->whenLoaded('space', function() {
                return [
                    'id' => $this->space->id,
                    'title' => $this->space->title,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
