<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'claim_number' => $this->claim_number,
            'insurance_company_id' => $this->insurance_company_id,
            'insurance_policy_id' => $this->insurance_policy_id,
            'sale_id' => $this->sale_id,
            'prescription_id' => $this->prescription_id,
            'customer_id' => $this->customer_id,
            'user_id' => $this->user_id,
            'service_date' => $this->service_date?->format('Y-m-d'),
            'submission_date' => $this->submission_date?->format('Y-m-d'),
            'settlement_date' => $this->settlement_date?->format('Y-m-d'),
            'total_amount' => $this->total_amount,
            'covered_amount' => $this->covered_amount,
            'patient_responsibility' => $this->patient_responsibility,
            'approved_amount' => $this->approved_amount,
            'paid_amount' => $this->paid_amount,
            'rejected_amount' => $this->rejected_amount,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'external_reference' => $this->external_reference,
            'notes' => $this->notes,
            'line_items' => $this->line_items,
            'metadata' => $this->metadata,
            'company' => $this->whenLoaded('insuranceCompany', function () {
                return [
                    'id' => $this->insuranceCompany->id,
                    'name' => $this->insuranceCompany->name,
                ];
            }),
            'policy' => $this->whenLoaded('insurancePolicy', function () {
                return [
                    'id' => $this->insurancePolicy->id,
                    'policy_number' => $this->insurancePolicy->policy_number,
                    'holder_name' => $this->insurancePolicy->holder_name,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
