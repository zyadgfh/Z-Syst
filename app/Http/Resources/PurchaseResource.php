<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'party_id' => $this->party_id,
            'user_id' => $this->user_id,
            'invoice_number' => $this->invoice_number,
            'purchase_date' => $this->purchase_date,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'due_amount' => $this->due_amount,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'payment_type' => $this->payment_type,
            'is_paid' => (bool) $this->is_paid,
            'note' => $this->note,
            'party' => new PartyResource($this->whenLoaded('party')),
        ];
    }
}
