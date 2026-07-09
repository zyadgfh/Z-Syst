<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LowStockProductResource extends JsonResource
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
            'current_quantity' => $data->current_quantity,
            'reorder_level' => $data->reorder_level,
            'reorder_quantity' => $data->reorder_quantity,
            'urgency' => $data->urgency,
        ];
    }
}
