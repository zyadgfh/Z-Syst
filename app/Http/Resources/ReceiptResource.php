<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReceiptResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'receipt_number' => $this->receipt_number,
            'sale_id' => $this->sale_id,
            'purchase_id' => $this->purchase_id,
            'type' => $this->type,
            'status' => $this->status,
            'data' => $this->data,
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'purchase' => new PurchaseResource($this->whenLoaded('purchase')),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
