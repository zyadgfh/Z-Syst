<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardKPIsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = is_object($this->resource) ? $this->resource : (object) $this->resource;

        return [
            'total_sales' => $data->total_sales ?? 0,
            'total_revenue' => $data->total_revenue ?? 0,
            'average_transaction_value' => $data->average_transaction_value ?? 0,
            'total_products_sold' => $data->total_products_sold ?? 0,
            'total_stock_value' => $data->total_stock_value ?? 0,
            'low_stock_count' => $data->low_stock_count ?? 0,
            'expiring_soon_count' => $data->expiring_soon_count ?? 0,
            'dead_stock_count' => $data->dead_stock_count ?? 0,
        ];
    }
}
