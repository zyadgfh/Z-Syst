<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\Inventory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

final class GoodsReceivedNoteService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly StockBatchService $stockBatchService,
        private readonly StockMovementService $stockMovementService,
    ) {}

    /**
     * Create a GRN and process items with full batch/inventory tracking.
     * Uses StockBatchService for FEFO-compatible stock receipt.
     */
    public function create(array $data, array $items, string $companyId, string $userId): GoodsReceivedNote
    {
        return $this->db->transaction(function () use ($data, $items, $companyId, $userId) {
            $note = $this->createNote($data, $companyId, $userId);
            $this->processItems($note, $items, $companyId, $data['branch_id']);
            $this->updatePurchaseOrderStatus($note);

            return $note->load(['supplier', 'branch', 'items.product', 'receivedBy']);
        });
    }

    private function createNote(array $data, string $companyId, string $userId): GoodsReceivedNote
    {
        return GoodsReceivedNote::create([
            'company_id' => $companyId,
            'purchase_order_id' => $data['purchase_order_id'],
            'supplier_id' => $data['supplier_id'],
            'branch_id' => $data['branch_id'],
            'grn_number' => $data['grn_number'] ?? 'GRN-'.strtoupper(Str::random(8)),
            'notes' => $data['notes'] ?? null,
            'received_by' => $userId,
        ]);
    }

    private function processItems(GoodsReceivedNote $note, array $items, string $companyId, string $branchId): void
    {
        foreach ($items as $item) {
            // 1. Create GRN item record
            $note->items()->create([
                'product_id' => $item['product_id'],
                'batch_number' => $item['batch_number'] ?? null,
                'quantity_received' => $item['quantity_received'],
                'unit_cost' => $item['unit_cost'],
                'expiry_date' => $item['expiry_date'] ?? null,
                'manufacturing_date' => $item['manufacturing_date'] ?? null,
                'rack_location' => $item['rack_location'] ?? null,
            ]);

            // 2. Use StockBatchService to receive stock (creates/updates Inventory + StockBatch records)
            $this->stockBatchService->receiveStock([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'product_id' => $item['product_id'],
                'batch_number' => $item['batch_number'] ?? null,
                'expiry_date' => $item['expiry_date'] ?? null,
                'manufacturing_date' => $item['manufacturing_date'] ?? null,
                'quantity' => $item['quantity_received'],
                'cost_price' => $item['unit_cost'],
                'selling_price' => $item['selling_price'] ?? 0,
                'supplier_id' => $note->supplier_id,
                'purchase_order_id' => $note->purchase_order_id,
                'grn_id' => $note->id,
                'rack_location' => $item['rack_location'] ?? null,
            ]);

            // 3. Update purchase order item received quantity
            if ($note->purchaseOrder) {
                $note->purchaseOrder->items()
                    ->where('product_id', $item['product_id'])
                    ->increment('quantity_received', $item['quantity_received']);
            }
        }
    }

    private function updatePurchaseOrderStatus(GoodsReceivedNote $note): void
    {
        $purchaseOrder = $note->purchaseOrder;

        if (! $purchaseOrder) {
            return;
        }

        $hasPendingItems = $purchaseOrder->items()
            ->whereColumn('quantity_received', '<', 'quantity_ordered')
            ->exists();

        $purchaseOrder->update([
            'status' => $hasPendingItems ? 'partial' : 'received',
        ]);
    }
}

