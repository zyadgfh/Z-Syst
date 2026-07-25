<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductStock;
use App\Services\Stock\StockAllocationService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

                // Deduct stock using StockAllocationService with pessimistic locking
                $branchId = $payload['branch_id'] ?? null;
                StockAllocationService::allocateToProductStock(
                    $product->id,
                    $item['quantity'],
                    $branchId
                );

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
        $prefix = $branchId ? 'B'.str_pad($branchId, 3, '0', STR_PAD_LEFT).'-' : '';

        return $prefix.strtoupper(Str::random(10));
    }
}
