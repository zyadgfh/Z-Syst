<?php

declare(strict_types=1);

namespace App\Modules\Products\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Product API Resource
 *
 * Formats the Product model for JSON API responses.
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'generic_name' => $this->generic_name,
            'barcode' => $this->barcode,
            'sku' => $this->product_code,
            'purchase_price' => (float) $this->purchase_price,
            'sale_price' => (float) $this->sales_price,
            'reorder_level' => (int) ($this->reorder_level ?? 0),
            'current_stock' => (float) ($this->stocks_sum_productStock ?? 0),
            'is_active' => (bool) $this->is_active,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'manufacturer' => $this->whenLoaded('manufacturer', fn () => [
                'id' => $this->manufacturer->id,
                'name' => $this->manufacturer->name,
            ]),
            'stocks' => $this->whenLoaded('stocks', fn () => $this->stocks->map(fn ($stock) => [
                'id' => $stock->id,
                'batch_no' => $stock->batch_no,
                'expire_date' => $stock->expire_date?->format('Y-m-d'),
                'quantity' => (float) $stock->productStock,
                'purchase_price' => (float) ($stock->purchase_price ?? 0),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

