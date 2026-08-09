<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'company_name' => $this->company_name,
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'tax_id' => $this->tax_id,
            'license_number' => $this->license_number,
            'rating' => $this->rating,
            'average_rating' => $this->average_rating,
            'performance_score' => $this->performance_score,
            'payment_terms' => $this->payment_terms,
            'credit_limit' => $this->credit_limit,
            'contract_start' => $this->contract_start?->format('Y-m-d'),
            'contract_end' => $this->contract_end?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'notes' => $this->notes,
            'ratings_count' => $this->whenLoaded('ratings', fn () => $this->ratings->count()),
            'contracts_count' => $this->whenLoaded('contracts', fn () => $this->contracts->count()),
            'performance' => $this->whenLoaded('performance', fn () => $this->performance),
            'purchase_orders_count' => $this->whenLoaded('purchaseOrders', fn () => $this->purchaseOrders->count()),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
