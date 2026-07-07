<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockTurnoverResource extends JsonResource
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
            'current_stock' => $data->current_stock,
            'sold_period' => $data->sold_period,
            'turnover_rate' => $data->turnover_rate,
            'days_of_supply' => $data->days_of_supply,
            'turnover_category' => $data->turnover_category,
        ];
    }
}