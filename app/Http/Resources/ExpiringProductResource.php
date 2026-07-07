<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ExpiringProductResource extends JsonResource
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
            'branch_id' => $data->branch_id,
            'branch_name' => $data->branch_name,
            'batch_number' => $data->batch_number ?? null,
            'quantity' => $data->quantity,
            'expiry_date' => $data->expiry_date,
            'days_until_expiry' => $data->days_until_expiry,
            'value' => $data->value,
        ];
    }
}