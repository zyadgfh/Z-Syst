<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StockAuditResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'audit_number' => $this->audit_number,
            'audit_type' => $this->audit_type,
            'status' => $this->status,
            'audit_date' => $this->audit_date ? $this->audit_date->format('Y-m-d H:i:s') : null,
            'completed_at' => $this->completed_at ? $this->completed_at->format('Y-m-d H:i:s') : null,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'business_id' => $this->business_id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ];
            }),
            'details_count' => $this->whenCounted('details'),
            'details' => StockAuditDetailResource::collection($this->whenLoaded('details')),
            'reconciliations_count' => $this->whenCounted('reconciliations'),
            'reconciliations' => StockReconciliationResource::collection($this->whenLoaded('reconciliations')),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}