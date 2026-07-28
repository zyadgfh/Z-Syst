<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReturn;
use App\Models\ProductStock;
use App\Services\Stock\StockAllocationService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

final class PurchaseOrderReturnService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function create(array $data, array $items, int $companyId, int $userId): PurchaseOrderReturn
    {
        return $this->db->transaction(function () use ($data, $items, $companyId, $userId) {
            $purchaseOrder = PurchaseOrder::forCompany($companyId)
                ->with('items')
                ->findOrFail($data['purchase_order_id']);

            $return = PurchaseOrderReturn::create([
                'company_id' => $companyId,
                'purchase_order_id' => $purchaseOrder->id,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'return_number' => 'POR-'.strtoupper(Str::random(8)),
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($items as $item) {
                $return->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity_returned' => $item['quantity_returned'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => $item['quantity_returned'] * $item['unit_cost'],
                    'batch_number' => $item['batch_number'] ?? null,
                ]);

                $purchaseOrder->items()
                    ->where('product_id', $item['product_id'])
                    ->decrement('quantity_received', $item['quantity_returned']);

                // Deduct stock using StockAllocationService with pessimistic locking
                StockAllocationService::allocateToProductStock(
                    $item['product_id'],
                    (int) $item['quantity_returned'],
                    $data['branch_id']
                );
            }

            return $return->load(['purchaseOrder', 'items.product', 'supplier', 'branch', 'createdBy']);
        });
    }

    /**
     * Update an existing purchase order return.
     * Only allowed when the return is in draft status.
     */
    public function update(PurchaseOrderReturn $return, array $data, array $items): PurchaseOrderReturn
    {
        return $this->db->transaction(function () use ($return, $data, $items) {
            $return->update([
                'notes' => $data['notes'] ?? $return->notes,
            ]);

            if (! empty($items)) {
                $return->items()->delete();

                foreach ($items as $item) {
                    $return->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity_returned' => $item['quantity_returned'],
                        'unit_cost' => $item['unit_cost'],
                        'total' => $item['quantity_returned'] * $item['unit_cost'],
                        'batch_number' => $item['batch_number'] ?? null,
                    ]);
                }
            }

            return $return->load(['purchaseOrder', 'items.product', 'supplier', 'branch', 'createdBy']);
        });
    }

    /**
     * Delete a purchase order return.
     * Restores the quantity_received on the purchase order items
     * and releases the stock back to inventory.
     */
    public function destroy(PurchaseOrderReturn $return): void
    {
        $this->db->transaction(function () use ($return) {
            $purchaseOrder = $return->purchaseOrder;

            if ($purchaseOrder) {
                foreach ($return->items as $item) {
                    $purchaseOrder->items()
                        ->where('product_id', $item->product_id)
                        ->increment('quantity_received', $item->quantity_returned);

                    // Release stock back to inventory
                    StockAllocationService::releaseFromProductStock(
                        $item->product_id,
                        (int) $item->quantity_returned,
                        $return->branch_id
                    );
                }
            }

            $return->items()->delete();
            $return->delete();
        });
    }
}
