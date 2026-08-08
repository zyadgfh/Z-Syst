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
            'invoice_number' => $this->invoiceNumber,
            'purchase_date' => $this->purchaseDate,
            'total_amount' => $this->totalAmount,
            'paid_amount' => $this->paidAmount,
            'due_amount' => $this->dueAmount,
            'discount_amount' => $this->discountAmount,
            'tax_amount' => $this->tax_amount,
            'payment_type' => $this->paymentType,
            'is_paid' => (bool) $this->isPaid,
            'note' => $this->note,
            'party' => new PartyResource($this->whenLoaded('party')),
        ];
    }
}
