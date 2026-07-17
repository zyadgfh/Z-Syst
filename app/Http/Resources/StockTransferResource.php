<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'from_branch_id' => $this->from_branch_id,
            'fromBranch' => $this->whenLoaded('fromBranch', fn() => $this->fromBranch->only(['id', 'name'])),
            'to_branch_id' => $this->to_branch_id,
            'toBranch' => $this->whenLoaded('toBranch', fn() => $this->toBranch->only(['id', 'name'])),
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'requested_by' => $this->requested_by,
            'requestedBy' => $this->whenLoaded('requestedBy', fn() => $this->requestedBy->only(['id', 'name'])),
            'approved_by' => $this->approved_by,
            'approvedBy' => $this->whenLoaded('approvedBy', fn() => $this->approvedBy->only(['id', 'name'])),
            'shipped_by' => $this->shipped_by,
            'shippedBy' => $this->whenLoaded('shippedBy', fn() => $this->shippedBy->only(['id', 'name'])),
            'received_by' => $this->received_by,
            'receivedBy' => $this->whenLoaded('receivedBy', fn() => $this->receivedBy->only(['id', 'name'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}