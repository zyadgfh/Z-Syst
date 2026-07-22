<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'tier_name' => $this->tier_name,
            'tier_label' => $this->tier_label,
            'price' => (float) $this->price,
            'min_quantity' => $this->min_quantity ? (float) $this->min_quantity : null,
            'max_quantity' => $this->max_quantity ? (float) $this->max_quantity : null,
            'customer_group_id' => $this->customer_group_id,
            'is_default' => $this->is_default,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}

