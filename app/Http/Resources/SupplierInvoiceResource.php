<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'purchase_id' => $this->purchase_id,
            'purchase_order_id' => $this->purchase_order_id,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date?->toIso8601String(),
            'due_date' => $this->due_date?->toIso8601String(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'status' => $this->status,
            'paid_amount' => (float) $this->paid_amount,
            'balance' => (float) $this->balance,
            'currency' => $this->currency,
            'payment_terms' => $this->payment_terms,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_mime_type' => $this->file_mime_type,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Computed fields
            'payment_percentage' => $this->getPaymentPercentage(),
            'days_until_due' => $this->getDaysUntilDue(),
            'is_due_soon' => $this->isDueSoon(),
            'is_critically_overdue' => $this->isCriticallyOverdue(),
            'file_url' => $this->file_path ? asset('storage/'.$this->file_path) : null,

            // Relationships
            'supplier' => new PartyResource($this->whenLoaded('supplier')),
            'purchase' => new PurchaseResource($this->whenLoaded('purchase')),
            'purchase_order' => new PurchaseOrderResource($this->whenLoaded('purchaseOrder')),
            'items' => SupplierInvoiceItemResource::collection($this->whenLoaded('items')),
            'payments' => SupplierInvoicePaymentResource::collection($this->whenLoaded('payments')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'approved_by_user' => new UserResource($this->whenLoaded('approvedBy')),
        ];
    }
}
