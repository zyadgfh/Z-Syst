<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class WarehouseStockService
{
    public function getDefaultWarehouse(int $businessId): ?Warehouse
    {
        return Warehouse::where('business_id', $businessId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public function getWarehouses(int $businessId): array
    {
        return Warehouse::where('business_id', $businessId)
            ->where('is_active', true)
            ->get()
            ->toArray();
    }

    public function createWarehouse(array $data): Warehouse
    {
        $warehouse = Warehouse::create($data);

        AuditLogger::log('warehouse.created', 'New warehouse created.', [
            'warehouse_id' => $warehouse->id,
            'business_id' => $warehouse->business_id,
        ]);

        return $warehouse;
    }

    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update($data);

        AuditLogger::log('warehouse.updated', 'Warehouse updated.', [
            'warehouse_id' => $warehouse->id,
            'business_id' => $warehouse->business_id,
        ]);

        return $warehouse;
    }

    public function getStockByWarehouse(int $productId, ?int $warehouseId = null, ?int $businessId = null): int
    {
        $query = WarehouseStock::where('product_id', $productId);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        return $query->sum('quantity');
    }

    public function createStockTransfer(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $transfer = StockTransfer::create($data);

            if ($transfer->status === 'completed') {
                $this->executeTransfer($transfer);
            }

            AuditLogger::log('stock.transfer', 'Stock transfer created.', [
                'transfer_id' => $transfer->id,
                'from_warehouse_id' => $transfer->from_warehouse_id,
                'to_warehouse_id' => $transfer->to_warehouse_id,
                'product_id' => $transfer->product_id,
                'quantity' => $transfer->quantity,
            ]);

            return $transfer;
        });
    }

    public function executeTransfer(StockTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer) {
            $fromStock = WarehouseStock::where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $transfer->product_id)
                ->lockForUpdate()
                ->first();

            if (! $fromStock || $fromStock->quantity < $transfer->quantity) {
                throw new \Exception('Insufficient stock in source warehouse.');
            }

            $fromStock->decrement('quantity', $transfer->quantity);

            $toStock = WarehouseStock::firstOrCreate(
                [
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $transfer->product_id,
                ],
                [
                    'business_id' => $transfer->business_id,
                    'quantity' => 0,
                ]
            );

            $toStock->increment('quantity', $transfer->quantity);

            $transfer->update(['status' => 'completed']);
        });
    }

    public function transferStock(int $businessId, int $productId, int $fromWarehouseId, int $toWarehouseId, int $quantity, ?string $notes = null): StockTransfer
    {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new \Exception('Cannot transfer to the same warehouse.');
        }

        return $this->createStockTransfer([
            'business_id' => $businessId,
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id' => $toWarehouseId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'status' => 'completed',
            'notes' => $notes,
            'user_id' => auth()->id() ?? 1,
        ]);
    }
}
