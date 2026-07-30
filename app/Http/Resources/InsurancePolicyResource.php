<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePolicyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'insurance_company_id' => $this->insurance_company_id,
            'customer_id' => $this->customer_id,
            'policy_number' => $this->policy_number,
            'member_id' => $this->member_id,
            'card_number' => $this->card_number,
            'holder_name' => $this->holder_name,
            'holder_dob' => $this->holder_dob?->format('Y-m-d'),
            'holder_gender' => $this->holder_gender,
            'holder_phone' => $this->holder_phone,
            'holder_email' => $this->holder_email,
            'holder_address' => $this->holder_address,
            'plan_type' => $this->plan_type,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'annual_limit' => $this->annual_limit,
            'used_amount' => $this->used_amount,
            'remaining_limit' => $this->remaining_limit,
            'coverage_percent' => $this->coverage_percent,
            'copay_percent' => $this->copay_percent,
            'is_valid' => $this->isValid(),
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'company' => $this->whenLoaded('insuranceCompany', function () {
                return [
                    'id' => $this->insuranceCompany->id,
                    'name' => $this->insuranceCompany->name,
                    'code' => $this->insuranceCompany->code,
                ];
            }),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name ?? $this->customer->party_name ?? null,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
