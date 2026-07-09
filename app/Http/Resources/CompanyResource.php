<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Company */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'tax_number' => $this->tax_number,
            'registration_number' => $this->registration_number,
            'logo' => $this->logo,
            'logo_url' => $this->logo ? url("storage/{$this->logo}") : null,
            'website' => $this->website,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'is_active' => (bool) $this->is_active,
            'max_branches' => $this->max_branches,
            'is_unlimited_branches' => (bool) $this->is_unlimited_branches,
            'current_branches_count' => $this->current_branches_count,
            'remaining_branches' => $this->remaining_branches,
            'branch_usage_percentage' => $this->branch_usage_percentage,
            'is_near_limit' => $this->is_near_limit,
            'is_at_limit' => $this->is_at_limit,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
