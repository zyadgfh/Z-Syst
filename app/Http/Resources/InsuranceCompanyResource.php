<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceCompanyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'name' => $this->name,
            'code' => $this->code,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'tax_id' => $this->tax_id,
            'status' => $this->status,
            'integration_type' => $this->integration_type,
            'api_endpoint' => $this->api_endpoint,
            'default_coverage_percent' => $this->default_coverage_percent,
            'default_copay_percent' => $this->default_copay_percent,
            'settlement_days' => $this->settlement_days,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'policies_count' => $this->whenCounted('policies'),
            'claims_count' => $this->whenCounted('claims'),
            'policies' => InsurancePolicyResource::collection($this->whenLoaded('policies')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
