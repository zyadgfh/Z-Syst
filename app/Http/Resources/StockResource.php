<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'product_id' => $this->product_id,
            'productStock' => $this->productStock,
            'batch_no' => $this->batch_no,
            'expire_date' => $this->expire_date?->toIso8601String(),
            'barcode' => $this->barcode,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
