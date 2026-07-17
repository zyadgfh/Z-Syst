<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'batch_no' => $this->batch_no,
            'productStock' => $this->productStock,
            'expire_date' => $this->expire_date?->format('Y-m-d'),
            'created_at' => $this->created_at,
        ];
    }
}