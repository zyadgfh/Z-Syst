<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'category_id' => $this->product_category_id,
            'sku' => $this->sku,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'cost_price' => $this->cost_price,
            'retail_price' => $this->retail_price,
            'wholesale_price' => $this->wholesale_price,
            'track_inventory' => (bool) $this->track_inventory,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            $this->mergeWhen($this->relationLoaded('category'), [
                'category' => new ProductCategoryResource($this->whenLoaded('category')),
            ]),

            $this->mergeWhen($this->relationLoaded('company'), [
                'company' => new CompanyResource($this->whenLoaded('company')),
            ]),
        ];
    }
}
