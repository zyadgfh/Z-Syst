<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TopSellingProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = is_object($this->resource) ? $this->resource : (object) $this->resource;

        return [
            'product_id' => $data->product_id,
            'product_name' => $data->product_name,
            'sku' => $data->sku ?? null,
            'total_quantity' => $data->total_quantity,
            'total_revenue' => $data->total_revenue,
            'total_sales' => $data->total_sales,
        ];
    }
}
