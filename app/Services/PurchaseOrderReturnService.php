<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReturn;
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
                'return_number' => 'POR-' . strtoupper(Str::random(8)),
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
            }

            return $return->load(['purchaseOrder', 'items.product', 'supplier', 'branch', 'createdBy']);
        });
    }
}
