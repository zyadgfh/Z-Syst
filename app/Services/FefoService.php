<?php

namespace App\Services;

use App\Models\FefoLog;
use App\Models\FefoSetting;
use App\Models\Product;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FefoService
{
    /**
     * Get the best batch(es) for a product based on FEFO (nearest expiry first).
     */
    public function getBestBatches(int $productId, int $quantityNeeded, int $businessId): Collection
    {
        $settings = FefoSetting::getForBusiness($businessId);

        if (! $settings->fefo_enabled) {
            // Fallback: just return any available stock
            return Stock::where('product_id', $productId)
                ->where('business_id', $businessId)
                ->where('productStock', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('expire_date')
                        ->orWhere('expire_date', '>=', now()->startOfDay());
                })
                ->orderBy('id', 'asc')
                ->take(10)
                ->get();
        }

        // Get all stock batches with quantity > 0, ordered by expire_date ASC (FEFO)
        $batches = Stock::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', now()->startOfDay());
            })
            ->orderBy('expire_date', 'asc')
            ->orderBy('id', 'asc') // for same expiry dates, older stock first
            ->get();

        $selectedBatches = collect();
        $remainingNeeded = $quantityNeeded;

        foreach ($batches as $batch) {
            if ($remainingNeeded <= 0) {
                break;
            }

            $takeFromBatch = min($batch->productStock, $remainingNeeded);
            $batch->allocated_quantity = $takeFromBatch;
            $selectedBatches->push($batch);

            $remainingNeeded -= $takeFromBatch;
        }

        return $selectedBatches;
    }

    /**
     * Deduct stock from multiple batches following FEFO principle.
     * Returns array of deduction details.
     *
     * @param  array  $context  ['sale_id' => ?, 'notes' => ?]
     *
     * @throws \RuntimeException if insufficient stock
     */
    public function deductFefo(int $productId, int $totalQuantity, int $businessId, array $context = []): array
    {
        $batches = $this->getBestBatches($productId, $totalQuantity, $businessId);
        $totalAvailable = $batches->sum('productStock');

        if ($totalAvailable < $totalQuantity) {
            throw new \RuntimeException("Insufficient stock for product ID {$productId}. Available: {$totalAvailable}, Requested: {$totalQuantity}");
        }

        $deductions = [];
        $remainingToDeduct = $totalQuantity;

        DB::transaction(function () use ($batches, &$remainingToDeduct, $businessId, $productId, $context, &$deductions) {
            foreach ($batches as $batch) {
                if ($remainingToDeduct <= 0) {
                    break;
                }

                $deductQty = min($batch->productStock, $remainingToDeduct);
                $batch->decrement('productStock', $deductQty);
                $batch->refresh();

                $deductionRecord = [
                    'stock_id' => $batch->id,
                    'batch_no' => $batch->batch_no,
                    'expire_date' => $batch->expire_date,
                    'quantity_deducted' => $deductQty,
                    'quantity_remaining_after' => $batch->productStock,
                ];

                $deductions[] = $deductionRecord;

                // Log the FEFO deduction
                $this->logFefoDeduction(
                    businessId: $businessId,
                    productId: $productId,
                    stockId: $batch->id,
                    batchNo: $batch->batch_no,
                    expireDate: $batch->expire_date,
                    quantityDeducted: $deductQty,
                    quantityRemaining: $batch->productStock,
                    saleId: $context['sale_id'] ?? null,
                    saleDetailId: $context['sale_detail_id'] ?? null,
                    notes: $context['notes'] ?? 'FEFO automatic deduction during sale'
                );

                $remainingToDeduct -= $deductQty;
            }
        });

        return $deductions;
    }

    /**
     * Calculate FEFO priority score for a batch.
     * Lower score = should be sold first.
     */
    public function calculateFefoScore(Stock $stock): float
    {
        $score = 0.0;

        // Primary factor: days until expiry (closer = higher priority)
        if ($stock->expire_date) {
            $daysUntilExpiry = now()->startOfDay()->diffInDays($stock->expire_date, false);
            if ($daysUntilExpiry < 0) {
                // Expired: lowest priority (shouldn't be sold)
                $score += 100000;
            } else {
                // Normalize: 0 days = 1000 points, 365+ days = 0 points
                $expiryScore = max(0, (365 - $daysUntilExpiry) / 365 * 1000);
                $score += $expiryScore;
            }
        } else {
            // No expiry date: lowest priority among non-expired
            $score += 500;
        }

        // Secondary factor: older stock first (by created_at)
        $score += $stock->created_at ? max(0, (100 - now()->diffInDays($stock->created_at)) / 100 * 100) : 0;

        return $score;
    }

    /**
     * Get all batches for a product sorted by FEFO priority.
     */
    public function getFefoSortedBatches(int $productId, int $businessId, bool $includeExpired = false): Collection
    {
        $query = Stock::where('product_id', $productId)
            ->where('business_id', $businessId)
            ->where('productStock', '>', 0);

        if (! $includeExpired) {
            $query->where(function ($q) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>=', now()->startOfDay());
            });
        }

        $batches = $query->get();

        // Sort by FEFO priority (nearest expiry first)
        return $batches->sortBy(function ($batch) {
            return $this->calculateFefoScore($batch);
        })->values();
    }

    /**
     * Validate if a batch can be used for sale (not expired).
     */
    public function isBatchValidForSale(Stock $stock): bool
    {
        if ($stock->productStock <= 0) {
            return false;
        }

        if ($stock->expire_date && $stock->expire_date < now()->startOfDay()) {
            return false;
        }

        return true;
    }

    /**
     * Get FEFO statistics for a business.
     */
    public function getFefoStatistics(int $businessId): array
    {
        $today = now()->startOfDay();

        $stocks = Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->with('product:id,productName')
            ->get();

        $stats = [
            'total_batches' => $stocks->count(),
            'total_stock_qty' => $stocks->sum('productStock'),
            'expired_batches' => 0,
            'expired_qty' => 0,
            'expiring_within_30_days' => 0,
            'expiring_30_qty' => 0,
            'expiring_within_7_days' => 0,
            'expiring_7_qty' => 0,
            'no_expiry_batches' => 0,
            'no_expiry_qty' => 0,
            'priority_products' => [],
        ];

        foreach ($stocks as $stock) {
            $expireDate = $stock->expire_date ? Carbon::parse($stock->expire_date)->startOfDay() : null;
            if (! $expireDate) {
                $stats['no_expiry_batches']++;
                $stats['no_expiry_qty'] += $stock->productStock;

                continue;
            }

            $daysRemaining = $today->diffInDays($expireDate, false);

            if ($daysRemaining < 0) {
                $stats['expired_batches']++;
                $stats['expired_qty'] += $stock->productStock;
            } elseif ($daysRemaining <= 7) {
                $stats['expiring_within_7_days']++;
                $stats['expiring_7_qty'] += $stock->productStock;
                $stats['expiring_within_30_days']++;
                $stats['expiring_30_qty'] += $stock->productStock;
            } elseif ($daysRemaining <= 30) {
                $stats['expiring_within_30_days']++;
                $stats['expiring_30_qty'] += $stock->productStock;
            }
        }

        // Top 10 products needing FEFO attention (most near-expiry qty)
        $stats['priority_products'] = $stocks->groupBy('product_id')
            ->map(function ($productStocks) {
                $product = $productStocks->first()->product;
                $nearExpiryQty = $productStocks->filter(function ($s) {
                    $expireDate = $s->expire_date ? Carbon::parse($s->expire_date)->startOfDay() : null;

                    return $expireDate && now()->startOfDay()->diffInDays($expireDate, false) <= 30;
                })->sum('productStock');

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->productName,
                    'total_stock' => $productStocks->sum('productStock'),
                    'near_expiry_qty' => $nearExpiryQty,
                ];
            })
            ->sortByDesc('near_expiry_qty')
            ->take(10)
            ->values()
            ->toArray();

        return $stats;
    }

    /**
     * Log a FEFO deduction event.
     */
    public function logFefoDeduction(
        int $businessId,
        int $productId,
        int $stockId,
        ?string $batchNo,
        ?string $expireDate,
        int $quantityDeducted,
        int $quantityRemaining,
        ?int $saleId = null,
        ?int $saleDetailId = null,
        string $notes = 'FEFO automatic deduction'
    ): void {
        try {
            FefoLog::create([
                'business_id' => $businessId,
                'product_id' => $productId,
                'stock_id' => $stockId,
                'sale_id' => $saleId,
                'sale_detail_id' => $saleDetailId,
                'batch_no' => $batchNo,
                'expire_date' => $expireDate,
                'quantity_deducted' => $quantityDeducted,
                'quantity_remaining_after' => $quantityRemaining,
                'action_type' => 'sale_deduction',
                'notes' => $notes,
                'meta' => [
                    'deduction_time' => now()->toDateTimeString(),
                    'deduction_source' => 'FEFO Service',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("FEFO log error: {$e->getMessage()}", [
                'business_id' => $businessId,
                'product_id' => $productId,
                'stock_id' => $stockId,
            ]);
        }
    }

    /**
     * Check if a batch is expiring soon (within grace period).
     */
    public function isExpiringSoon(Stock $stock, int $businessId): bool
    {
        if (! $stock->expire_date) {
            return false;
        }

        $settings = FefoSetting::getForBusiness($businessId);
        $graceDays = $settings->expiry_grace_days;

        $expireDate = Carbon::parse($stock->expire_date)->startOfDay();
        $threshold = now()->startOfDay()->addDays($graceDays);

        return $expireDate <= $threshold && $expireDate >= now()->startOfDay();
    }

    /**
     * Get FEFO suggestions for a sale: batch allocation recommendation.
     *
     * @param  array  $cartItems  [['product_id' => 1, 'quantity' => 5], ...]
     */
    public function getSaleSuggestions(array $cartItems, int $businessId): array
    {
        $suggestions = [];

        foreach ($cartItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'] ?? 1;

            $batches = $this->getBestBatches($productId, $quantity, $businessId);
            $totalAvailable = $batches->sum('productStock');

            $suggestions[] = [
                'product_id' => $productId,
                'requested_qty' => $quantity,
                'available_qty' => $totalAvailable,
                'sufficient' => $totalAvailable >= $quantity,
                'batches' => $batches->map(function ($batch) {
                    return [
                        'stock_id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'expire_date' => $batch->expire_date,
                        'available_qty' => $batch->productStock,
                        'allocated_qty' => $batch->allocated_quantity ?? 0,
                        'fefo_priority' => $this->calculateFefoScore($batch),
                    ];
                }),
            ];
        }

        return $suggestions;
    }

    /**
     * Auto-deduct expired stock (soft removal - set to 0 with logging).
     *
     * @return int Number of batches affected
     */
    public function autoRemoveExpiredStock(int $businessId): int
    {
        $settings = FefoSetting::getForBusiness($businessId);
        if (! $settings->auto_deduct_expired_stock) {
            return 0;
        }

        $expiredStocks = Stock::where('business_id', $businessId)
            ->where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->where('expire_date', '<', now()->startOfDay())
            ->get();

        $count = 0;
        foreach ($expiredStocks as $stock) {
            $qty = $stock->productStock;
            $stock->update(['productStock' => 0]);

            $this->logFefoDeduction(
                businessId: $businessId,
                productId: $stock->product_id,
                stockId: $stock->id,
                batchNo: $stock->batch_no,
                expireDate: $stock->expire_date,
                quantityDeducted: $qty,
                quantityRemaining: 0,
                notes: 'Auto-removal of expired stock'
            );

            $count++;
        }

        return $count;
    }
}
