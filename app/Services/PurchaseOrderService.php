<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Product;
use App\Models\Party;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseOrderService
{
    /**
     * Notification dispatcher used to inform suppliers about PO lifecycle events.
     */
    protected NotificationService $notifications;

    public function __construct(?NotificationService $notifications = null)
    {
        $this->notifications = $notifications ?? app(NotificationService::class);
    }

    /**
     * Create a new purchase order.
     */
    public function create(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_id' => $data['business_id'],
                'branch_id' => $data['branch_id'] ?? null,
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

        $poItem = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'quantity' => $itemData['quantity'],
            'received_quantity' => 0,
            'pending_quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'] ?? $product->purchase_without_tax ?? 0,
            'discount' => $itemData['discount'] ?? 0,
            'tax' => $itemData['tax'] ?? 0,
            'notes' => $itemData['notes'] ?? null,
        ]);

        $po->calculateTotal();

        return $poItem;
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
        if (!$po->isDraft()) {
            throw new \Exception('Only draft orders can be sent');
        }

        $po->markAsSent();

        // Email + SMS the supplier with the full PO details.
        $this->notifySupplier($po, 'sent');

        return $po;
    }

    /**
     * Approve purchase order.
     */
    public function approve(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (!$po->isSent()) {
            throw new \Exception('Only sent orders can be approved');
        }

        $po->approve($userId);

        // Status already moved to accepted by approve(); inform the supplier.
        $this->notifySupplier($po, 'approved');

        return $po;
    }

    /**
     * Reject purchase order.
     */
    public function reject(PurchaseOrder $po, int $userId, string $reason): PurchaseOrder
    {
        if (!$po->isSent()) {
            throw new \Exception('Only sent orders can be rejected');
        }

        $po->reject($userId, $reason);

        // Status already moved to rejected by reject(); inform the supplier.
        $this->notifySupplier($po, 'rejected', $reason ? "Reason: {$reason}" : null);

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

        // Inform the supplier about the cancellation.
        $this->notifySupplier($po, 'cancelled');

        // No inventory rollback is required here: orders that already have
        // received quantities (received / partially received) cannot reach
        // this point, so no stock movements were ever recorded for a
        // cancellable PO.

        return $po;
    }

    /**
     * Restore cancelled purchase order.
     */
    public function restore(PurchaseOrder $po): PurchaseOrder
    {
        if (!$po->isCancelled()) {
            throw new \Exception('Only cancelled orders can be restored');
        }

        $po->update(['status' => PurchaseOrder::STATUS_DRAFT]);

        // Let the supplier know the previously cancelled order is back on.
        $this->notifySupplier($po, 'restored');

        return $po;
    }

    /**
     * Convert PO to Purchase.
     */
    public function convertToPurchase(PurchaseOrder $po): \App\Models\Purchase
    {
        if (!$po->isApproved()) {
            throw new \Exception('Only approved orders can be converted to purchases');
        }

        return DB::transaction(function () use ($po) {
            $purchase = \App\Models\Purchase::create([
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
                \App\Models\PurchaseDetails::create([
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
        if (!$po->isDraft()) {
            throw new \Exception('Only draft orders can be deleted');
        }

        return $po->delete();
    }

    /**
     * Notify the linked supplier (email + SMS) about a PO lifecycle event.
     *
     * @param  PurchaseOrder  $po  The purchase order.
     * @param  string  $event  Past-tense verb, e.g. "sent", "approved", "cancelled".
     * @param  string|null  $note  Optional extra line (e.g. a rejection reason).
     */
    protected function notifySupplier(PurchaseOrder $po, string $event, ?string $note = null): void
    {
        $po->loadMissing(['supplier', 'items.product']);

        $supplier = $po->supplier;

        $lines = [
            "Purchase order {$po->po_number} has been {$event}.",
            'Status: '.$po->status,
            'Priority: '.($po->priority ?? 'normal'),
            'Items: '.$po->items->count(),
            'Total: '.number_format((float) ($po->total_amount ?? 0), 2),
        ];

        if ($po->expected_delivery_date) {
            $lines[] = 'Expected delivery: '.$po->expected_delivery_date->toDateString();
        }

        if ($note) {
            $lines[] = $note;
        }

        $table = [
            'headers' => ['Product', 'Quantity', 'Unit Price', 'Total'],
            'rows' => $po->items->map(function (PurchaseOrderItem $item) {
                return [
                    $item->product?->name ?? "Product #{$item->product_id}",
                    (string) $item->quantity,
                    number_format((float) $item->unit_price, 2),
                    number_format((float) $item->total, 2),
                ];
            })->values()->toArray(),
        ];

        $result = $this->notifications->notifySupplier(
            $supplier,
            "Purchase Order {$po->po_number} {$event}",
            $lines,
            $table
        );

        Log::info("Supplier notified about purchase order {$event}", [
            'purchase_order_id' => $po->id,
            'po_number' => $po->po_number,
            'supplier_id' => $supplier?->id,
            'email_sent' => $result['email'],
            'sms_sent' => $result['sms'],
        ]);
    }
}
