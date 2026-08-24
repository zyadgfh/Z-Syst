<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\GrnItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class GRNService
{
    /**
     * Create a new GRN.
     */
    public function create(array $data): GoodsReceivedNote
    {
        return DB::transaction(function () use ($data) {
            $grn = GoodsReceivedNote::create([
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_id' => $data['business_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'received_by' => $data['received_by'] ?? null,
                'grn_number' => GoodsReceivedNote::generateGRNNumber(),
                'received_date' => $data['received_date'] ?? now(),
                'location' => $data['location'] ?? null,
                'status' => GoodsReceivedNote::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            // Add items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addItem($grn, $item);
                }
            }

            return $grn;
        });
    }

    /**
     * Add item to GRN.
     */
    public function addItem(GoodsReceivedNote $grn, array $itemData): GrnItem
    {
        $product = Product::findOrFail($itemData['product_id']);

        // Map API field names to model column names
        $receivedQty = $itemData['quantity_received'] ?? $itemData['received_quantity'] ?? 0;
        $acceptedQty = $itemData['quantity_accepted'] ?? $itemData['accepted_quantity'] ?? $receivedQty;
        $rejectedQty = $itemData['quantity_rejected'] ?? $itemData['rejected_quantity'] ?? 0;
        $unitCost = $itemData['unit_cost'] ?? $itemData['purchase_price'] ?? $product->purchase_without_tax ?? 0;

        $grnItem = GrnItem::create([
            'grn_id' => $grn->id,
            'product_id' => $product->id,
            'ordered_quantity' => $itemData['ordered_quantity'] ?? 0,
            'received_quantity' => $receivedQty,
            'accepted_quantity' => $acceptedQty,
            'rejected_quantity' => $rejectedQty,
            'batch_number' => $itemData['batch_number'] ?? null,
            'expiry_date' => $itemData['expiry_date'] ?? null,
            'purchase_price' => $unitCost,
            'notes' => $itemData['notes'] ?? null,
        ]);

        return $grnItem;
    }

    /**
     * Update GRN.
     */
    public function update(GoodsReceivedNote $grn, array $data): GoodsReceivedNote
    {
        return DB::transaction(function () use ($grn, $data) {
            $grn->update([
                'supplier_id' => $data['supplier_id'] ?? $grn->supplier_id,
                'location' => $data['location'] ?? $grn->location,
                'notes' => $data['notes'] ?? $grn->notes,
            ]);

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $grn->items()->delete();
                foreach ($data['items'] as $item) {
                    $this->addItem($grn, $item);
                }
            }

            return $grn;
        });
    }

    /**
     * Verify GRN.
     */
    public function verify(GoodsReceivedNote $grn, mixed $userIdOrData = null): GoodsReceivedNote
    {
        if (! in_array($grn->status, [GoodsReceivedNote::STATUS_DRAFT, GoodsReceivedNote::STATUS_PENDING])) {
            throw new \Exception('Only draft or pending GRNs can be verified');
        }

        $userId = is_array($userIdOrData) ? ($userIdOrData['user_id'] ?? (auth()->id() ?? 1)) : ($userIdOrData ?? (auth()->id() ?? 1));

        return DB::transaction(function () use ($grn, $userId) {
            $grn->markAsVerified($userId);

            // Update stock based on accepted quantities
            foreach ($grn->items as $item) {
                if ($item->accepted_quantity > 0) {
                    $this->updateStock($grn, $item);
                }
            }

            // Update PO status if linked
            if ($grn->purchaseOrder) {
                $this->updatePurchaseOrderStatus($grn->purchaseOrder);
            }

            return $grn;
        });
    }

    /**
     * Update stock for GRN item.
     */
    protected function updateStock(GoodsReceivedNote $grn, GrnItem $item): void
    {
        $stock = Stock::firstOrCreate([
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
        ]);

        $stock->increment('productStock', $item->accepted_quantity);
    }

    /**
     * Update purchase order status based on GRN.
     */
    protected function updatePurchaseOrderStatus($purchaseOrder): void
    {
        $totalReceived = $purchaseOrder->items->sum('received_quantity');
        $totalOrdered = $purchaseOrder->items->sum('quantity');

        if ($totalReceived >= $totalOrdered) {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_RECEIVED]);
        } elseif ($totalReceived > 0) {
            $purchaseOrder->update(['status' => PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    /**
     * Accept GRN.
     */
    public function accept(GoodsReceivedNote $grn): GoodsReceivedNote
    {
        if (! $grn->isVerified()) {
            throw new \Exception('Only verified GRNs can be accepted');
        }

        $totalRejected = $grn->total_rejected_quantity;
        $totalReceived = $grn->total_received_quantity;

        return DB::transaction(function () use ($grn, $totalRejected, $totalReceived) {
            if ($totalRejected > 0 && $totalRejected < $totalReceived) {
                $grn->markAsPartiallyAccepted();
            } elseif ($totalRejected === 0) {
                $grn->markAsAccepted();
            } else {
                $grn->markAsRejected();
            }

            // Update stock
            foreach ($grn->items as $item) {
                if ($item->accepted_quantity > 0) {
                    $this->updateStock($grn, $item);
                }
            }

            return $grn;
        });
    }

    /**
     * Reject GRN.
     */
    public function reject(GoodsReceivedNote $grn): GoodsReceivedNote
    {
        if (! $grn->isVerified()) {
            throw new \Exception('Only verified GRNs can be rejected');
        }

        return DB::transaction(function () use ($grn) {
            $grn->markAsRejected();

            // Rollback stock if already updated
            foreach ($grn->items as $item) {
                if ($item->accepted_quantity > 0) {
                    $this->rollbackStock($grn, $item);
                }
            }

            return $grn;
        });
    }

    /**
     * Rollback stock for GRN item.
     */
    protected function rollbackStock(GoodsReceivedNote $grn, GrnItem $item): void
    {
        $stock = Stock::where([
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
        ])->first();

        if ($stock) {
            $stock->decrement('productStock', $item->accepted_quantity);
        }
    }

    /**
     * Delete GRN.
     */
    public function delete(GoodsReceivedNote $grn): void
    {
        if ($grn->isVerified()) {
            throw new \Exception('Cannot delete verified GRNs');
        }

        $grn->delete();
    }

    /**
     * Get GRNs by business.
     */
    public function getByBusiness(int $businessId)
    {
        return GoodsReceivedNote::forBusiness($businessId)
            ->with(['supplier', 'items.product', 'receivedBy'])
            ->latest()
            ->get();
    }

    /**
     * Get GRNs by supplier.
     */
    public function getBySupplier(int $supplierId, int $businessId)
    {
        return GoodsReceivedNote::forBusiness($businessId)
            ->forSupplier($supplierId)
            ->with(['items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get GRNs by purchase order.
     */
    public function getByPurchaseOrder(int $purchaseOrderId, int $businessId)
    {
        return GoodsReceivedNote::forBusiness($businessId)
            ->forPurchaseOrder($purchaseOrderId)
            ->with(['items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get pending GRNs.
     */
    public function getPending(int $businessId)
    {
        return GoodsReceivedNote::forBusiness($businessId)
            ->pending()
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get GRN statistics.
     */
    public function getStatistics(int $businessId): array
    {
        $query = GoodsReceivedNote::forBusiness($businessId);

        return [
            'total' => $query->count(),
            'pending' => $query->clone()->pending()->count(),
            'verified' => $query->clone()->verified()->count(),
            'accepted' => $query->clone()->byStatus(GoodsReceivedNote::STATUS_ACCEPTED)->count(),
            'partially_accepted' => $query->clone()->byStatus(GoodsReceivedNote::STATUS_PARTIALLY_ACCEPTED)->count(),
            'rejected' => $query->clone()->byStatus(GoodsReceivedNote::STATUS_REJECTED)->count(),
        ];
    }

    /**
     * Check if GRN can be edited.
     */
    public function canBeEdited(GoodsReceivedNote $grn): bool
    {
        return in_array($grn->status, [GoodsReceivedNote::STATUS_DRAFT, GoodsReceivedNote::STATUS_PENDING]);
    }

    /**
     * Check if GRN can be deleted.
     */
    public function canBeDeleted(GoodsReceivedNote $grn): bool
    {
        return $grn->status === GoodsReceivedNote::STATUS_DRAFT;
    }

    /**
     * Check if GRN can be verified.
     */
    public function canBeVerified(GoodsReceivedNote $grn): bool
    {
        return $grn->status === GoodsReceivedNote::STATUS_PENDING;
    }

    /**
     * Check if GRN can be accepted.
     */
    public function canBeAccepted(GoodsReceivedNote $grn): bool
    {
        return $grn->status === GoodsReceivedNote::STATUS_VERIFIED;
    }

    /**
     * Remove a GRN item.
     */
    public function removeItem(GrnItem $item): bool
    {
        $item->delete();

        return true;
    }

    /**
     * Update a GRN item.
     */
    public function updateItem(GrnItem $item, array $data): GrnItem
    {
        $item->update($data);

        return $item;
    }

    /**
     * Get pending GRNs (alias for getPending).
     */
    public function getPendingGRNs(int $businessId)
    {
        return $this->getPending($businessId);
    }

    /**
     * Get GRNs by status.
     */
    public function getByStatus(int $businessId, string $status)
    {
        return GoodsReceivedNote::forBusiness($businessId)
            ->byStatus($status)
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Update GRN status derived from items.
     */
    public function updateGRNStatus(GoodsReceivedNote $grn): string
    {
        $totalOrdered = $grn->items->sum('ordered_quantity');
        $totalReceived = $grn->items->sum('received_quantity');

        if ($totalReceived === 0) {
            $newStatus = GoodsReceivedNote::STATUS_PENDING;
        } elseif ($totalReceived >= $totalOrdered && $totalOrdered > 0) {
            $newStatus = GoodsReceivedNote::STATUS_RECEIVED;
        } else {
            $newStatus = GoodsReceivedNote::STATUS_PARTIALLY_RECEIVED;
        }

        $grn->update(['status' => $newStatus]);

        return $newStatus;
    }
}
