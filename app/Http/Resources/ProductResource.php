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
            'product_name' => $this->product_name,
            'product_code' => $this->product_code,
            // Legacy aliases: the bundled Flutter app still reads the camelCase
            // keys on some screens (expiry alerts, FEFO, auto-order). Drop these
            // once the mobile build ships snake_case only.
            'productName' => $this->product_name,
            'productCode' => $this->product_code,
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
