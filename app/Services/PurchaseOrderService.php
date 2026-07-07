<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
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

    private function generatePoNumber(): string
    {
        return 'PO-' . strtoupper(Str::random(8));
    }

    private function calculateTotals(Collection|array $items): array
    {
        $items = collect($items);

        $subtotal = $items->sum(fn(array $item): float => $item['quantity_ordered'] * $item['unit_cost']);
        $discount = $items->sum(fn(array $item): float => $item['discount'] ?? 0);
        $tax = $items->sum(fn(array $item): float => $item['tax'] ?? 0);

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