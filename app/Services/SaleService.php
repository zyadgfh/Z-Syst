<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Database\DatabaseManager;

class SaleService
{
    protected $db;

    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
    }

    public function createSale(array $payload): Sale
    {
        return $this->db->transaction(function () use ($payload) {
            $invoice = $this->generateInvoiceNumber($payload['branch_id'] ?? null);

            $sale = Sale::create(array_merge($payload, [
                'invoice_number' => $invoice,
                'user_id' => Auth::id(),
            ]));

            $subtotal = 0;

            foreach ($payload['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                $lineTotal = round(($item['unit_price'] * $item['quantity']) - ($item['discount'] ?? 0) + ($item['tax'] ?? 0), 2);

                $saleItem = SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'batch_number' => $item['batch_number'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0,
                    'tax' => $item['tax'] ?? 0,
                    'total' => $lineTotal,
                ]);

                // Deduct stock from product stocks (simple FIFO: reduce earliest stock first)
                $this->deductStock($product, $item['quantity']);

                $subtotal += $lineTotal;
            }

            $sale->subtotal = $subtotal;
            $sale->total_amount = $subtotal - ($payload['discount_amount'] ?? 0) + ($payload['tax_amount'] ?? 0);
            $sale->amount_paid = $payload['amount_paid'] ?? 0;
            $sale->change_amount = max(0, ($sale->amount_paid - $sale->total_amount));
            $sale->save();

            return $sale->load('items.product');
        });
    }

    protected function generateInvoiceNumber($branchId = null): string
    {
        $prefix = $branchId ? 'B' . str_pad($branchId, 3, '0', STR_PAD_LEFT) . '-' : '';
        return $prefix . strtoupper(Str::random(10));
    }

    protected function deductStock(Product $product, int $qty)
    {
        $remaining = $qty;

        $stocks = $product->stocks()->where('quantity', '>', 0)->orderBy('created_at')->get();

        foreach ($stocks as $stock) {
            if ($remaining <= 0) break;

            $take = min($stock->quantity, $remaining);
            $stock->quantity -= $take;
            $stock->save();

            $remaining -= $take;
        }

        if ($remaining > 0) {
            // negative stock allowed? For now, throw
            throw new \RuntimeException('Insufficient stock for product ID: ' . $product->id);
        }
    }
}
