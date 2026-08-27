<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Create a new purchase order.
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $count = PurchaseOrder::where('business_id', $data['business_id'])->count() + 1;
            $po_number = 'PO-' . date('Y') . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);

            $po = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_id' => $data['business_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'po_number' => $po_number,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'priority' => $data['priority'] ?? PurchaseOrder::PRIORITY_NORMAL,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'terms' => $data['terms'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Add items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addItem($po, $item);
                }
            }

            $po->calculateTotal();

            return $po;
        });
    }

    /**
     * Add item to purchase order.
     */
    public function addItem(PurchaseOrder $po, array $itemData): PurchaseOrderItem
    {
        $product = Product::findOrFail($itemData['product_id']);

        $unitPrice = $itemData['unit_price'] ?? $product->purchase_without_tax ?? 0;
        $quantity = $itemData['quantity'];
        $discount = $itemData['discount'] ?? 0;
        $tax = $itemData['tax'] ?? 0;
        $total = ($unitPrice * $quantity) - $discount + $tax;

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'received_quantity' => 0,
            'pending_quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'notes' => $itemData['notes'] ?? null,
        ]);

        $po->calculateTotal();

        return $poItem->refresh();
    }

    /**
     * Update purchase order.
     */
    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            $po->update([
                'supplier_id' => $data['supplier_id'] ?? $po->supplier_id,
                'priority' => $data['priority'] ?? $po->priority,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $po->expected_delivery_date,
                'terms' => $data['terms'] ?? $po->terms,
                'internal_notes' => $data['internal_notes'] ?? $po->internal_notes,
                'notes' => $data['notes'] ?? $po->notes,
            ]);

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $po->items()->delete();
                foreach ($data['items'] as $item) {
                    $this->addItem($po, $item);
                }
            }

            $po->calculateTotal();

            return $po;
        });
    }

    /**
     * Send purchase order to supplier.
     */
    public function send(PurchaseOrder $po): PurchaseOrder
    {
        if (! $po->isDraft()) {
            throw new \Exception('Only draft orders can be sent');
        }

        $po->markAsSent();

        // TODO: Send notification to supplier
        // TODO: Email supplier with PO details

        return $po;
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! $po->isSent()) {
            throw new \Exception('Only sent orders can be approved');
        }

        $po->approve($userId);

        // TODO: Notify supplier
        // TODO: Update purchase order status

        return $po;
    }

    /**
     * Reject purchase order.
     */
    public function reject(PurchaseOrder $po, int $userId, string $reason): PurchaseOrder
    {
        if (! $po->isSent()) {
            throw new \Exception('Only sent orders can be rejected');
        }

        $po->reject($userId, $reason);

        // TODO: Notify supplier
        // TODO: Update purchase order status

        return $po;
    }

    /**
     * Cancel purchase order.
     */
    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if ($po->isReceived() || $po->isPartiallyReceived()) {
            throw new \Exception('Cannot cancel received orders');
        }

        $po->cancel();

        // TODO: Notify supplier
        // TODO: Update inventory if needed

        return $po;
    }

    /**
     * Restore cancelled purchase order.
     */
    public function restore(PurchaseOrder $po): PurchaseOrder
    {
        if (! $po->isCancelled()) {
            throw new \Exception('Only cancelled orders can be restored');
        }

        $po->update(['status' => PurchaseOrder::STATUS_DRAFT]);

        // TODO: Notify supplier

        return $po;
    }

    /**
     * Convert PO to Purchase.
     */
    public function convertToPurchase(PurchaseOrder $po): Purchase
    {
        if (! $po->isApproved()) {
            throw new \Exception('Only approved orders can be converted to purchases');
        }

        return DB::transaction(function () use ($po) {
            $purchase = Purchase::create([
                'party_id' => $po->supplier_id,
                'business_id' => $po->business_id,
                'branch_id' => $po->branch_id,
                'user_id' => $po->created_by,
                'tax_id' => null,
                'discountAmount' => $po->discount_amount,
                'tax_amount' => $po->tax_amount,
                'dueAmount' => $po->total_amount,
                'paidAmount' => 0,
                'totalAmount' => $po->total_amount,
                'isPaid' => false,
                'paymentType' => 'Cash',
                'purchaseDate' => now(),
                'purchase_data' => json_encode([
                    'po_id' => $po->id,
                    'po_number' => $po->po_number,
                ]),
                'note' => "Created from PO: {$po->po_number}",
            ]);

            // Add purchase details
            foreach ($po->items as $poItem) {
                PurchaseDetails::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $poItem->product_id,
                    'purchase_without_tax' => $poItem->unit_price,
                    'purchase_with_tax' => $poItem->unit_price + $poItem->tax,
                    'profit_percent' => 0,
                    'sales_price' => $poItem->product->sales_price ?? 0,
                    'wholesale_price' => $poItem->product->wholesale_price ?? 0,
                    'quantities' => $poItem->quantity,
                    'batch_no' => null,
                    'expire_date' => null,
                ]);
            }

            // Update PO status
            $po->update(['status' => PurchaseOrder::STATUS_RECEIVED]);

            return $purchase;
        });
    }

    /**
     * Get purchase orders by business.
     */
    public function getByBusiness(int $businessId)
    {
        return PurchaseOrder::forBusiness($businessId)
            ->with(['supplier', 'items.product', 'createdBy'])
            ->latest()
            ->get();
    }

    /**
     * Get purchase orders by supplier.
     */
    public function getBySupplier(int $supplierId, int $businessId)
    {
        return PurchaseOrder::forBusiness($businessId)
            ->forSupplier($supplierId)
            ->with(['items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get pending purchase orders.
     */
    public function getPending(int $businessId)
    {
        return PurchaseOrder::forBusiness($businessId)
            ->pending()
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get overdue purchase orders.
     */
    public function getOverdue(int $businessId)
    {
        return PurchaseOrder::forBusiness($businessId)
            ->overdue()
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get purchase order statistics.
     */
    public function getStatistics(int $businessId): array
    {
        $total = PurchaseOrder::forBusiness($businessId)->count();
        $draft = PurchaseOrder::forBusiness($businessId)->draft()->count();
        $pending = PurchaseOrder::forBusiness($businessId)->pending()->count();
        $approved = PurchaseOrder::forBusiness($businessId)->approved()->count();
        $overdue = PurchaseOrder::forBusiness($businessId)->overdue()->count();

        return [
            'total' => $total,
            'draft' => $draft,
            'pending' => $pending,
            'approved' => $approved,
            'overdue' => $overdue,
        ];
    }

    /**
     * Delete purchase order.
     */
    public function delete(PurchaseOrder $po): bool
    {
        if (! $po->isDraft()) {
            throw new \Exception('Only draft orders can be deleted');
        }

        return $po->delete();
    }
}
