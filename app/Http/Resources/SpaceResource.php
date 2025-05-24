<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \Gbairai\Core\Models\Space $this */
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'status' => $this->status->value, // Valeur de l'enum
            'status_label' => $this->status->label(), // Label lisible
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'ticket_price' => $this->when($this->type === \Gbairai\Core\Enums\SpaceType::PUBLIC_PAID, $this->ticket_price),
            'currency' => $this->when($this->type === \Gbairai\Core\Enums\SpaceType::PUBLIC_PAID, $this->currency),
            'max_participants' => $this->max_participants,
            'is_recording_enabled_by_host' => $this->is_recording_enabled_by_host,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration_seconds' => $this->duration_seconds,
            'host' => new UserResource($this->whenLoaded('host')), // Charge l'hôte si la relation est chargée
            // 'participants_count' => $this->whenCounted('participants'), // Si vous chargez le compte
            // 'participants' => SpaceParticipantResource::collection($this->whenLoaded('participants')), // Pour plus tard
            'created_at' => $this->created_at?->toIso8601String(),

            // URLs pour les actions (exemple, à adapter selon vos routes)
            // 'can_join' => auth()->check() && auth()->user()->can('join', $this->resource),
            // 'can_start' => auth()->check() && auth()->user()->can('start', $this->resource),
        ];
    }
}