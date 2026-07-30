<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InsuranceCoverageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'insurance_company_id' => $this->insurance_company_id,
            'product_id' => $this->product_id,
            'category_id' => $this->category_id,
            'coverage_code' => $this->coverage_code,
            'scope' => $this->scope,
            'coverage_percent' => $this->coverage_percent,
            'copay_percent' => $this->copay_percent,
            'max_amount_per_claim' => $this->max_amount_per_claim,
            'max_amount_per_year' => $this->max_amount_per_year,
            'requires_preauthorization' => $this->requires_preauthorization,
            'is_active' => $this->is_active,
            'is_effective' => $this->isEffective(),
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'notes' => $this->notes,
            'company' => $this->whenLoaded('insuranceCompany', function () {
                return [
                    'id' => $this->insuranceCompany->id,
                    'name' => $this->insuranceCompany->name,
                ];
            }),
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name ?? $this->product->product_name ?? null,
                ];
            }),
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name ?? $this->category->category_name ?? null,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
