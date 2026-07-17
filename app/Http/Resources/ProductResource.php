<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'productName' => $this->productName,
            'productCode' => $this->productCode,
            'barcode' => $this->barcode,
            'generic_name' => $this->generic_name,
            'type_id' => $this->type_id,
            'medicine_type' => new MedicineTypeResource($this->whenLoaded('medicine_type')),
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'manufacturer_id' => $this->manufacturer_id,
            'manufacturer' => new ManufacturerResource($this->whenLoaded('manufacturer')),
            'unit_id' => $this->unit_id,
            'unit' => $this->whenLoaded('unit', fn() => $this->unit->only(['id', 'name'])),
            'purchase_without_tax' => $this->purchase_without_tax,
            'purchase_with_tax' => $this->purchase_with_tax,
            'profit_percent' => $this->profit_percent,
            'sales_price' => $this->sales_price,
            'wholesale_price' => $this->wholesale_price,
            'alert_qty' => $this->alert_qty,
            'box_size_id' => $this->box_size_id,
            'box_size' => $this->whenLoaded('box_size', fn() => $this->box_size->only(['id', 'name'])),
            'tax_id' => $this->tax_id,
            'tax' => new TaxResource($this->whenLoaded('tax')),
            'images' => $this->images,
            'meta' => $this->meta,
            'stock' => $this->whenLoaded('stocks', fn() => $this->stocks->sum('productStock')),
            'expiring_item' => $this->whenLoaded('expiring_item', fn() => $this->expiring_item?->only(['id', 'expire_date', 'productStock'])),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}