<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAuditDetailResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'stock_audit_id' => $this->stock_audit_id,
            'business_id' => $this->business_id,
            'product_id' => $this->product_id,
            'stock_id' => $this->stock_id,
            'batch_no' => $this->batch_no,
            'expire_date' => $this->expire_date ? $this->expire_date->format('Y-m-d') : null,
            'system_quantity' => $this->system_quantity,
            'physical_quantity' => $this->physical_quantity,
            'variance' => $this->variance,
            'unit_cost' => $this->unit_cost,
            'variance_value' => $this->variance_value,
            'variance_type' => $this->variance_type,
            'notes' => $this->notes,
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
