<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'po_number' => $this->po_number,
            'company_id' => $this->company_id,
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn() => $this->branch->only(['id', 'name'])),
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'expected_delivery_date' => $this->expected_delivery_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->created_by,
            'createdBy' => $this->whenLoaded('createdBy', fn() => $this->createdBy->only(['id', 'name'])),
            'approved_by' => $this->approved_by,
            'approvedBy' => $this->whenLoaded('approvedBy', fn() => $this->approvedBy->only(['id', 'name'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}