<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DonationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        /** @var \Gbairai\Core\Models\Donation $this */
        return [
            'id' => $this->id,
            'amount_subunit' => $this->amount_subunit,
            'amount_formatted' => $this->amount, // Utilise l'accesseur getAmountAttribute()
            'currency' => $this->currency,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'donor' => new UserResource($this->whenLoaded('donor')),
            'recipient' => new UserResource($this->whenLoaded('recipient')),
            'space_id' => $this->space_id,
            'paystack_reference' => $this->paystack_reference,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}