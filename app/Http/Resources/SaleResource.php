<?php

namespace App\Http\Resources;

use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Sale */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_id' => $this->customer_id,
            'branch_id' => $this->branch_id,
            'user_id' => $this->user_id,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'change_amount' => $this->change_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'sale_type' => $this->sale_type,
            'prescription_id' => $this->prescription_id,
            'notes' => $this->notes,
            'company_id' => $this->company_id,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            $this->mergeWhen($this->relationLoaded('items'), [
                'items' => SaleItemResource::collection($this->whenLoaded('items')),
            ]),
            $this->mergeWhen($this->relationLoaded('user'), [
                'user' => new UserResource($this->whenLoaded('user')),
            ]),
        ];
    }
}
