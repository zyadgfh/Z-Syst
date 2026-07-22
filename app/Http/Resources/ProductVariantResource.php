<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_name' => $this->variant_name,
            'barcode' => $this->barcode,
            'package_size' => $this->package_size,
            'unit_quantity' => (float) $this->unit_quantity,
            'unit_of_measure' => $this->unit_of_measure,
            'purchase_price' => (float) $this->purchase_price,
            'sales_price' => (float) $this->sales_price,
            'wholesale_price' => (float) $this->wholesale_price,
            'sku' => $this->sku,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
        ];
    }
}

