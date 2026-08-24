<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WarehouseService
{
    /**
     * Create new warehouse
     */
    public function createWarehouse(array $data): Warehouse
    {
        return DB::transaction(function () use ($data) {
            // If this is set as default, remove default status from other warehouses
            if (isset($data['is_default']) && $data['is_default']) {
                Warehouse::where('business_id', $data['business_id'])
                    ->update(['is_default' => false]);
            }

            if (!isset($data['code'])) {
                $data['code'] = $this->generateUniqueWarehouseCode($data['business_id']);
            }

            return Warehouse::create($data);
        });
    }

    /**
     * Update warehouse
     */
    public function updateWarehouse(Warehouse $warehouse, array $data): Warehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            // If this is set as default, remove default status from other warehouses
            if (isset($data['is_default']) && $data['is_default'] && ! $warehouse->is_default) {
                Warehouse::where('business_id', $warehouse->business_id)
                    ->where('id', '!=', $warehouse->id)
                    ->update(['is_default' => false]);
            }

            $warehouse->update($data);

            return $warehouse->fresh();
        });
    }

    /**
     * Delete warehouse
     */
    public function deleteWarehouse(Warehouse $warehouse): bool
    {
        if ($warehouse->is_default) {
            throw new \Exception('Cannot delete default warehouse');
        }

        if ($warehouse->stocks()->exists()) {
            throw new \Exception('Cannot delete warehouse with existing stock');
        }

        return $warehouse->delete();
    }

    /**
     * Add stock to warehouse
     */
    public function addStock(int $warehouseId, int $productId, int $quantity, int $businessId): WarehouseStock
    {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity, $businessId) {
            $stock = WarehouseStock::firstOrCreate([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'business_id' => $businessId,
            ]);

            $stock->increase($quantity);

            return $stock->fresh();
        });
    }

    /**
     * Remove stock from warehouse
     */
    public function removeStock(int $warehouseId, int $productId, int $quantity): bool
    {
        return DB::transaction(function () use ($warehouseId, $productId, $quantity) {
            $stock = WarehouseStock::where([
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ])->first();

            if (! $stock) {
                throw new \Exception('Stock not found');
            }

            if (! $stock->decrease($quantity)) {
                throw new \Exception('Insufficient stock');
            }

            return true;
        });
    }

    /**
     * Create stock transfer
     */
    public function createTransfer(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            // Validate source warehouse has sufficient stock
            $fromWarehouse = Warehouse::find($data['from_warehouse_id']);
            if (! $fromWarehouse->hasSufficientStock($data['product_id'], $data['quantity'])) {
                throw new \Exception('Insufficient stock in source warehouse');
            }

            // Validate transfer is not to same warehouse
            if ($data['from_warehouse_id'] === $data['to_warehouse_id']) {
                throw new \Exception('Cannot transfer to same warehouse');
            }

            return StockTransfer::create($data);
        });
    }

    /**
     * Complete stock transfer
     */
    public function completeTransfer(StockTransfer $transfer): StockTransfer
    {
        if (! $transfer->complete()) {
            throw new \Exception('Cannot complete transfer');
        }

        return $transfer->fresh();
    }

    /**
     * Cancel stock transfer
     */
    public function cancelTransfer(StockTransfer $transfer): StockTransfer
    {
        if (! $transfer->cancel()) {
            throw new \Exception('Cannot cancel transfer');
        }

        return $transfer->fresh();
    }

    /**
     * Get total stock across all warehouses for a product
     */
    public function getTotalStockForProduct(int $businessId, int $productId): int
    {
        return WarehouseStock::forBusiness($businessId)
            ->where('product_id', $productId)
            ->sum('quantity');
    }

    /**
     * Get stock distribution across warehouses for a product
     */
    public function getStockDistribution(int $businessId, int $productId): array
    {
        return WarehouseStock::forBusiness($businessId)
            ->where('product_id', $productId)
            ->with('warehouse:id,name,code')
            ->get()
            ->map(function ($stock) {
                return [
                    'warehouse_id' => $stock->warehouse_id,
                    'warehouse_name' => $stock->warehouse->name,
                    'warehouse_code' => $stock->warehouse->code,
                    'quantity' => $stock->quantity,
                ];
            })
            ->toArray();
    }

    /**
     * Get warehouse statistics
     */
    public function getWarehouseStatistics(int $businessId, ?int $warehouseId = null): array
    {
        $query = Warehouse::forBusiness($businessId);

        if ($warehouseId) {
            $query->where('id', $warehouseId);
        }

        $warehouses = $query->get();

        return [
            'total_warehouses' => $warehouses->count(),
            'active_warehouses' => $warehouses->where('is_active', true)->count(),
            'total_products' => WarehouseStock::forBusiness($businessId)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->count(),
            'total_stock' => WarehouseStock::forBusiness($businessId)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->sum('quantity'),
            'low_stock_products' => WarehouseStock::forBusiness($businessId)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->lowStock()
                ->count(),
            'out_of_stock_products' => WarehouseStock::forBusiness($businessId)
                ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
                ->outOfStock()
                ->count(),
            'pending_transfers' => StockTransfer::forBusiness($businessId)
                ->when($warehouseId, fn ($q) => $q->where('from_warehouse_id', $warehouseId))
                ->pending()
                ->count(),
        ];
    }

    /**
     * Get transfer statistics
     */
    public function getTransferStatistics(int $businessId, array $filters = []): array
    {
        $query = StockTransfer::forBusiness($businessId);

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['warehouse_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('from_warehouse_id', $filters['warehouse_id'])
                    ->orWhere('to_warehouse_id', $filters['warehouse_id']);
            });
        }

        $transfers = $query->get();

        return [
            'total_transfers' => $transfers->count(),
            'total_quantity_transferred' => $transfers->sum('quantity'),
            'pending_transfers' => $transfers->where('status', 'pending')->count(),
            'completed_transfers' => $transfers->where('status', 'completed')->count(),
            'cancelled_transfers' => $transfers->where('status', 'cancelled')->count(),
        ];
    }

    /**
     * Generate unique warehouse code
     */
    protected function generateUniqueWarehouseCode(int $businessId): string
    {
        do {
            $code = 'WH-'.$businessId.'-'.strtoupper(Str::random(4));
        } while (Warehouse::where('code', $code)->exists());

        return $code;
    }

    /**
     * Set default warehouse for business
     */
    public function setDefaultWarehouse(Warehouse $warehouse): Warehouse
    {
        return DB::transaction(function () use ($warehouse) {
            // Remove default status from other warehouses
            Warehouse::where('business_id', $warehouse->business_id)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);

            // Set this warehouse as default
            $warehouse->update(['is_default' => true]);

            return $warehouse->fresh();
        });
    }

    /**
     * Sync stock across warehouses (for inventory reconciliation)
     */
    public function syncStock(int $businessId, array $stockData): array
    {
        $results = [];

        foreach ($stockData as $data) {
            try {
                DB::transaction(function () use ($data, $businessId, &$results) {
                    $stock = WarehouseStock::firstOrCreate([
                        'warehouse_id' => $data['warehouse_id'],
                        'product_id' => $data['product_id'],
                        'business_id' => $businessId,
                    ]);

                    $oldQuantity = $stock->quantity;
                    $stock->update(['quantity' => $data['quantity']]);

                    $results[] = [
                        'warehouse_id' => $data['warehouse_id'],
                        'product_id' => $data['product_id'],
                        'old_quantity' => $oldQuantity,
                        'new_quantity' => $data['quantity'],
                        'status' => 'success',
                    ];
                });
            } catch (\Exception $e) {
                $results[] = [
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $data['product_id'],
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
