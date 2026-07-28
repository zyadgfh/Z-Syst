<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderReturnItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_order_return_id' => $this->purchase_order_return_id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity_returned' => $this->quantity_returned,
            'unit_cost' => $this->unit_cost,
            'total' => $this->total,
            'batch_number' => $this->batch_number,
        ];
    }
}
