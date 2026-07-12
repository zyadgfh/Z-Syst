<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LegacyProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productName' => $this->productName,
            'productCode' => $this->productCode,
            'purchase_without_tax' => $this->purchase_without_tax,
            'purchase_with_tax' => $this->purchase_with_tax,
            'profit_percent' => $this->profit_percent,
            'sales_price' => $this->sales_price,
            'wholesale_price' => $this->wholesale_price,
            'alert_qty' => $this->alert_qty,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'type_id' => $this->type_id,
            'manufacturer_id' => $this->manufacturer_id,
            'box_size_id' => $this->box_size_id,
            'tax_id' => $this->tax_id,
            'tax_type' => $this->tax_type,
            'images' => $this->images,
            'meta' => $this->meta,
            'stocks_sum_productStock' => $this->stocks_sum_productStock ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'category' => $this->whenLoaded('category'),
            'unit' => $this->whenLoaded('unit'),
            'medicine_type' => $this->whenLoaded('medicine_type'),
            'manufacturer' => $this->whenLoaded('manufacterer'),
            'box_size' => $this->whenLoaded('box_size'),
            'stocks' => $this->whenLoaded('stocks'),
            'tax' => $this->whenLoaded('tax'),
        ];
    }
}