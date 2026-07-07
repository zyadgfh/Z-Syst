<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Branch;
use App\Models\StockTransfer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    protected $companyId;
    protected $branchId;

    public function __construct()
    {
        $this->companyId = auth()->user()->company_id ?? null;
        $this->branchId = auth()->user()->branch_id ?? null;
    }

    /**
     * Get dashboard KPIs
     */
    public function getDashboardKPIs($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:kpis:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            $query = Sale::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $sales = $query->get();

            return [
                'total_sales' => $sales->count(),
                'total_revenue' => $sales->sum('total_amount'),
                'average_transaction_value' => $sales->count() > 0 ? $sales->avg('total_amount') : 0,
                'total_products_sold' => SaleItem::whereIn('sale_id', $sales->pluck('id'))->sum('quantity'),
                'total_stock_value' => $this->getTotalStockValue(),
                'low_stock_count' => $this->getLowStockCount(),
                'expiring_soon_count' => $this->getExpiringSoonCount(),
                'dead_stock_count' => $this->getDeadStockCount(),
            ];
        });
    }

    /**
     * Get sales trends over time
     */
    public function getSalesTrends($period = 'daily', $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:sales_trends:{$period}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($period, $startDate, $endDate) {
            $query = Sale::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

                $driver = DB::getDriverName();
            $groupBy = match($period) {
                'hourly' => $driver === 'sqlite' ? "strftime('%Y-%m-%d %H:00', created_at)" : "DATE_FORMAT(created_at, '%Y-%m-%d %H:00')",
                'daily' => $driver === 'sqlite' ? "date(created_at)" : "DATE(created_at)",
                'weekly' => $driver === 'sqlite' ? "strftime('%Y-%W', created_at)" : "DATE_FORMAT(created_at, '%Y-%u')",
                'monthly' => $driver === 'sqlite' ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')",
                default => $driver === 'sqlite' ? "date(created_at)" : "DATE(created_at)",
            };

            return $query->select([
                    DB::raw("{$groupBy} as date"),
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as average_transaction_value'),
                ])
                ->groupBy(DB::raw($groupBy))
                ->orderBy('date')
                ->get()
                ->toArray();
        });
    }

    /**
     * Get top selling products
     */
    public function getTopSellingProducts($limit = 10, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:top_products:{$limit}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($limit, $startDate, $endDate) {
            $query = SaleItem::select([
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(total) as total_revenue'),
                    DB::raw('COUNT(DISTINCT sale_id) as total_sales'),
                ])
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                    
                    if ($this->branchId) {
                        $q->where('branch_id', $this->branchId);
                    }
                })
                ->with('product')
                ->groupBy('product_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();

            return $query->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'sku' => $item->product->sku ?? null,
                    'total_quantity' => $item->total_quantity,
                    'total_revenue' => $item->total_revenue,
                    'total_sales' => $item->total_sales,
                ];
            })->toArray();
        });
    }

    /**
     * Get sales by category
     */
    public function getSalesByCategory($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:sales_by_category:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $query = SaleItem::select([
                    'products.product_category_id as category_id',
                    DB::raw('SUM(sale_items.quantity) as total_quantity'),
                    DB::raw('SUM(sale_items.total) as total_revenue'),
                ])
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->where('company_id', $this->companyId)
                        ->whereBetween('created_at', [$startDate, $endDate]);
                    
                    if ($this->branchId) {
                        $q->where('branch_id', $this->branchId);
                    }
                })
                ->whereNotNull('products.product_category_id')
                ->groupBy('products.product_category_id')
                ->get();

            return $query->map(function ($item) {
                $category = \App\Models\ProductCategory::find($item->category_id);
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $category->name ?? 'Uncategorized',
                    'total_quantity' => $item->total_quantity,
                    'total_revenue' => $item->total_revenue,
                ];
            });
        });
    }

    /**
     * Get sales by branch
     */
    public function getSalesByBranch($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:sales_by_branch:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            return Sale::select([
                    'branch_id',
                    DB::raw('COUNT(*) as total_sales'),
                    DB::raw('SUM(total_amount) as total_revenue'),
                    DB::raw('AVG(total_amount) as average_transaction_value'),
                ])
                ->where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('branch_id')
                ->with('branch')
                ->orderBy('total_revenue', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'branch_id' => $item->branch_id,
                        'branch_name' => $item->branch->name ?? 'Unknown',
                        'total_sales' => $item->total_sales,
                        'total_revenue' => $item->total_revenue,
                        'average_transaction_value' => $item->average_transaction_value,
                    ];
                });
        });
    }

    /**
     * Get payment method breakdown
     */
    public function getPaymentMethodBreakdown($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:payment_methods:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $query = Sale::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            return $query->select([
                    'payment_method',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(total_amount) as total_amount'),
                ])
                ->groupBy('payment_method')
                ->get()
                ->map(function ($item) {
                    return [
                        'payment_method' => $item->payment_method,
                        'count' => $item->count,
                        'total_amount' => $item->total_amount,
                        'percentage' => $item->count > 0 ? round(($item->count / $this->getTotalSalesCount($startDate, $endDate)) * 100, 2) : 0,
                    ];
                });
        });
    }

    /**
     * Get total stock value
     */
    protected function getTotalStockValue()
    {
        $query = ProductStock::where('company_id', $this->companyId)
            ->where('is_active', true);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->get()->sum(function ($stock) {
            return $stock->quantity * ($stock->product->cost_price ?? 0);
        });
    }

    /**
     * Get low stock count
     */
    protected function getLowStockCount()
    {
        $query = ProductStock::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->get()->filter(function ($stock) {
            return $stock->quantity <= $stock->reorder_level;
        })->count();
    }

    /**
     * Get expiring soon count (within 30 days)
     */
    protected function getExpiringSoonCount()
    {
        $thirtyDaysFromNow = Carbon::now()->addDays(30);

        $query = ProductStock::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->whereBetween('expiry_date', [Carbon::now(), $thirtyDaysFromNow]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->count();
    }

    /**
     * Get dead stock count (no sales in 90 days)
     */
    protected function getDeadStockCount()
    {
        $ninetyDaysAgo = Carbon::now()->subDays(90);

        $soldProductIds = SaleItem::whereHas('sale', function ($q) use ($ninetyDaysAgo) {
            $q->where('company_id', $this->companyId)
                ->where('created_at', '>=', $ninetyDaysAgo);
            
            if ($this->branchId) {
                $q->where('branch_id', $this->branchId);
            }
        })->pluck('product_id')->toArray();

        $query = ProductStock::where('company_id', $this->companyId)
            ->where('is_active', true)
            ->where('quantity', '>', 0)
            ->whereNotIn('product_id', $soldProductIds);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->count();
    }

    /**
     * Get total sales count for percentage calculations
     */
    protected function getTotalSalesCount($startDate, $endDate)
    {
        $query = Sale::where('company_id', $this->companyId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($this->branchId) {
            $query->where('branch_id', $this->branchId);
        }

        return $query->count();
    }

    /**
     * Clear analytics cache for company
     */
    public function clearCache()
    {
        $pattern = "company:{$this->companyId}:*";
        Cache::forget($pattern);
    }

    /**
     * Get inventory summary
     */
    public function getInventorySummary()
    {
        $cacheKey = "company:{$this->companyId}:inventory_summary";
        
        return Cache::remember($cacheKey, 300, function () {
            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true);

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $stocks = $query->get();

            return [
                'total_products' => $stocks->pluck('product_id')->unique()->count(),
                'total_stock_items' => $stocks->count(),
                'total_quantity' => $stocks->sum('quantity'),
                'total_stock_value' => $stocks->sum(function ($stock) {
                    return $stock->quantity * ($stock->product->cost_price ?? 0);
                }),
                'total_retail_value' => $stocks->sum(function ($stock) {
                    return $stock->quantity * ($stock->product->retail_price ?? 0);
                }),
                'low_stock_count' => $stocks->filter(function ($stock) {
                    return $stock->quantity <= $stock->reorder_level;
                })->count(),
                'out_of_stock_count' => $stocks->where('quantity', 0)->count(),
                'overstock_count' => $stocks->filter(function ($stock) {
                    return $stock->quantity > $stock->reorder_quantity;
                })->count(),
            ];
        });
    }

    /**
     * Get low stock products
     */
    public function getLowStockProducts($limit = 20)
    {
        $cacheKey = "company:{$this->companyId}:low_stock:{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->with('product', 'branch');

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $stocks = $query->get()->filter(function ($stock) {
                return $stock->quantity <= $stock->reorder_level;
            });

            return $stocks->sortBy('quantity')
                ->take($limit)
                ->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name ?? 'Unknown',
                        'sku' => $stock->product->sku ?? null,
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'Unknown',
                        'current_quantity' => $stock->quantity,
                        'reorder_level' => $stock->reorder_level,
                        'reorder_quantity' => $stock->reorder_quantity,
                        'urgency' => $this->calculateReorderUrgency($stock),
                    ];
                })->values()->toArray();
        });
    }

    /**
     * Get out of stock products
     */
    public function getOutOfStockProducts($limit = 20)
    {
        $cacheKey = "company:{$this->companyId}:out_of_stock:{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($limit) {
            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->where('quantity', 0)
                ->with('product', 'branch');

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            return $query->orderBy('updated_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name ?? 'Unknown',
                        'sku' => $stock->product->sku ?? null,
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'Unknown',
                        'current_quantity' => $stock->quantity,
                        'last_updated' => $stock->updated_at,
                    ];
                })->toArray();
        });
    }

    /**
     * Get expiring products
     */
    public function getExpiringProducts($days = 30, $limit = 20)
    {
        $cacheKey = "company:{$this->companyId}:expiring:{$days}:{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($days, $limit) {
            $endDate = Carbon::now()->addDays($days);

            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->whereBetween('expiry_date', [Carbon::now(), $endDate])
                ->where('quantity', '>', 0)
                ->with('product', 'branch');

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            return $query->orderBy('expiry_date', 'asc')
                ->limit($limit)
                ->get()
                ->map(function ($stock) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name ?? 'Unknown',
                        'sku' => $stock->product->sku ?? null,
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'Unknown',
                        'batch_number' => $stock->batch_number,
                        'quantity' => $stock->quantity,
                        'expiry_date' => $stock->expiry_date,
                        'days_until_expiry' => Carbon::now()->diffInDays($stock->expiry_date, false),
                        'value' => $stock->quantity * ($stock->product->cost_price ?? 0),
                    ];
                })->toArray();
        });
    }

    /**
     * Get dead stock (no movement in specified days)
     */
    public function getDeadStock($days = 90, $limit = 20)
    {
        $cacheKey = "company:{$this->companyId}:dead_stock:{$days}:{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($days, $limit) {
            $cutoffDate = Carbon::now()->subDays($days);

            $soldProductIds = SaleItem::whereHas('sale', function ($q) use ($cutoffDate) {
                $q->where('company_id', $this->companyId)
                    ->where('created_at', '>=', $cutoffDate);
                
                if ($this->branchId) {
                    $q->where('branch_id', $this->branchId);
                }
            })->pluck('product_id')->toArray();

            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->whereNotIn('product_id', $soldProductIds)
                ->with('product', 'branch');

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            return $query->orderBy('quantity', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($stock) use ($days) {
                    return [
                        'product_id' => $stock->product_id,
                        'product_name' => $stock->product->name ?? 'Unknown',
                        'sku' => $stock->product->sku ?? null,
                        'branch_id' => $stock->branch_id,
                        'branch_name' => $stock->branch->name ?? 'Unknown',
                        'quantity' => $stock->quantity,
                        'value' => $stock->quantity * ($stock->product->cost_price ?? 0),
                        'days_without_movement' => $days,
                    ];
                });
        });
    }

    /**
     * Get fast-moving products
     */
    public function getFastMovingProducts($days = 30, $limit = 20)
    {
        $cacheKey = "company:{$this->companyId}:fast_moving:{$days}:{$limit}";
        
        return Cache::remember($cacheKey, 600, function () use ($days, $limit) {
            $startDate = Carbon::now()->subDays($days);

            $query = SaleItem::select([
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('COUNT(DISTINCT sale_id) as total_sales'),
                ])
                ->whereHas('sale', function ($q) use ($startDate) {
                    $q->where('company_id', $this->companyId)
                        ->where('created_at', '>=', $startDate);
                    
                    if ($this->branchId) {
                        $q->where('branch_id', $this->branchId);
                    }
                })
                ->with('product')
                ->groupBy('product_id')
                ->orderBy('total_quantity', 'desc')
                ->limit($limit)
                ->get();

            return $query->map(function ($item) use ($days) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'sku' => $item->product->sku ?? null,
                    'total_quantity' => $item->total_quantity,
                    'total_sales' => $item->total_sales,
                    'average_daily_sales' => round($item->total_quantity / $days, 2),
                ];
            });
        });
    }

    /**
     * Get stock turnover analysis
     */
    public function getStockTurnover($days = 90)
    {
        $cacheKey = "company:{$this->companyId}:stock_turnover:{$days}";
        
        return Cache::remember($cacheKey, 1800, function () use ($days) {
            $startDate = Carbon::now()->subDays($days);

            $query = ProductStock::where('company_id', $this->companyId)
                ->where('is_active', true)
                ->where('quantity', '>', 0)
                ->with('product');

            if ($this->branchId) {
                $query->where('branch_id', $this->branchId);
            }

            $stocks = $query->get();

            $soldQuantities = SaleItem::select([
                    'product_id',
                    DB::raw('SUM(quantity) as total_sold'),
                ])
                ->whereHas('sale', function ($q) use ($startDate) {
                    $q->where('company_id', $this->companyId)
                        ->where('created_at', '>=', $startDate);
                    
                    if ($this->branchId) {
                        $q->where('branch_id', $this->branchId);
                    }
                })
                ->groupBy('product_id')
                ->pluck('total_sold', 'product_id');

            return $stocks->map(function ($stock) use ($soldQuantities, $days) {
                $sold = $soldQuantities->get($stock->product_id, 0);
                $averageStock = $stock->quantity;
                $turnoverRate = $averageStock > 0 ? round(($sold / $averageStock) * (365 / $days), 2) : 0;
                $daysOfSupply = $sold > 0 ? round(($averageStock / $sold) * $days, 2) : 999;

                return [
                    'product_id' => $stock->product_id,
                    'product_name' => $stock->product->name ?? 'Unknown',
                    'current_stock' => $stock->quantity,
                    'sold_period' => $sold,
                    'turnover_rate' => $turnoverRate,
                    'days_of_supply' => $daysOfSupply,
                    'turnover_category' => $this->categorizeTurnover($turnoverRate),
                ];
            })->sortByDesc('turnover_rate')->values();
        });
    }

    /**
     * Calculate reorder urgency
     */
    protected function calculateReorderUrgency($stock)
    {
        if ($stock->quantity == 0) {
            return 'critical';
        } elseif ($stock->quantity <= $stock->reorder_level * 0.5) {
            return 'high';
        } elseif ($stock->quantity <= $stock->reorder_level) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Categorize turnover rate
     */
    protected function categorizeTurnover($rate)
    {
        if ($rate >= 12) {
            return 'fast';
        } elseif ($rate >= 6) {
            return 'moderate';
        } elseif ($rate >= 3) {
            return 'slow';
        } else {
            return 'dead';
        }
    }

    /**
     * Get stock transfer trends over time
     */
    public function getTransferTrends($period = 'daily', $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:transfer_trends:{$period}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($period, $startDate, $endDate) {
            $query = StockTransfer::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where(function ($q) {
                    $q->where('from_branch_id', $this->branchId)
                      ->orWhere('to_branch_id', $this->branchId);
                });
            }

            $groupBy = match($period) {
                'hourly' => DB::raw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00")'),
                'daily' => DB::raw('DATE(created_at)'),
                'weekly' => DB::raw('DATE_FORMAT(created_at, "%Y-%u")'),
                'monthly' => DB::raw('DATE_FORMAT(created_at, "%Y-%m")'),
                default => DB::raw('DATE(created_at)'),
            };

            return $query->select([
                    $groupBy . ' as date',
                    DB::raw('COUNT(*) as total_transfers'),
                    DB::raw('SUM(total_value) as total_value'),
                    DB::raw('SUM(total_quantity) as total_quantity'),
                    DB::raw('SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as completed_transfers'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_transfers'),
                ])
                ->groupBy($groupBy)
                ->orderBy('date')
                ->get();
        });
    }

    /**
     * Get transfers by branch
     */
    public function getTransfersByBranch($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:transfers_by_branch:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $query = StockTransfer::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where(function ($q) {
                    $q->where('from_branch_id', $this->branchId)
                      ->orWhere('to_branch_id', $this->branchId);
                });
            }

            return $query->select([
                    'from_branch_id',
                    'to_branch_id',
                    DB::raw('COUNT(*) as total_transfers'),
                    DB::raw('SUM(total_value) as total_value'),
                    DB::raw('SUM(total_quantity) as total_quantity'),
                    DB::raw('SUM(CASE WHEN status = "received" THEN 1 ELSE 0 END) as completed_transfers'),
                ])
                ->with(['fromBranch', 'toBranch'])
                ->groupBy('from_branch_id', 'to_branch_id')
                ->orderBy('total_value', 'desc')
                ->get()
                ->map(function ($item) {
                    return [
                        'from_branch_id' => $item->from_branch_id,
                        'from_branch_name' => $item->fromBranch->name ?? 'Unknown',
                        'to_branch_id' => $item->to_branch_id,
                        'to_branch_name' => $item->toBranch->name ?? 'Unknown',
                        'total_transfers' => $item->total_transfers,
                        'total_value' => $item->total_value,
                        'total_quantity' => $item->total_quantity,
                        'completed_transfers' => $item->completed_transfers,
                    ];
                });
        });
    }

    /**
     * Get most transferred products
     */
    public function getMostTransferredProducts($limit = 10, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:most_transferred:{$limit}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($limit, $startDate, $endDate) {
            $query = \App\Models\StockTransferItem::select([
                    'product_id',
                    DB::raw('SUM(quantity_requested) as total_quantity_requested'),
                    DB::raw('SUM(quantity_sent) as total_quantity_sent'),
                    DB::raw('SUM(quantity_received) as total_quantity_received'),
                    DB::raw('SUM(total_cost) as total_value'),
                    DB::raw('COUNT(DISTINCT stock_transfer_id) as total_transfers'),
                ])
                ->whereHas('stockTransfer', function ($q) use ($startDate, $endDate) {
                    $q->where('company_id', $this->companyId)
                      ->whereBetween('created_at', [$startDate, $endDate]);
                    
                    if ($this->branchId) {
                        $q->where(function ($query) {
                            $query->where('from_branch_id', $this->branchId)
                                  ->orWhere('to_branch_id', $this->branchId);
                        });
                    }
                })
                ->with('product')
                ->groupBy('product_id')
                ->orderBy('total_quantity_sent', 'desc')
                ->limit($limit)
                ->get();

            return $query->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Unknown',
                    'sku' => $item->product->sku ?? null,
                    'total_quantity_requested' => $item->total_quantity_requested,
                    'total_quantity_sent' => $item->total_quantity_sent,
                    'total_quantity_received' => $item->total_quantity_received,
                    'total_value' => $item->total_value,
                    'total_transfers' => $item->total_transfers,
                ];
            });
        });
    }

    /**
     * Get transfer performance metrics
     */
    public function getTransferPerformanceMetrics($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $cacheKey = "company:{$this->companyId}:transfer_performance:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $query = StockTransfer::where('company_id', $this->companyId)
                ->whereBetween('created_at', [$startDate, $endDate]);

            if ($this->branchId) {
                $query->where(function ($q) {
                    $q->where('from_branch_id', $this->branchId)
                      ->orWhere('to_branch_id', $this->branchId);
                });
            }

            $transfers = $query->get();

            $completedTransfers = $transfers->where('status', 'received');
            $pendingTransfers = $transfers->where('status', 'pending');
            $inTransitTransfers = $transfers->where('status', 'in_transit');

            // Calculate average completion time (from request to receive)
            $completionTimes = $completedTransfers->map(function ($transfer) {
                return $transfer->received_at->diffInHours($transfer->requested_at);
            });

            return [
                'total_transfers' => $transfers->count(),
                'completed_transfers' => $completedTransfers->count(),
                'pending_transfers' => $pendingTransfers->count(),
                'in_transit_transfers' => $inTransitTransfers->count(),
                'completion_rate' => $transfers->count() > 0 
                    ? round(($completedTransfers->count() / $transfers->count()) * 100, 2) 
                    : 0,
                'average_completion_time_hours' => $completionTimes->avg() ?: 0,
                'total_value_transferred' => $completedTransfers->sum('total_value'),
                'total_quantity_transferred' => $completedTransfers->sum('total_quantity'),
            ];
        });
    }
}