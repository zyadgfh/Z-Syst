<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GRNItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grn_id' => $this->grn_id,
            'product_id' => $this->product_id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'sku' => $this->product->sku,
                ];
            }),
            'ordered_quantity' => $this->ordered_quantity,
            'received_quantity' => $this->received_quantity,
            'accepted_quantity' => $this->accepted_quantity,
            'rejected_quantity' => $this->rejected_quantity,
            'pending_quantity' => $this->pending_quantity,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'purchase_price' => $this->purchase_price,
            'total' => $this->total,
            'acceptance_rate' => $this->acceptance_rate,
            'notes' => $this->notes,
            'quality_checks_count' => $this->whenLoaded('qualityChecks', fn () => $this->qualityChecks->count()),
            'quality_checks' => QualityCheckResource::collection($this->whenLoaded('qualityChecks')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
