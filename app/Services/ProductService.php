<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\StockNotFoundException;
use App\Models\Product;
use App\Models\Stock;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    use WithTransactionalOperations;

    /**
     * Create a new product with stock.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Product
     * @throws \Exception
     */
    public function createProduct(array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $product = Product::create($data + [
                'business_id' => $businessId,
            ]);

            Stock::create($data + [
                'product_id' => $product->id,
                'business_id' => $businessId,
            ]);

            return $product->fresh();
        });
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Product
     * @throws \Exception
     */
    public function updateProduct(Product $product, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($product, $data, $businessId) {
            // Handle image removal
            if (isset($data['removed_images'])) {
                $prevImages = array_diff($product->images ?? [], $data['removed_images']);
                foreach ($data['removed_images'] as $image) {
                    if (Storage::exists($image)) {
                        Storage::delete($image);
                    }
                }
                $prevImages = array_values($prevImages);
            } else {
                $prevImages = $product->images ?? [];
            }

            // Merge new images
            $newImages = $data['images'] ?? [];
            $mergedImages = array_merge($prevImages, $newImages);

            // Update stock
            $stock = Stock::where('product_id', $product->id)->first();
            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                    'productStock' => $stock->productStock + ($data['qty'] ?? 0),
                ]);
            } else {
                Stock::create($data + [
                    'product_id' => $product->id,
                    'business_id' => $businessId,
                    'productStock' => $data['qty'] ?? 0,
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                ]);
            }

            // Update product
            $product->update($data + [
                'images' => $mergedImages,
            ]);

            return $product->fresh();
        });
    }

    /**
     * Update product stock.
     *
     * @param int $productId
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Product
     * @throws InsufficientStockException
     * @throws StockNotFoundException
     * @throws \Exception
     */
    public function updateStock(int $productId, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($productId, $data, $businessId) {
            $product = Product::findOrFail($productId);
            $product->update($data);

            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', $data['batch_no'] ?? null)
                ->first();

            if ($stock) {
                $newQuantity = $stock->productStock + ($data['qty'] ?? 0);
                if ($newQuantity < 0) {
                    throw new InsufficientStockException(
                        $product->id,
                        abs($data['qty'] ?? 0),
                        $stock->productStock
                    );
                }

                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                    'productStock' => $newQuantity,
                ]);
            } else {
                Stock::create($data + [
                    'product_id' => $product->id,
                    'productStock' => $data['qty'] ?? 0,
                    'expire_date' => $data['expire_date'] ?? null,
                    'business_id' => $businessId,
                ]);
            }

            return $product->fresh();
        });
    }

    /**
     * Delete a product and its images.
     *
     * @param Product $product
     * @return bool
     * @throws \Exception
     */
    public function deleteProduct(Product $product): bool
    {
        foreach ($product->images ?? [] as $image) {
            if (Storage::exists($image)) {
                Storage::delete($image);
            }
        }

        return $product->delete();
    }

    /**
     * Get products with stocks.
     *
     * @param array $filters
     * @param int $businessId
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getProductsWithStock(array $filters, int $businessId, int $perPage = 10)
    {
        $query = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'productStock')
            ->with([
                'product.tax:id,rate,name',
                'product:id,productName,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,productCode',
            ])
            ->where('business_id', $businessId)
            ->latest();

        if (isset($filters['search'])) {
            $term = '%'.$filters['search'].'%';
            $query->where(function ($subQuery) use ($term) {
                $subQuery->where('batch_no', 'like', $term)
                    ->orWhereHas('product', function ($query) use ($term) {
                        $query->where('productName', 'like', $term)
                            ->orWhere('productCode', 'like', $term);
                    });
            });
        }

        if (isset($filters['check_stock']) && $filters['check_stock'] == 'true') {
            $query->where('productStock', '>', 0);
        }

        return $query->paginate($perPage);
    }
}