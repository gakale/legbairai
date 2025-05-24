<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $this */
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar_url' => $this->avatar_url,
            // 'name' => $this->name, // Si vous avez un champ 'name'
            'is_verified' => $this->is_verified,
            'is_premium' => $this->is_premium,
            // N'exposez pas l'email ou d'autres infos sensibles sauf si nécessaire pour l'utilisateur authentifié lui-même
            // ou pour des contextes spécifiques.
        ];
    }
}