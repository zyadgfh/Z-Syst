<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Stock\StockAllocationService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class SaleService
{
    protected $db;

    public function __construct(
        DatabaseManager $db,
        private readonly StockBatchService $stockBatchService,
        private readonly StockMovementService $stockMovementService,
    ) {
        $this->db = $db;
    }

    /**
     * Create a new sale with FEFO batch allocation.
     * Automatically allocates stock from the nearest-expiring batches first.
     */
    public function createSale(array $payload): Sale
    {
        return $this->db->transaction(function () use ($payload) {
            $companyId = $payload['company_id'] ?? app('tenant.company_id');
            $branchId = $payload['branch_id'] ?? throw new \InvalidArgumentException('branch_id is required for sale');

            $invoice = $this->generateInvoiceNumber($branchId);

            $sale = Sale::create(array_merge($payload, [
                'invoice_number' => $invoice,
                'user_id' => Auth::id(),
                'company_id' => $companyId,
            ]));

            $subtotal = 0;

            foreach ($payload['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Use FEFO allocation if no specific batch is requested
                if (empty($item['batch_number']) && $product->track_inventory) {
                    $allocations = $this->stockBatchService->allocateFefo(
                        productId: $product->id,
                        branchId: $branchId,
                        quantityNeeded: (float) $item['quantity']
                    );

                    // Create sale items for each batch allocation
                    foreach ($allocations as $allocation) {
                        $lineTotal = round(
                            ($allocation['unit_price'] * $allocation['quantity'])
                            - ($item['discount'] ?? 0)
                            + ($item['tax'] ?? 0),
                            2
                        );

                        SaleItem::create([
                            'sale_id' => $sale->id,
                            'product_id' => $product->id,
                            'batch_number' => $allocation['batch_number'],
                            'inventory_id' => $allocation['inventory_id'],
                            'quantity' => $allocation['quantity'],
                            'unit_price' => $allocation['unit_price'],
                            'cost_price' => $allocation['cost_price'],
                            'discount' => $item['discount'] ?? 0,
                            'tax' => $item['tax'] ?? 0,
                            'total' => $lineTotal,
                        ]);

                        // Deduct stock with pessimistic locking
                        StockAllocationService::allocateToProductStock(
                            $product->id,
                            (int) $allocation['quantity'],
                            $branchId,
                            $allocation['inventory_id']
                        );

                        // Log stock movement via new StockMovementService
                        $this->stockMovementService->logMovement(
                            productId: $product->id,
                            branchId: $branchId,
                            movementType: 'out',
                            quantity: $allocation['quantity'],
                            referenceType: 'sale',
                            referenceId: $sale->id,
                            batchNumber: $allocation['batch_number'],
                            extra: [
                                'inventory_id' => $allocation['inventory_id'],
                                'unit_price' => $allocation['unit_price'],
                                'cost_price' => $allocation['cost_price'],
                            ]
                        );

                        $subtotal += $lineTotal;
                    }
                } else {
                    // Legacy mode: direct deduction without FEFO (backward compatibility)
                    $batchNumber = $item['batch_number'] ?? null;
                    $unitPrice = $item['unit_price'] ?? $product->sales_price;
                    $lineTotal = round(
                        ($unitPrice * $item['quantity'])
                        - ($item['discount'] ?? 0)
                        + ($item['tax'] ?? 0),
                        2
                    );

                    $saleItem = SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'batch_number' => $batchNumber,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'discount' => $item['discount'] ?? 0,
                        'tax' => $item['tax'] ?? 0,
                        'total' => $lineTotal,
                    ]);

                    // Deduct stock using legacy method
                    StockAllocationService::allocateToProductStock(
                        $product->id,
                        (int) $item['quantity'],
                        $branchId
                    );

                    $this->stockMovementService->logMovement(
                        productId: $product->id,
                        branchId: $branchId,
                        movementType: 'out',
                        quantity: $item['quantity'],
                        referenceType: 'sale',
                        referenceId: $sale->id,
                        batchNumber: $batchNumber,
                        extra: ['legacy_mode' => true]
                    );

                    $subtotal += $lineTotal;
                }
            }

            $sale->subtotal = $subtotal;
            $sale->total_amount = $subtotal - ($payload['discount_amount'] ?? 0) + ($payload['tax_amount'] ?? 0);
            $sale->amount_paid = $payload['amount_paid'] ?? 0;
            $sale->change_amount = max(0, ($sale->amount_paid - $sale->total_amount));
            $sale->save();

            return $sale->load('items.product');
        });
    }

    /**
     * Reverse a sale (full return) - restore stock to original batches.
     */
    public function reverseSale(Sale $sale): Sale
    {
        return $this->db->transaction(function () use ($sale) {
            foreach ($sale->items as $item) {
                // Restore stock via StockAllocationService
                StockAllocationService::releaseFromProductStock(
                    $item->product_id,
                    (int) $item->quantity,
                    $sale->branch_id
                );

                // Log restoration
                $this->stockMovementService->logMovement(
                    productId: $item->product_id,
                    branchId: $sale->branch_id,
                    movementType: 'in',
                    quantity: $item->quantity,
                    referenceType: 'sale_reversal',
                    referenceId: $sale->id,
                    batchNumber: $item->batch_number,
                    extra: ['restored_from_sale' => $sale->id]
                );
            }

            $sale->update(['status' => 'cancelled']);

            return $sale->fresh();
        });
    }

    protected function generateInvoiceNumber($branchId = null): string
    {
        $prefix = $branchId ? 'B'.str_pad($branchId, 3, '0', STR_PAD_LEFT).'-' : '';

        return $prefix.strtoupper(Str::random(10));
    }
}

