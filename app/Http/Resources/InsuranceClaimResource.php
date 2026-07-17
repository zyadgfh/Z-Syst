<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceClaimResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'claim_number' => $this->claim_number,
            'insurance_company_id' => $this->insurance_company_id,
            'insuranceCompany' => new InsuranceCompanyResource($this->whenLoaded('insuranceCompany')),
            'patient_id' => $this->patient_id,
            'patient' => new PatientResource($this->whenLoaded('patient')),
            'sale_id' => $this->sale_id,
            'sale' => $this->whenLoaded('sale', fn() => $this->sale->only(['id', 'invoice_number', 'total_amount'])),
            'amount_claimed' => $this->amount_claimed,
            'amount_approved' => $this->amount_approved,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}