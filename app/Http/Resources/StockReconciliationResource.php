<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockReconciliationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'stock_audit_id' => $this->stock_audit_id,
            'user_id' => $this->user_id,
            'product_id' => $this->product_id,
            'stock_id' => $this->stock_id,
            'batch_no' => $this->batch_no,
            'expire_date' => $this->expire_date ? $this->expire_date->format('Y-m-d') : null,
            'adjustment_type' => $this->adjustment_type,
            'previous_quantity' => $this->previous_quantity,
            'new_quantity' => $this->new_quantity,
            'adjustment_quantity' => $this->adjustment_quantity,
            'unit_cost' => $this->unit_cost,
            'adjustment_value' => $this->adjustment_value,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reason' => $this->reason,
            'is_posted' => $this->is_posted,
            'posted_at' => $this->posted_at ? $this->posted_at->format('Y-m-d H:i:s') : null,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->productName,
                    'code' => $this->product->productCode,
                ];
            }),
            'stock' => $this->whenLoaded('stock', function () {
                return [
                    'id' => $this->stock->id,
                    'batch_no' => $this->stock->batch_no,
                    'quantity' => $this->stock->productStock,
                ];
            }),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
