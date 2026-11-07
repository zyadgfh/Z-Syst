<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockAudit;
use App\Models\StockAuditDetail;
use App\Services\DeviceRegistryService;
use App\Services\StockTakeService;
use Illuminate\Http\Request;

/**
 * Device-facing stock take: authenticated devices (X-Device-Token) join the
 * open session, each scan increments the counted quantity. Permission gate:
 * staff token with stocktake-create (added to staff role).
 */
class DeviceStockTakeController extends Controller
{
    public function __construct(
        protected DeviceRegistryService $registry,
        protected StockTakeService $takes,
    ) {}

    /** POST /api/v1/stock-takes — open a session (or return the live one). */
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('stocktake-create')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $businessId = (int) $user->business_id;
        $live = StockAudit::forBusiness($businessId)->where('status', 'in_progress')->latest('id')->first();
        if ($live) {
            return response()->json(['success' => true, 'data' => $this->payload($live), 'existing' => true]);
        }

        $audit = $this->takes->open($businessId, $user, (string) $request->input('type', 'spot_check'));

        return response()->json(['success' => true, 'data' => $this->payload($audit)], 201);
    }

    /** GET /api/v1/stock-takes/current */
    public function current(Request $request)
    {
        $user = $request->user();
        $live = StockAudit::forBusiness((int) $user->business_id)->where('status', 'in_progress')->latest('id')->first();

        return response()->json(['success' => true, 'data' => $live ? $this->payload($live) : null]);
    }

    /** POST /api/v1/stock-takes/scan {code, qty} */
    public function scan(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->can('stocktake-create')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $data = $request->validate(['code' => 'required|string|max:64', 'qty' => 'nullable|integer|min:1']);
        $audit = StockAudit::forBusiness((int) $user->business_id)->where('status', 'in_progress')->latest('id')->first();
        if (!$audit) {
            return response()->json(['success' => false, 'message' => 'لا توجد جلسة جرد مفتوحة'], 422);
        }

        $detail = $this->takes->scan($audit, $data['code'], (int) ($data['qty'] ?? 1));
        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'كود غير معروف'], 404);
        }

        return response()->json([
            'success' => true,
            'beep' => true,
            'data' => [
                'product_id' => $detail->product_id,
                'physical_quantity' => (int) $detail->physical_quantity,
                'system_quantity' => (int) $detail->system_quantity,
                'variance' => (int) $detail->variance,
            ],
        ]);
    }

    protected function payload(StockAudit $audit): array
    {
        return [
            'id' => $audit->id,
            'audit_number' => $audit->audit_number,
            'status' => $audit->status,
            'lines' => $audit->details()->count(),
            'counted' => $audit->details()->where('physical_quantity', '>', 0)->count(),
        ];
    }
}
