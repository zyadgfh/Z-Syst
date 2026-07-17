<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'dosage' => $this->dosage,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
            'quantity' => $this->quantity,
            'dispensed_quantity' => $this->dispensed_quantity,
            'instructions' => $this->instructions,
            'substitution_allowed' => $this->substitution_allowed,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}