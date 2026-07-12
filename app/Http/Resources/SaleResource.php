<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'party_id' => $this->party_id,
            'user_id' => $this->user_id,
            'tax_id' => $this->tax_id,
            'discountAmount' => $this->discountAmount,
            'dueAmount' => $this->dueAmount,
            'isPaid' => $this->isPaid,
            'tax_amount' => $this->tax_amount,
            'paidAmount' => $this->paidAmount,
            'totalAmount' => $this->totalAmount,
            'lossProfit' => $this->lossProfit,
            'paymentType' => $this->paymentType,
            'invoiceNumber' => $this->invoiceNumber,
            'saleDate' => $this->saleDate,
            'sale_data' => $this->sale_data,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'party' => $this->whenLoaded('party'),
            'user' => $this->whenLoaded('user'),
            'tax' => $this->whenLoaded('tax'),
            'details' => $this->whenLoaded('details'),
        ];
    }
}