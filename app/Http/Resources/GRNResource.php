<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GRNResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grn_number' => $this->grn_number,
            'purchase_order_id' => $this->purchase_order_id,
            'purchase_order' => $this->whenLoaded('purchaseOrder', function () {
                return [
                    'id' => $this->purchaseOrder->id,
                    'po_number' => $this->purchaseOrder->po_number,
                ];
            }),
            'supplier_id' => $this->supplier_id,
            'supplier' => $this->whenLoaded('supplier', function () {
                return [
                    'id' => $this->supplier->id,
                    'name' => $this->supplier->name,
                ];
            }),
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'received_by' => $this->received_by,
            'received_by_user' => $this->whenLoaded('receivedBy', function () {
                return [
                    'id' => $this->receivedBy->id,
                    'name' => $this->receivedBy->name,
                ];
            }),
            'verified_by' => $this->verified_by,
            'verified_by_user' => $this->whenLoaded('verifiedBy', function () {
                return [
                    'id' => $this->verifiedBy->id,
                    'name' => $this->verifiedBy->name,
                ];
            }),
            'received_date' => $this->received_date?->format('Y-m-d'),
            'location' => $this->location,
            'status' => $this->status,
            'notes' => $this->notes,
            'verified_at' => $this->verified_at?->format('Y-m-d H:i:s'),
            'total_received_quantity' => $this->total_received_quantity,
            'total_accepted_quantity' => $this->total_accepted_quantity,
            'total_rejected_quantity' => $this->total_rejected_quantity,
            'total_value' => $this->total_value,
            'completion_percentage' => $this->completion_percentage,
            'items_count' => $this->whenLoaded('items', fn() => $this->items->count()),
            'items' => GRNItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
