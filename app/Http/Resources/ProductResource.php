<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'productName' => $this->productName,
            'productCode' => $this->productCode,
            'sales_price' => $this->sales_price,
            'purchase_without_tax' => $this->purchase_without_tax,
            'purchase_with_tax' => $this->purchase_with_tax,
            'alert_qty' => $this->alert_qty,
            'wholesale_price' => $this->wholesale_price,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'manufacturer_id' => $this->manufacturer_id,
        ];
    }
}
