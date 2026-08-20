<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\StockNotFoundException;
use App\Models\Product;
use App\Models\Stock;
use App\Services\Stock\StockAllocationService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductService
{
    use WithTransactionalOperations;

    public function __construct(
        private StockAllocationService $stockAllocationService
    ) {}

    public function list(array $filters, int $businessId, int $perPage = 10)
    {
        return Product::select('id', 'productName', 'productCode', 'purchase_with_tax', 'sales_price')
            ->where('business_id', $businessId)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $query->where('productName', 'like', $term)
                    ->orWhere('productCode', 'like', $term);
            })
            ->when(!empty($filters['expire_date']), function ($query) use ($filters) {
                $query->whereHas('stocks', function ($query) use ($filters) {
                    $query->whereBetween('expire_date', [today(), $filters['expire_date']]);
                });
            })
            ->when((isset($filters['expired']) && $filters['expired'] == 'true'), function ($query) {
                $query->whereHas('stocks', function ($query) {
                    $query->where('expire_date', '<', today())
                        ->where('productStock', '>', 0);
                });
            })
            ->withSum('stocks', 'productStock')
            ->with(['expiringItem' => function ($query) {
                $query->select('expire_date', 'product_id')
                    ->where('productStock', '>', 0)
                    ->whereNotNull('expire_date');
            }])
            ->latest()
            ->paginate($perPage);
    }

    public function show(int $id)
    {
        return Product::query()
            ->with('unit:id,unitName', 'medicine_type:id,name', 'manufacturer:id,name', 'box_size:id,name', 'category:id,categoryName', 'stocks:id,expire_date,product_id,batch_no,productStock', 'tax:id,rate')
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);
    }

    public function createProduct(array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $product = Product::create($data + [
                'business_id' => $businessId,
            ]);

            $stock = Stock::create($data + [
                'product_id' => $product->id,
                'business_id' => $businessId,
                'productStock' => 0, // Set to 0 initially
            ]);

            // Add stock using StockAllocationService
            $quantity = $data['qty'] ?? 0;
            if ($quantity > 0) {
                $this->stockAllocationService->addStock(
                    $stock,
                    $quantity,
                    Product::class,
                    $product->id,
                    auth()->id() ?? 0,
                    'Initial stock creation'
                );
            }

            return $product->fresh();
        });
    }

    public function updateProduct(Product $product, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($product, $data, $businessId) {
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

            $newImages = $data['images'] ?? [];
            $mergedImages = array_merge($prevImages, $newImages);

            $stock = Stock::where('product_id', $product->id)->first();
            $qtyToAdd = $data['qty'] ?? 0;

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                ]);

                if ($qtyToAdd > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyToAdd,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Added stock during product update'
                    );
                }
            } else {
                $stock = Stock::create($data + [
                    'product_id' => $product->id,
                    'business_id' => $businessId,
                    'productStock' => 0,
                    'batch_no' => $data['batch_no'] ?? null,
                    'expire_date' => $data['expire_date'] ?? null,
                ]);

                if ($qtyToAdd > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyToAdd,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Initial stock added during product update'
                    );
                }
            }

            $product->update($data + [
                'images' => $mergedImages,
            ]);

            return $product->fresh();
        });
    }

    public function updateStock(int $productId, array $data, int $businessId): Product
    {
        return $this->executeTransaction(function () use ($productId, $data, $businessId) {
            $product = Product::where('id', $productId)
                ->where('business_id', $businessId)
                ->firstOrFail();
            $product->update($data);

            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', $data['batch_no'] ?? null)
                ->first();

            $qtyAdjustment = $data['qty'] ?? 0;

            if ($stock) {
                $stock->update([
                    'batch_no' => $data['batch_no'] ?? $stock->batch_no,
                    'expire_date' => $data['expire_date'] ?? $stock->expire_date,
                ]);

                if ($qtyAdjustment > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyAdjustment,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock updated manually (addition)'
                    );
                } elseif ($qtyAdjustment < 0) {
                    $qtyToDeduct = abs($qtyAdjustment);
                if ($stock->productStock < $qtyToDeduct) {
                    throw new InsufficientStockException(
                        message: "Insufficient stock for {$product->productName}. Available: {$stock->productStock}, Requested: {$qtyToDeduct}",
                        errors: [
                            'product_id' => $product->id,
                            'available' => $stock->productStock,
                            'requested' => $qtyToDeduct,
                        ]
                    );
                }
                    $this->stockAllocationService->allocate(
                        $stock,
                        $qtyToDeduct,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock updated manually (deduction)'
                    );
                }
            } else {
                $stock = Stock::create($data + [
                    'product_id' => $product->id,
                    'productStock' => 0,
                    'expire_date' => $data['expire_date'] ?? null,
                    'business_id' => $businessId,
                ]);

                if ($qtyAdjustment > 0) {
                    $this->stockAllocationService->addStock(
                        $stock,
                        $qtyAdjustment,
                        Product::class,
                        $product->id,
                        auth()->id() ?? 0,
                        'Stock initialized manually'
                    );
                }
            }

            return $product->fresh();
        });
    }

    public function deleteProduct(Product $product): bool
    {
        foreach ($product->images ?? [] as $image) {
            if (Storage::exists($image)) {
                Storage::delete($image);
            }
        }

        return $product->delete();
    }

    public function getProductsWithStock(array $filters, int $businessId, int $perPage = 10)
    {
        $query = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'productStock')
            ->with([
                'product.tax:id,rate,name',
                'product:id,productName,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,productCode',
            ])
            ->where('business_id', $businessId)
            ->latest();

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
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