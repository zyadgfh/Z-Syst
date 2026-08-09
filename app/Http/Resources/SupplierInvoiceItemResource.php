<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_invoice_id' => $this->supplier_invoice_id,
            'product_id' => $this->product_id,
            'purchase_detail_id' => $this->purchase_detail_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount' => (float) $this->discount,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date?->toIso8601String(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
            'purchase_detail' => new PurchaseDetailsResource($this->whenLoaded('purchaseDetail')),
        ];
    }
}
