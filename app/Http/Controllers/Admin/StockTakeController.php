<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockAudit;
use App\Services\StockTakeService;
use Illuminate\Http\Request;

class StockTakeController extends Controller
{
    public function __construct(protected StockTakeService $takes)
    {
        $this->middleware('permission:stocktake-read')->only(['index', 'show']);
        $this->middleware('permission:stocktake-create')->only(['store', 'apply']);
    }

    public function index(Request $request)
    {
        return view('admin.stocktake.index', [
            'audits' => StockAudit::forBusiness((int) $request->user()->business_id)->latest('id')->paginate(12),
        ]);
    }

    public function store(Request $request)
    {
        $audit = $this->takes->open((int) $request->user()->business_id, $request->user());

        return redirect()->route('admin.stocktake.show', $audit)->with('success', __('بدأت جلسة جرد — امسح الأصناف من الجهاز أو من هنا.'));
    }

    public function show(Request $request, StockAudit $audit)
    {
        $details = $audit->details()->with('product:id,product_name,product_code')->orderBy('id')->paginate(25);

        return view('admin.stocktake.show', [
            'audit' => $audit,
            'details' => $details,
            'summary' => [
                'lines' => $audit->details()->count(),
                'variance_lines' => $audit->details()->where('variance', '!=', 0)->count(),
                'counted' => $audit->details()->where('physical_quantity', '>', 0)->count(),
            ],
        ]);
    }

    /** Web-side scan (same handler the device API uses). */
    public function webScan(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:64']);
        $audit = StockAudit::forBusiness((int) $request->user()->business_id)
            ->where('status', 'in_progress')->latest('id')->first();
        if (!$audit) {
            return response()->json(['success' => false, 'message' => 'لا جلسة جرد مفتوحة'], 422);
        }
        $detail = $this->takes->scan($audit, $data['code']);
        if (!$detail) {
            return response()->json(['success' => false, 'message' => 'كود غير معروف'], 404);
        }

        return response()->json(['success' => true, 'beep' => true, 'data' => [
            'product_id' => $detail->product_id,
            'physical_quantity' => (int) $detail->physical_quantity,
            'variance' => (int) $detail->variance,
        ]]);
    }

    public function apply(Request $request, StockAudit $audit)
    {
        $result = $this->takes->apply($audit);

        return redirect()->route('admin.stocktake.show', $audit)
            ->with('success', __('تم تطبيق الجرد: ').$result['applied'].' '.__('سطر معدّل'));
    }
}
