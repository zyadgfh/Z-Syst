<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\GRNItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\ProductStock;
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
    public function addItem(GoodsReceivedNote $grn, array $itemData): GRNItem
    {
        $product = Product::findOrFail($itemData['product_id']);

        $grnItem = GRNItem::create([
            'grn_id' => $grn->id,
            'product_id' => $product->id,
            'ordered_quantity' => $itemData['ordered_quantity'] ?? 0,
            'received_quantity' => $itemData['received_quantity'] ?? 0,
            'accepted_quantity' => $itemData['accepted_quantity'] ?? $itemData['received_quantity'] ?? 0,
            'rejected_quantity' => $itemData['rejected_quantity'] ?? 0,
            'batch_number' => $itemData['batch_number'] ?? null,
            'expiry_date' => $itemData['expiry_date'] ?? null,
            'purchase_price' => $itemData['purchase_price'] ?? $product->purchase_without_tax ?? 0,
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
    public function verify(GoodsReceivedNote $grn, int $userId): GoodsReceivedNote
    {
        if (!$grn->isPending()) {
            throw new \Exception('Only pending GRNs can be verified');
        }

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
    protected function updateStock(GoodsReceivedNote $grn, GRNItem $item): void
    {
        $stock = Stock::firstOrCreate([
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
            'branch_id' => $grn->branch_id,
        ]);

        $stock->increment('quantity', $item->accepted_quantity);

        // Add product stock entry with batch/expiry
        ProductStock::create([
            'stock_id' => $stock->id,
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
            'branch_id' => $grn->branch_id,
            'quantity' => $item->accepted_quantity,
            'batch_no' => $item->batch_number,
            'expire_date' => $item->expiry_date,
            'purchase_price' => $item->purchase_price,
            'grn_id' => $grn->id,
        ]);
    }

    /**
     * Update purchase order status based on GRN.
     */
    protected function updatePurchaseOrderStatus($purchaseOrder): void
    {
        $totalReceived = $purchaseOrder->items->sum('received_quantity');
        $totalOrdered = $purchaseOrder->items->sum('quantity');

        if ($totalReceived >= $totalOrdered) {
            $purchaseOrder->update(['status' => \App\Models\PurchaseOrder::STATUS_RECEIVED]);
        } elseif ($totalReceived > 0) {
            $purchaseOrder->update(['status' => \App\Models\PurchaseOrder::STATUS_PARTIALLY_RECEIVED]);
        }
    }

    /**
     * Accept GRN.
     */
    public function accept(GoodsReceivedNote $grn): GoodsReceivedNote
    {
        if (!$grn->isVerified()) {
            throw new \Exception('Only verified GRNs can be accepted');
        }

        $totalRejected = $grn->total_rejected_quantity;
        $totalReceived = $grn->total_received_quantity;

        if ($totalRejected > 0 && $totalRejected < $totalReceived) {
            $grn->markAsPartiallyAccepted();
        } elseif ($totalRejected === 0) {
            $grn->markAsAccepted();
        } else {
            $grn->markAsRejected();
        }

        return $grn;
    }

    /**
     * Reject GRN.
     */
    public function reject(GoodsReceivedNote $grn): GoodsReceivedNote
    {
        if (!$grn->isVerified()) {
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
    protected function rollbackStock(GoodsReceivedNote $grn, GRNItem $item): void
    {
        $stock = Stock::where([
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
            'branch_id' => $grn->branch_id,
        ])->first();

        if ($stock) {
            $stock->decrement('quantity', $item->accepted_quantity);
        }

        // Delete product stock entries
        ProductStock::where([
            'product_id' => $item->product_id,
            'business_id' => $grn->business_id,
            'branch_id' => $grn->branch_id,
            'grn_id' => $grn->id,
        ])->delete();
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
}
