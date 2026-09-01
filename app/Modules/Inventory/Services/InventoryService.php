<?php

namespace App\Modules\Inventory\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Services\FEFODispensingService;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryService
{
    protected FEFODispensingService $fefoDispensingService;

    public function __construct(FEFODispensingService $fefoDispensingService)
    {
        $this->fefoDispensingService = $fefoDispensingService;
    }

    public function getInventory(array $filters, int $businessId, int $perPage = 15): LengthAwarePaginator
    {
        $query = Stock::where('business_id', $businessId)
            ->with('product');

        if (isset($filters['search'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->where('productName', 'like', "%{$filters['search']}%")
                  ->orWhere('productCode', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['low_stock'])) {
            $query->where('productStock', '<', $filters['low_stock']);
        }

        if (isset($filters['batch_no'])) {
            $query->where('batch_no', 'like', "%{$filters['batch_no']}%");
        }

        return $query->latest()->paginate($perPage);
    }

    public function getLowStock(int $businessId, int $threshold = 10): array
    {
        $lowStock = Stock::where('business_id', $businessId)
            ->where('productStock', '<=', $threshold)
            ->where('productStock', '>', 0)
            ->with('product')
            ->get();

        return [
            'count' => $lowStock->count(),
            'items' => $lowStock,
        ];
    }

    public function getExpiringBatches(int $businessId, int $daysThreshold = 30): array
    {
        $thresholdDate = now()->addDays($daysThreshold);

        $expiring = Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->where('expire_date', '<=', $thresholdDate)
            ->where('expire_date', '>=', now())
            ->with('product')
            ->orderBy('expire_date', 'asc')
            ->get();

        return [
            'count' => $expiring->count(),
            'items' => $expiring,
        ];
    }

    public function createStockMovement(array $data, int $businessId, int $userId): StockMovement
    {
        return StockMovement::create([
            'business_id' => $businessId,
            'product_id' => $data['product_id'],
            'stock_id' => $data['stock_id'] ?? null,
            'user_id' => $userId,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'movement_date' => $data['movement_date'] ?? now(),
        ]);
    }

    public function getStockMovements(array $filters, int $businessId, int $perPage = 15): LengthAwarePaginator
    {
        $query = StockMovement::where('business_id', $businessId)
            ->with(['product', 'stock', 'user']);

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['date_from'])) {
            $query->where('movement_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('movement_date', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }
}
