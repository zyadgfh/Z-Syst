<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SalesTrendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $data = is_object($this->resource) ? $this->resource : (object) $this->resource;

        return [
            'date' => $data->date ?? null,
            'total_sales' => $data->total_sales ?? 0,
            'total_revenue' => $data->total_revenue ?? 0,
            'average_transaction_value' => $data->average_transaction_value ?? 0,
        ];
    }
}
