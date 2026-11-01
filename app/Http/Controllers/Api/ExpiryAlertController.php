<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Services\FefoService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExpiryAlertController extends Controller
{
    protected FefoService $fefoService;

    public function __construct(FefoService $fefoService)
    {
        $this->fefoService = $fefoService;
    }

    /**
     * Get expiry alerts statistics (counts per category).
     */
    public function stats()
    {
        $businessId = auth()->user()->business_id;
        $today = now()->startOfDay();

        $stocks = Stock::where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->whereHas('product', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->with('product:id,productName,business_id')
            ->get();

        $stats = [
            'expired' => 0,
            'expiring_today' => 0,
            'expiring_7_days' => 0,
            'expiring_30_days' => 0,
            'expiring_60_days' => 0,
            'expiring_90_days' => 0,
            'expiring_365_days' => 0,
            'total' => 0,
        ];

        foreach ($stocks as $stock) {
            $expireDate = $stock->expire_date ? Carbon::parse($stock->expire_date)->startOfDay() : null;
            if (! $expireDate) {
                continue;
            }

            $daysRemaining = $today->diffInDays($expireDate, false);

            $stats['total']++;

            if ($daysRemaining < 0) {
                $stats['expired']++;
            } elseif ($daysRemaining == 0) {
                $stats['expiring_today']++;
            } elseif ($daysRemaining <= 7) {
                $stats['expiring_7_days']++;
            } elseif ($daysRemaining <= 30) {
                $stats['expiring_30_days']++;
            } elseif ($daysRemaining <= 60) {
                $stats['expiring_60_days']++;
            } elseif ($daysRemaining <= 90) {
                $stats['expiring_90_days']++;
            } else {
                $stats['expiring_365_days']++;
            }
        }

        // Add FEFO recommendation stats
        $fefoStats = $this->fefoService->getFefoStatistics($businessId);
        $stats['fefo_priority_products'] = $fefoStats['priority_products'];
        $stats['fefo_batches_no_expiry'] = $fefoStats['no_expiry_batches'];
        $stats['fefo_batches_no_expiry_qty'] = $fefoStats['no_expiry_qty'];

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $stats,
        ]);
    }

    /**
     * Get paginated list of expiring/expired products with FEFO sorting.
     */
    public function index(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $today = now()->startOfDay();

        $threshold = $request->threshold; // expired, today, 7, 30, 60, 90, 365, all

        $query = Stock::where('productStock', '>', 0)
            ->whereNotNull('expire_date')
            ->whereHas('product', function ($query) use ($businessId) {
                $query->where('business_id', $businessId);
            })
            ->with([
                'product:id,productName,productCode,sales_price,purchase_with_tax,business_id,category_id',
                'product.category:id,categoryName',
            ]);

        // Apply threshold filter
        switch ($threshold) {
            case 'expired':
                $query->where('expire_date', '<', $today);
                break;
            case 'today':
                $query->whereDate('expire_date', $today);
                break;
            case '7':
                $query->whereBetween('expire_date', [$today, $today->copy()->addDays(7)]);
                break;
            case '30':
                $query->whereBetween('expire_date', [$today, $today->copy()->addDays(30)]);
                break;
            case '60':
                $query->whereBetween('expire_date', [$today, $today->copy()->addDays(60)]);
                break;
            case '90':
                $query->whereBetween('expire_date', [$today, $today->copy()->addDays(90)]);
                break;
            case '365':
                $query->whereBetween('expire_date', [$today, $today->copy()->addDays(365)]);
                break;
            default:
                // all - get expired + expiring within 365 days
                $query->where(function ($q) use ($today) {
                    $q->where('expire_date', '<', $today)
                        ->orWhereBetween('expire_date', [$today, $today->copy()->addDays(365)]);
                });
                break;
        }

        // Search filter
        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_no', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($sub) use ($search) {
                        $sub->where('productName', 'like', "%{$search}%")
                            ->orWhere('productCode', 'like', "%{$search}%");
                    });
            });
        }

        // FEFO sorting: nearest expiry first
        $query->orderBy('expire_date', 'asc')
            ->orderBy('id', 'asc');

        $stocks = $query->paginate($request->per_page ?? 20);

        // Add computed days_remaining and severity to each item
        $stocks->getCollection()->transform(function ($stock) use ($today) {
            $expireDate = $stock->expire_date ? Carbon::parse($stock->expire_date)->startOfDay() : null;
            $stock->days_remaining = $expireDate ? $today->diffInDays($expireDate, false) : null;

            // Severity: critical=expired, high=<=7days, medium=<=30days, low=<=90days, info=<=365days
            if ($stock->days_remaining < 0) {
                $stock->severity = 'critical';
                $stock->severity_label = 'منتهي (Expired)';
            } elseif ($stock->days_remaining == 0) {
                $stock->severity = 'critical';
                $stock->severity_label = 'تنتهي اليوم (Expiring Today)';
            } elseif ($stock->days_remaining <= 7) {
                $stock->severity = 'high';
                $stock->severity_label = 'خلال 7 أيام (Within 7 days)';
            } elseif ($stock->days_remaining <= 30) {
                $stock->severity = 'medium';
                $stock->severity_label = 'خلال 30 يوماً (Within 30 days)';
            } elseif ($stock->days_remaining <= 60) {
                $stock->severity = 'medium';
                $stock->severity_label = 'خلال 60 يوماً (Within 60 days)';
            } elseif ($stock->days_remaining <= 90) {
                $stock->severity = 'low';
                $stock->severity_label = 'خلال 90 يوماً (Within 90 days)';
            } else {
                $stock->severity = 'info';
                $stock->severity_label = 'خلال 365 يوماً (Within 365 days)';
            }

            return $stock;
        });

        // Add FEFO recommendation for each expiring batch
        $stocks->getCollection()->transform(function ($stock) {
            $stock->fefo_recommendation = [
                'should_sell_first' => $stock->days_remaining !== null && $stock->days_remaining >= 0 && $stock->days_remaining <= 30,
                'priority' => $stock->days_remaining !== null && $stock->days_remaining < 0 ? 'do_not_sell' : ($stock->days_remaining <= 7 ? 'urgent' : ($stock->days_remaining <= 30 ? 'high' : 'normal')),
                'days_until_expiry' => $stock->days_remaining,
            ];

            return $stock;
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $stocks,
        ]);
    }
}
