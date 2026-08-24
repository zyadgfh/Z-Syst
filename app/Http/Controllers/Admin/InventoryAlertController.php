<?php

namespace App\Http\Controllers\Admin;

use App\Events\InventoryAlertCreated;
use App\Http\Controllers\Controller;
use App\Models\InventoryAlert;
use App\Services\FirebasePushService;
use Illuminate\Http\Request;

class InventoryAlertController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryAlert::with(['product.category', 'product.manufacturer']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->boolean('acknowledged', false)) {
            $query->where('acknowledged', true);
        } else {
            $query->where('acknowledged', false);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('productName', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $alerts = $query->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('created_at')
            ->paginate(20);

        // Stats
        $stats = [
            'total'     => InventoryAlert::unacknowledged()->count(),
            'critical'  => InventoryAlert::unacknowledged()->where('severity', 'critical')->count(),
            'warning'   => InventoryAlert::unacknowledged()->where('severity', 'warning')->count(),
            'info'      => InventoryAlert::unacknowledged()->where('severity', 'info')->count(),
            'low_stock' => InventoryAlert::unacknowledged()->where('type', 'low_stock')->count(),
            'out_of_stock' => InventoryAlert::unacknowledged()->where('type', 'out_of_stock')->count(),
            'expiring'  => InventoryAlert::unacknowledged()->whereIn('type', ['expiring_soon', 'expired'])->count(),
        ];

        return view('admin.inventory-alerts.index', compact('alerts', 'stats'));
    }

    public function acknowledge(InventoryAlert $alert)
    {
        $alert->acknowledge(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الاستلام',
        ]);
    }

    public function acknowledgeAll()
    {
        InventoryAlert::unacknowledged()->update([
            'acknowledged'    => true,
            'acknowledged_at' => now(),
            'acknowledged_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد جميع التنبيهات',
        ]);
    }

    public function runScan()
    {
        $businessId = auth()->user()->business_id;
        $created = InventoryAlert::runInventoryScan($businessId);

        // Broadcast critical alerts in real-time
        $criticalAlerts = InventoryAlert::unacknowledged()
            ->where('severity', 'critical')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->get();

        $pushService = new FirebasePushService();

        foreach ($criticalAlerts as $alert) {
            broadcast(new InventoryAlertCreated($alert));

            // Send FCM push notification for critical alerts
            $pushService->sendInventoryAlert($alert);
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'message' => "تم الفحص — {$created} تنبيه جديد",
        ]);
    }

    /**
     * Get real-time bell data (count + recent alerts).
     */
    public function bellData()
    {
        $businessId = auth()->user()->business_id;

        $count = InventoryAlert::where('business_id', $businessId)
            ->unacknowledged()
            ->count();

        $recent = InventoryAlert::where('business_id', $businessId)
            ->unacknowledged()
            ->with('product:id,productName')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'type', 'severity', 'message', 'created_at', 'product_id']);

        return response()->json([
            'count'  => $count,
            'alerts' => $recent,
        ]);
    }

    /**
     * Acknowledge a single alert (AJAX).
     */
    public function acknowledgeAlert(InventoryAlert $alert)
    {
        $alert->acknowledge(auth()->id());

        return response()->json([
            'success' => true,
            'count'   => InventoryAlert::where('business_id', auth()->user()->business_id)->unacknowledged()->count(),
        ]);
    }

    /**
     * Get chart data for the dashboard widget.
     */
    public function chartData()
    {
        $businessId = auth()->user()->business_id;

        // Alerts by type over last 30 days
        $byType = InventoryAlert::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("type, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type');

        // Alerts by severity
        $bySeverity = InventoryAlert::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("severity, COUNT(*) as count")
            ->groupBy('severity')
            ->pluck('count', 'severity');

        // Daily trend
        $dailyTrend = InventoryAlert::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date');

        return response()->json([
            'byType'      => $byType,
            'bySeverity'  => $bySeverity,
            'dailyTrend'  => $dailyTrend,
        ]);
    }
}
