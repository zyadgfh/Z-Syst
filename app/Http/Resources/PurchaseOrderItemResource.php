<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_id' => $this->purchase_order_id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'received_quantity' => $this->received_quantity,
            'pending_quantity' => $this->pending_quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Computed fields
            'is_fully_received' => $this->isFullyReceived(),
            'is_partially_received' => $this->isPartiallyReceived(),
            'remaining_quantity' => $this->getRemainingQuantity(),

            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
