<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GrnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grn_id' => $this->grn_id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'batch_number' => $this->batch_number,
            'quantity_received' => $this->quantity_received,
            'unit_cost' => $this->unit_cost,
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'manufacturing_date' => $this->manufacturing_date?->format('Y-m-d'),
            'rack_location' => $this->rack_location,
            'total' => $this->quantity_received * $this->unit_cost,
        ];
    }
}
