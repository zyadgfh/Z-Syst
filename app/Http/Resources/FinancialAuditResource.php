<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FinancialAuditResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'audit_number' => $this->audit_number,
            'audit_type' => $this->audit_type,
            'start_date' => $this->start_date->format('Y-m-d'),
            'end_date' => $this->end_date->format('Y-m-d'),
            'status' => $this->status,
            'opening_balance' => $this->opening_balance,
            'total_revenue' => $this->total_revenue,
            'total_expenses' => $this->total_expenses,
            'closing_balance' => $this->closing_balance,
            'variance' => $this->variance,
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
            'completed_at' => $this->completed_at ? $this->completed_at->format('Y-m-d H:i:s') : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
