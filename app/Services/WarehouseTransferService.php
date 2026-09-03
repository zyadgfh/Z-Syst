<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseTransferService
{
    /**
     * Transfer stock between warehouses
     */
    public function transferStock(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        int $quantity,
        string $reason = null
    ): array {
        return DB::transaction(function () use ($fromWarehouseId, $toWarehouseId, $productId, $quantity, $reason) {
            $fromWarehouse = Warehouse::findOrFail($fromWarehouseId);
            $toWarehouse = Warehouse::findOrFail($toWarehouseId);
            $product = Product::findOrFail($productId);

            // Check if source warehouse has enough stock
            $sourceStock = Stock::where('warehouse_id', $fromWarehouseId)
                ->where('product_id', $productId)
                ->where('quantity', '>=', $quantity)
                ->first();

            if (! $sourceStock) {
                throw new \Exception("Insufficient stock in source warehouse");
            }

            // Deduct from source warehouse
            $sourceStock->decrement('quantity', $quantity);

            // Add to destination warehouse
            $destStock = Stock::firstOrCreate(
                [
                    'warehouse_id' => $toWarehouseId,
                    'product_id' => $productId,
                ],
                [
                    'quantity' => 0,
                    'business_id' => $toWarehouse->business_id,
                ]
            );
            $destStock->increment('quantity', $quantity);

            // Log the transfer
            Log::info("Stock transferred from warehouse {$fromWarehouseId} to {$toWarehouseId}", [
                'product_id' => $productId,
                'quantity' => $quantity,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'from_warehouse' => $fromWarehouse->name,
                'to_warehouse' => $toWarehouse->name,
                'product' => $product->name,
                'quantity' => $quantity,
            ];
        });
    }

    /**
     * Get stock distribution across warehouses
     */
    public function getStockDistribution(int $productId): array
    {
        $stocks = Stock::with('warehouse')
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->get();

        return $stocks->map(function ($stock) {
            return [
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'quantity' => $stock->quantity,
                'location' => $stock->warehouse->location,
            ];
        })->toArray();
    }

    /**
     * Balance stock across warehouses based on demand
     */
    public function balanceStock(int $productId, array $targetDistribution): array
    {
        return DB::transaction(function () use ($productId, $targetDistribution) {
            $transfers = [];

            foreach ($targetDistribution as $distribution) {
                $warehouseId = $distribution['warehouse_id'];
                $targetQuantity = $distribution['target_quantity'];

                $currentStock = Stock::where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->first();

                if ($currentStock) {
                    $difference = $targetQuantity - $currentStock->quantity;

                    if ($difference > 0) {
                        // Need to add stock (transfer from other warehouses)
                        $transfers[] = [
                            'warehouse_id' => $warehouseId,
                            'needed' => $difference,
                            'action' => 'receive',
                        ];
                    } elseif ($difference < 0) {
                        // Need to remove stock (transfer to other warehouses)
                        $transfers[] = [
                            'warehouse_id' => $warehouseId,
                            'excess' => abs($difference),
                            'action' => 'send',
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'transfers_needed' => $transfers,
            ];
        });
    }

    /**
     * Get warehouse stock summary
     */
    public function getWarehouseSummary(int $warehouseId): array
    {
        $warehouse = Warehouse::with('business')->findOrFail($warehouseId);

        $totalProducts = Stock::where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->count();

        $totalStock = Stock::where('warehouse_id', $warehouseId)
            ->sum('quantity');

        $totalValue = Stock::where('warehouse_id', $warehouseId)
            ->join('products', 'stocks.product_id', '=', 'products.id')
            ->sum(DB::raw('stocks.quantity * products.purchase_price'));

        return [
            'warehouse' => $warehouse->name,
            'business' => $warehouse->business->name,
            'total_products' => $totalProducts,
            'total_stock' => $totalStock,
            'total_value' => $totalValue,
            'is_default' => $warehouse->is_default,
        ];
    }
}