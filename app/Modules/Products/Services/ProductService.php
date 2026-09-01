<?php

namespace App\Modules\Products\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Product;
use App\Models\Stock;
use App\Traits\WithTransactionalOperations;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    use WithTransactionalOperations;

    public function getProducts(array $filters, int $businessId, int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::where('business_id', $businessId);

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('productName', 'like', "%{$filters['search']}%")
                  ->orWhere('productCode', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['category', 'tax'])->latest()->paginate($perPage);
    }

    public function getProduct(int $id, int $businessId): Product
    {
        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->with(['category', 'tax', 'stocks'])
            ->firstOrFail();

        return $product;
    }

    public function createProduct(array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $product = Product::create($data + [
                'business_id' => $businessId,
            ]);

            if (isset($data['stock_quantity']) && $data['stock_quantity'] > 0) {
                Stock::create([
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'productStock' => $data['stock_quantity'],
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                    'purchase_price' => $data['purchase_price'] ?? null,
                    'cost_price' => $data['cost_price'] ?? null,
                ]);
            }

            return $product->fresh();
        });
    }

    public function updateProduct(int $id, array $data, int $businessId): Product
    {
        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $product->update($data);

        return $product->fresh();
    }

    public function deleteProduct(int $id, int $businessId): bool
    {
        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();

        return $product->delete();
    }

    public function getProductStock(int $productId, int $businessId): array
    {
        $stocks = Stock::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->get();

        return [
            'total_stock' => $stocks->sum('productStock'),
            'batches' => $stocks,
        ];
    }

    public function updateStock(int $productId, array $data, int $businessId): void
    {
        $this->executeTransaction(function () use ($productId, $data, $businessId) {
            $product = Product::where('id', $productId)
                ->where('business_id', $businessId)
                ->firstOrFail();

            $quantity = $data['quantity'] ?? 0;

            if ($quantity < 0) {
                $stock = Stock::where('product_id', $productId)
                    ->where('business_id', $businessId)
                    ->first();

                if (!$stock || $stock->productStock < abs($quantity)) {
                    throw new InsufficientStockException($productId, abs($quantity), $stock->productStock ?? 0);
                }

                $stock->decrement('productStock', abs($quantity));
            } else {
                $stock = Stock::where('product_id', $productId)
                    ->where('business_id', $businessId)
                    ->first();

                if ($stock) {
                    $stock->increment('productStock', $quantity);
                } else {
                    Stock::create([
                        'business_id' => $businessId,
                        'product_id' => $productId,
                        'productStock' => $quantity,
                        'batch_no' => $data['batch_no'] ?? null,
                        'expire_date' => $data['expire_date'] ?? null,
                        'purchase_price' => $data['purchase_price'] ?? null,
                        'cost_price' => $data['cost_price'] ?? null,
                    ]);
                }
            }
        });
    }
}
