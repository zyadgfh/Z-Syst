<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierInvoicePaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'supplier_invoice_id' => $this->supplier_invoice_id,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toIso8601String(),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'bank_reference' => $this->bank_reference,
            'amount' => (float) $this->amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'file_path' => $this->file_path,
            'file_name' => $this->file_name,
            'file_mime_type' => $this->file_mime_type,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Computed fields
            'file_url' => $this->file_path ? asset('storage/'.$this->file_path) : null,

            // Relationships
            'invoice' => new SupplierInvoiceResource($this->whenLoaded('invoice')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'approved_by_user' => new UserResource($this->whenLoaded('approvedBy')),
        ];
    }
}
