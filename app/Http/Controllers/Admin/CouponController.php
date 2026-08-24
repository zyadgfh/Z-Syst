<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CouponController extends Controller
{
    /**
     * Show the bulk code generator form.
     */
    public function bulkGenerateForm()
    {
        return view('admin.coupons.bulk-generate');
    }

    /**
     * Generate N random unique coupon codes with the same discount settings.
     */
    public function bulkGenerate(Request $request)
    {
        $request->validate([
            'count'                  => 'required|integer|min:1|max:5000',
            'prefix'                 => 'nullable|string|max:10',
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|numeric|min:0.01',
            'description'            => 'nullable|string|max:255',
            'minimum_order_amount'   => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'            => 'nullable|integer|min:1',
            'usage_limit_per_user'   => 'nullable|integer|min:1',
            'starts_at'              => 'nullable|date',
            'expires_at'             => 'nullable|date|after_or_equal:starts_at',
            'single_use'             => 'boolean',
            'code_length'            => 'nullable|integer|min:6|max:20',
        ]);

        $businessId = auth()->user()->business_id;
        $count      = $request->integer('count');
        $prefix     = strtoupper(trim($request->input('prefix', '')));
        $length     = $request->integer('code_length', 8);

        $codes      = [];
        $attempts   = 0;
        $maxAttempts = $count * 3;

        while (count($codes) < $count && $attempts < $maxAttempts) {
            $attempts++;
            $random = strtoupper(Str::random($length));
            $code   = $prefix ? $prefix . $random : $random;

            if (Coupon::where('code', $code)->exists() || in_array($code, $codes)) {
                continue;
            }

            $codes[] = $code;
        }

        $created = 0;
        foreach ($codes as $code) {
            Coupon::create([
                'business_id'             => $businessId,
                'code'                    => $code,
                'description'             => $request->input('description'),
                'type'                    => $request->input('type'),
                'value'                   => $request->input('value'),
                'minimum_order_amount'    => $request->input('minimum_order_amount', 0),
                'maximum_discount_amount' => $request->input('maximum_discount_amount'),
                'usage_limit'             => $request->input('usage_limit', 1),
                'usage_limit_per_user'    => $request->input('usage_limit_per_user', 1),
                'starts_at'               => $request->input('starts_at'),
                'expires_at'              => $request->input('expires_at'),
                'active'                  => true,
                'single_use'              => $request->boolean('single_use', true),
            ]);
            $created++;
        }

        // Get IDs of created coupons for the QR codes page
        $createdIds = Coupon::whereIn('code', $codes)->pluck('id')->toArray();

        return redirect()->route('admin.coupons.qr-codes', ['ids' => $createdIds])
            ->with('success', "تم إنشاء {$created} كوبون بنجاح");
    }
    public function index(Request $request)
    {
        $query = Coupon::withCount('usages');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'active'   => $query->where('active', true)->where('expires_at', '>', now()),
                'expired'  => $query->where('expires_at', '<', now()),
                'inactive' => $query->where('active', false),
                default    => null,
            };
        }

        $coupons = $query->latest()->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'                   => 'required|string|max:50|unique:coupons,code',
            'description'            => 'nullable|string|max:255',
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|numeric|min:0.01',
            'minimum_order_amount'   => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'            => 'nullable|integer|min:1',
            'usage_limit_per_user'   => 'nullable|integer|min:1',
            'starts_at'              => 'nullable|date',
            'expires_at'             => 'nullable|date|after_or_equal:starts_at',
            'single_use'             => 'boolean',
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['business_id'] = auth()->user()->business_id;

        Coupon::create($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'تم إنشاء الكوبون بنجاح');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $validated = $request->validate([
            'description'            => 'nullable|string|max:255',
            'type'                   => 'required|in:percentage,fixed',
            'value'                  => 'required|numeric|min:0.01',
            'minimum_order_amount'   => 'nullable|numeric|min:0',
            'maximum_discount_amount' => 'nullable|numeric|min:0',
            'usage_limit'            => 'nullable|integer|min:1',
            'usage_limit_per_user'   => 'nullable|integer|min:1',
            'starts_at'              => 'nullable|date',
            'expires_at'             => 'nullable|date|after_or_equal:starts_at',
            'active'                 => 'boolean',
            'single_use'             => 'boolean',
        ]);

        $coupon->update($validated);

        return redirect()->route('admin.coupons.index')
            ->with('success', 'تم تحديث الكوبون بنجاح');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons.index')
            ->with('success', 'تم حذف الكوبون بنجاح');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['active' => !$coupon->active]);

        return response()->json([
            'success' => true,
            'active'  => $coupon->active,
            'message' => $coupon->active ? 'تم تفعيل الكوبون' : 'تم تعطيل الكوبون',
        ]);
    }

    /**
     * Export codes as a text file.
     */
    public function exportCodes(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:coupons,id',
        ]);

        $codes = Coupon::whereIn('id', $request->ids)
            ->pluck('code')
            ->join("\n");

        return response($codes, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => 'attachment; filename="coupon_codes.txt"',
        ]);
    }

    /**
     * Export coupon codes as CSV with discount details.
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:coupons,id',
        ]);

        $coupons = Coupon::whereIn('id', $request->ids)->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="coupon_codes_' . now()->format('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($coupons) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($handle, [
                'الكود', 'النوع', 'القيمة', 'الحد الأدنى للطلب', 'حد الاستخدام',
                'حد الاستخدام/مستخدم', 'تاريخ البداية', 'تاريخ الانتهاء', 'استخدام واحد', 'الحالة',
            ]);

            foreach ($coupons as $coupon) {
                fputcsv($handle, [
                    $coupon->code,
                    $coupon->type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت',
                    $coupon->value,
                    $coupon->minimum_order_amount,
                    $coupon->usage_limit,
                    $coupon->usage_limit_per_user,
                    $coupon->starts_at?->format('Y-m-d H:i'),
                    $coupon->expires_at?->format('Y-m-d H:i'),
                    $coupon->single_use ? 'نعم' : 'لا',
                    $coupon->active ? 'نشط' : 'معطّل',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Show printable QR codes page for bulk-generated coupons.
     */
    public function qrCodes(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:coupons,id',
        ]);

        $coupons = Coupon::whereIn('id', $request->ids)
            ->orderBy('code')
            ->get();

        // Generate shareable URLs for each code
        $codesWithUrls = $coupons->map(function ($coupon) {
            return [
                'code'        => $coupon->code,
                'type'        => $coupon->type,
                'value'       => $coupon->value,
                'description' => $coupon->description,
                'expires_at'  => $coupon->expires_at?->format('Y-m-d'),
                'qr_url'      => 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($coupon->code),
            ];
        });

        return view('admin.coupons.qr-codes', ['codes' => $codesWithUrls]);
    }
}
