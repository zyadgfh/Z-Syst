<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\ProductStock;
use App\Models\PurchaseOrder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

final class GoodsReceivedNoteService
{
    public function __construct(
        private readonly DatabaseManager $db,
    ) {}

    public function create(array $data, array $items, int $companyId, int $userId): GoodsReceivedNote
    {
        return $this->db->transaction(function () use ($data, $items, $companyId, $userId) {
            $note = $this->createNote($data, $companyId, $userId);
            $this->processItems($note, $items, $companyId, $data['branch_id']);
            $this->updatePurchaseOrderStatus($note);

            return $note->load(['supplier', 'branch', 'items.product', 'receivedBy']);
        });
    }

    private function createNote(array $data, int $companyId, int $userId): GoodsReceivedNote
    {
        return GoodsReceivedNote::create([
            'company_id' => $companyId,
            'purchase_order_id' => $data['purchase_order_id'],
            'supplier_id' => $data['supplier_id'],
            'branch_id' => $data['branch_id'],
            'grn_number' => 'GRN-' . strtoupper(Str::random(8)),
            'notes' => $data['notes'] ?? null,
            'received_by' => $userId,
        ]);
    }

    private function processItems(GoodsReceivedNote $note, array $items, int $companyId, int $branchId): void
    {
        foreach ($items as $item) {
            $note->items()->create([
                'product_id' => $item['product_id'],
                'batch_number' => $item['batch_number'] ?? null,
                'quantity_received' => $item['quantity_received'],
                'unit_cost' => $item['unit_cost'],
                'expiry_date' => $item['expiry_date'] ?? null,
                'manufacturing_date' => $item['manufacturing_date'] ?? null,
                'rack_location' => $item['rack_location'] ?? null,
            ]);

            ProductStock::create([
                'company_id' => $companyId,
                'product_id' => $item['product_id'],
                'branch_id' => $branchId,
                'batch_number' => $item['batch_number'] ?? null,
                'quantity' => $item['quantity_received'],
                'unit_cost' => $item['unit_cost'],
                'expiry_date' => $item['expiry_date'] ?? null,
                'manufacturing_date' => $item['manufacturing_date'] ?? null,
                'rack_location' => $item['rack_location'] ?? null,
            ]);

            $note->purchaseOrder->items()
                ->where('product_id', $item['product_id'])
                ->increment('quantity_received', $item['quantity_received']);
        }
    }

    private function updatePurchaseOrderStatus(GoodsReceivedNote $note): void
    {
        $purchaseOrder = $note->purchaseOrder;
        $hasPendingItems = $purchaseOrder->items()
            ->whereColumn('quantity_received', '<', 'quantity_ordered')
            ->exists();

        $purchaseOrder->update([
            'status' => $hasPendingItems ? 'partial' : 'received',
        ]);
    }
}