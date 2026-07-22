<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sale API Resource
 *
 * Formats the Sale model for JSON API responses.
 * Includes items with product details, totals, and receipt URL.
 */
class SaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'customer_name' => $this->customer_name ?? 'نقدي',
            'customer_phone' => $this->customer_phone,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) ($this->discount_amount ?? 0),
            'tax_amount' => (float) ($this->tax_amount ?? 0),
            'total_amount' => (float) $this->total_amount,
            'amount_paid' => (float) ($this->amount_paid ?? 0),
            'change_amount' => (float) ($this->change_amount ?? 0),
            'payment_method' => $this->payment_method ?? 'cash',
            'status' => $this->status,
            'notes' => $this->notes,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? $item->product?->product_name ?? 'منتج',
                'barcode' => $item->product?->barcode,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount' => (float) ($item->discount ?? 0),
                'tax' => (float) ($item->tax ?? 0),
                'line_total' => (float) $item->line_total,
            ])),
            'created_by' => $this->whenLoaded('createdBy', fn () => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),
            'receipt_url' => url("/api/v1/sales/{$this->id}/receipt"),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

