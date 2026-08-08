<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BarcodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'barcode_number' => $this->barcode_number,
            'barcode_type' => $this->barcode_type,
            'barcode_image' => $this->barcode_image ? Storage::url($this->barcode_image) : null,
            'print_status' => $this->print_status,
            'printed_at' => $this->printed_at?->toIso8601String(),
            'printed_by' => $this->printed_by,
            'print_count' => $this->print_count,
            'size' => $this->size,
            'print_settings' => $this->print_settings,
            'is_active' => $this->is_active,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Relationships
            'product' => new ProductResource($this->whenLoaded('product')),
            'batch' => new StockResource($this->whenLoaded('batch')),
        ];
    }
}
