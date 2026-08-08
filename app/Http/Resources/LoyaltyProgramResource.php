<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyProgramResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'points_per_currency' => $this->points_per_currency,
            'min_points_for_reward' => $this->min_points_for_reward,
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'transactions_count' => $this->whenCounted('transactions'),
            'transactions' => LoyaltyTransactionResource::collection($this->whenLoaded('transactions')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
