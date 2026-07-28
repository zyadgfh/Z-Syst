<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'supplier_id' => $this->supplier_id,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'branch_id' => $this->branch_id,
            'branch' => $this->whenLoaded('branch', fn () => $this->branch->only(['id', 'name'])),
            'return_number' => $this->return_number,
            'notes' => $this->notes,
            'total_amount' => $this->totalAmount(),
            'total_quantity_returned' => $this->totalQuantityReturned(),
            'items' => PurchaseOrderReturnItemResource::collection($this->whenLoaded('items')),
            'created_by' => $this->created_by,
            'createdBy' => $this->whenLoaded('createdBy', fn () => $this->createdBy->only(['id', 'name'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
