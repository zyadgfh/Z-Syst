<?php

namespace App\Http\Resources;

use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Drug */
class DrugResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'generic_name' => $this->generic_name,
            'barcode' => $this->barcode,
            'manufacturer' => $this->manufacturer,
            'form' => $this->form,
            'strength' => $this->strength,
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
