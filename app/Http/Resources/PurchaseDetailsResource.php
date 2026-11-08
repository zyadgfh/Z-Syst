<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseDetailsResource extends JsonResource
{
    /**
     * Transform the purchase line into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'product_id' => $this->product_id,
            'purchase_without_tax' => (float) $this->purchase_without_tax,
            'purchase_with_tax' => (float) $this->purchase_with_tax,
            'profit_percent' => (float) $this->profit_percent,
            'sales_price' => (float) $this->sales_price,
            'wholesale_price' => (float) $this->wholesale_price,
            'quantities' => (int) $this->quantities,
            'batch_no' => $this->batch_no,
            'expire_date' => $this->expire_date?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
