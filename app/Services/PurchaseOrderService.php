<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class PurchaseOrderService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function create(array $data, array $items, int $companyId, int $userId): PurchaseOrder
    {
        return $this->db->transaction(function () use ($data, $items, $companyId, $userId) {
            $totals = $this->calculateTotals($items);

            $purchaseOrder = PurchaseOrder::create([
                'company_id' => $companyId,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'uuid' => (string) Str::uuid(),
                'po_number' => $this->generatePoNumber(),
                'status' => 'draft',
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->createItems($purchaseOrder, $items);

            return $purchaseOrder->load(['supplier', 'branch', 'items.product']);
        });
    }

    public function approve(PurchaseOrder $purchaseOrder, int $userId): PurchaseOrder
    {
        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => $userId,
        ]);

        return $purchaseOrder->fresh();
    }

    public function send(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrder->update(['status' => 'sent']);

        return $purchaseOrder->fresh();
    }

    public function cancel(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $purchaseOrder->update(['status' => 'cancelled']);

        return $purchaseOrder->fresh();
    }

    /**
     * Update an existing purchase order with new items.
     * Only allowed when the PO is in draft or pending status.
     */
    public function update(PurchaseOrder $purchaseOrder, array $data, array $items): PurchaseOrder
    {
        return $this->db->transaction(function () use ($purchaseOrder, $data, $items) {
            $purchaseOrder->update([
                'supplier_id' => $data['supplier_id'] ?? $purchaseOrder->supplier_id,
                'branch_id' => $data['branch_id'] ?? $purchaseOrder->branch_id,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $purchaseOrder->expected_delivery_date,
                'notes' => $data['notes'] ?? $purchaseOrder->notes,
            ]);

            if (! empty($items)) {
                $purchaseOrder->items()->delete();

                $totals = $this->calculateTotals($items);
                $purchaseOrder->update([
                    'subtotal' => $totals['subtotal'],
                    'discount' => $totals['discount'],
                    'tax' => $totals['tax'],
                    'total' => $totals['total'],
                ]);

                $this->createItems($purchaseOrder, $items);
            }

            return $purchaseOrder->load(['supplier', 'branch', 'items.product']);
        });
    }

    /**
     * Mark a purchase order as partially or fully received.
     * Updates the status based on whether all items have been received.
     */
    public function receive(PurchaseOrder $purchaseOrder): PurchaseOrder
    {
        $hasPendingItems = $purchaseOrder->items()
            ->whereColumn('quantity_received', '<', 'quantity_ordered')
            ->exists();

        $purchaseOrder->update([
            'status' => $hasPendingItems ? 'partial' : 'received',
        ]);

        return $purchaseOrder->fresh();
    }

    /**
     * Duplicate a purchase order to create a new draft copy.
     * Useful for reordering from the same supplier.
     */
    public function duplicate(PurchaseOrder $purchaseOrder, int $userId): PurchaseOrder
    {
        return $this->db->transaction(function () use ($purchaseOrder, $userId) {
            $totals = $this->calculateTotals($purchaseOrder->items->toArray());

            $newPurchaseOrder = PurchaseOrder::create([
                'company_id' => $purchaseOrder->company_id,
                'supplier_id' => $purchaseOrder->supplier_id,
                'branch_id' => $purchaseOrder->branch_id,
                'uuid' => (string) Str::uuid(),
                'po_number' => $this->generatePoNumber(),
                'status' => 'draft',
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'tax' => $totals['tax'],
                'total' => $totals['total'],
                'expected_delivery_date' => $purchaseOrder->expected_delivery_date,
                'notes' => $purchaseOrder->notes,
                'created_by' => $userId,
            ]);

            foreach ($purchaseOrder->items as $item) {
                $newPurchaseOrder->items()->create([
                    'product_id' => $item->product_id,
                    'quantity_ordered' => $item->quantity_ordered,
                    'quantity_received' => 0,
                    'unit_cost' => $item->unit_cost,
                    'discount' => $item->discount,
                    'tax' => $item->tax,
                    'total' => $item->total,
                ]);
            }

            return $newPurchaseOrder->load(['supplier', 'branch', 'items.product']);
        });
    }

    private function generatePoNumber(): string
    {
        return 'PO-'.strtoupper(Str::random(8));
    }

    private function calculateTotals(Collection|array $items): array
    {
        $items = collect($items);

        $subtotal = $items->sum(fn (array $item): float => $item['quantity_ordered'] * $item['unit_cost']);
        $discount = $items->sum(fn (array $item): float => $item['discount'] ?? 0);
        $tax = $items->sum(fn (array $item): float => $item['tax'] ?? 0);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $subtotal - $discount + $tax,
        ];
    }

    private function createItems(PurchaseOrder $purchaseOrder, Collection|array $items): void
    {
        foreach ($items as $item) {
            $purchaseOrder->items()->create([
                'product_id' => $item['product_id'],
                'quantity_ordered' => $item['quantity_ordered'],
                'quantity_received' => 0,
                'unit_cost' => $item['unit_cost'],
                'discount' => $item['discount'] ?? 0,
                'tax' => $item['tax'] ?? 0,
                'total' => ($item['quantity_ordered'] * $item['unit_cost'])
                    - ($item['discount'] ?? 0)
                    + ($item['tax'] ?? 0),
            ]);
        }
    }
}
