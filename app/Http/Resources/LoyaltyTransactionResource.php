<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'loyalty_program_id' => $this->loyalty_program_id,
            'party_id' => $this->party_id,
            'points' => $this->points,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'reference_type' => $this->reference_type,
            'transaction_date' => $this->transaction_date?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'party' => new PartyResource($this->whenLoaded('party')),
            'loyalty_program' => new LoyaltyProgramResource($this->whenLoaded('loyaltyProgram')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
