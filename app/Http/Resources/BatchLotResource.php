<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BatchLotResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'product_id' => $this->product_id,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'quantity' => $this->quantity,
            'manufacturing_date' => $this->manufacturing_date?->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'supplier_id' => $this->supplier_id,
            'purchase_id' => $this->purchase_id,
            'storage_location' => $this->storage_location,
            'cost_per_unit' => $this->cost_per_unit,
            'status' => $this->status,
            'is_expired' => $this->isExpired(),
            'is_recalled' => $this->isRecalled(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
