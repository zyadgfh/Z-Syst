<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InsurancePlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'insurance_company_id' => $this->insurance_company_id,
            'name' => $this->name,
            'coverage_percentage' => $this->coverage_percentage,
            'deductible_amount' => $this->deductible_amount,
            'max_coverage_amount' => $this->max_coverage_amount,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}