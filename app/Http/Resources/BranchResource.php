<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Branch */
class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'company_id' => $this->company_id,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'phone' => $this->phone,
            'email' => $this->email,
            'manager_name' => $this->manager_name,
            'opening_time' => $this->opening_time,
            'closing_time' => $this->closing_time,
            'is_active' => (bool) $this->is_active,
            'is_main' => (bool) $this->is_main,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            $this->mergeWhen($this->relationLoaded('company'), [
                'company' => new CompanyResource($this->whenLoaded('company')),
            ]),
        ];
    }
}
