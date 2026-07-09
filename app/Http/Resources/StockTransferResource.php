<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * StockTransferResource
 *
 * API resource for transforming StockTransfer models to JSON responses.
 * Follows the JSON:API specification for consistent API responses.
 */
class StockTransferResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_number' => $this->transfer_number,
            'status' => $this->status,

            // Relationships
            'company' => [
                'id' => $this->company_id,
                'name' => $this->company->name ?? null,
            ],
            'from_branch' => [
                'id' => $this->from_branch_id,
                'name' => $this->fromBranch->name ?? null,
                'address' => $this->fromBranch->address ?? null,
            ],
            'to_branch' => [
                'id' => $this->to_branch_id,
                'name' => $this->toBranch->name ?? null,
                'address' => $this->toBranch->address ?? null,
            ],

            // User references
            'requested_by' => [
                'id' => $this->requested_by,
                'name' => $this->requestedBy->name ?? null,
                'email' => $this->requestedBy->email ?? null,
            ],
            'approved_by' => $this->approved_by ? [
                'id' => $this->approved_by,
                'name' => $this->approvedBy->name ?? null,
                'email' => $this->approvedBy->email ?? null,
            ] : null,
            'shipped_by' => $this->shipped_by ? [
                'id' => $this->shipped_by,
                'name' => $this->shippedBy->name ?? null,
                'email' => $this->shippedBy->email ?? null,
            ] : null,
            'received_by' => $this->received_by ? [
                'id' => $this->received_by,
                'name' => $this->receivedBy->name ?? null,
                'email' => $this->receivedBy->email ?? null,
            ] : null,

            // Transfer details
            'notes' => $this->notes,
            'rejection_reason' => $this->rejection_reason,

            // Timestamps
            'requested_at' => $this->requested_at ? $this->requested_at->format('Y-m-d H:i:s') : null,
            'approved_at' => $this->approved_at ? $this->approved_at->format('Y-m-d H:i:s') : null,
            'rejected_at' => $this->rejected_at ? $this->rejected_at->format('Y-m-d H:i:s') : null,
            'shipped_at' => $this->shipped_at ? $this->shipped_at->format('Y-m-d H:i:s') : null,
            'received_at' => $this->received_at ? $this->received_at->format('Y-m-d H:i:s') : null,
            'cancelled_at' => $this->cancelled_at ? $this->cancelled_at->format('Y-m-d H:i:s') : null,

            // Totals
            'total_items' => (float) $this->total_items,
            'total_quantity' => (float) $this->total_quantity,
            'total_value' => (float) $this->total_value,

            // Items (nested resource)
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),

            // Status flags
            'can_be_approved' => $this->canBeApproved(),
            'can_be_rejected' => $this->canBeRejected(),
            'can_be_shipped' => $this->canBeShipped(),
            'can_be_received' => $this->canBeReceived(),
            'can_be_cancelled' => $this->canBeCancelled(),

            // Metadata
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'status' => 'success',
                'version' => '1.0.0',
            ],
        ];
    }
}
