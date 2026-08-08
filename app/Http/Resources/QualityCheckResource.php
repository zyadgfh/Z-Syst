<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QualityCheckResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grn_item_id' => $this->grn_item_id,
            'checker_id' => $this->checker_id,
            'checker' => $this->whenLoaded('checker', function () {
                return [
                    'id' => $this->checker->id,
                    'name' => $this->checker->name,
                ];
            }),
            'check_date' => $this->check_date->format('Y-m-d'),
            'quality_status' => $this->quality_status,
            'defects' => $this->defects,
            'damage_quantity' => $this->damage_quantity,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'notes' => $this->notes,
            'photos' => $this->photos,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
