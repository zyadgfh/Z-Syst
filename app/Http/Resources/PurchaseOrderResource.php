<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
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
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'po_number' => $this->po_number,
            'status' => $this->status,
            'priority' => $this->priority,
            'expected_delivery_date' => $this->expected_delivery_date?->toIso8601String(),
            'actual_delivery_date' => $this->actual_delivery_date?->toIso8601String(),
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'terms' => $this->terms,
            'internal_notes' => $this->internal_notes,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'rejected_at' => $this->rejected_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Computed fields
            'total_quantity' => $this->getTotalQuantity(),
            'received_quantity' => $this->getReceivedQuantity(),
            'pending_quantity' => $this->getPendingQuantity(),
            'completion_percentage' => $this->getCompletionPercentage(),
            
            // Relationships
            'supplier' => new PartyResource($this->whenLoaded('supplier')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
            'created_by_user' => new UserResource($this->whenLoaded('createdBy')),
            'approved_by_user' => new UserResource($this->whenLoaded('approvedBy')),
        ];
    }
}
