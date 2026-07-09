<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * StockTransferItemResource
 *
 * API resource for transforming StockTransferItem models to JSON responses.
 * Follows the JSON:API specification for consistent API responses.
 */
class StockTransferItemResource extends JsonResource
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

            // Product information
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product->name ?? null,
                'sku' => $this->product->sku ?? null,
                'category' => $this->product->category->name ?? null,
            ],

            // Stock information
            'product_stock_id' => $this->product_stock_id,
            'batch_number' => $this->batch_number,
            'expiry_date' => $this->expiry_date ? $this->expiry_date->format('Y-m-d') : null,

            // Quantities
            'quantity_requested' => (float) $this->quantity_requested,
            'quantity_sent' => (float) $this->quantity_sent,
            'quantity_received' => (float) $this->quantity_received,
            'remaining_to_send' => (float) $this->remaining_to_send,
            'remaining_to_receive' => (float) $this->remaining_to_receive,

            // Costs
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->total_cost,

            // Status flags
            'is_fully_sent' => $this->isFullySent(),
            'is_fully_received' => $this->isFullyReceived(),
            'has_discrepancy' => $this->hasDiscrepancy(),

            // Notes
            'notes' => $this->notes,

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
