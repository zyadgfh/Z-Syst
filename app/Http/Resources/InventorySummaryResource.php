<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InventorySummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = is_object($this->resource) ? $this->resource : (object) $this->resource;
        
        return [
            'total_products' => $data->total_products ?? 0,
            'total_stock_items' => $data->total_stock_items ?? 0,
            'total_quantity' => $data->total_quantity ?? 0,
            'total_stock_value' => $data->total_stock_value ?? 0,
            'total_retail_value' => $data->total_retail_value ?? 0,
            'low_stock_count' => $data->low_stock_count ?? 0,
            'out_of_stock_count' => $data->out_of_stock_count ?? 0,
            'overstock_count' => $data->overstock_count ?? 0,
        ];
    }
}