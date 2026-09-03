<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\ComparisonHistory;
use App\Models\Product;

class CompareController extends Controller
{
    /**
     * Show comparison page for selected products.
     */
    public function index()
    {
        // Support share token URLs
        $shareToken = request('share');
        if ($shareToken) {
            $historyRecord = \App\Models\ComparisonHistory::findByShareToken($shareToken);
            if ($historyRecord) {
                return redirect()->route('compare.index', ['ids' => $historyRecord->product_ids]);
            }
            return redirect()->route('catalog.index')
                ->with('warning', 'رابط المشاركة غير صالح أو منتهي');
        }

        $ids = collect(request('ids', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(4)
            ->values();

        if ($ids->isEmpty()) {
            return redirect()->route('catalog.index')
                ->with('warning', 'يرجى تحديد منتجات للمقارنة');
        }

        $products = Product::active()
            ->whereIn('id', $ids)
            ->with(['category', 'manufacturer'])
            ->get();

        // Build comparison data
        $specs = $this->buildSpecs($products);

        // Save to comparison history
        ComparisonHistory::saveComparison(
            $ids->toArray(),
            auth()->id(),
            session()->getId()
        );

        return view('customer.compare.index', compact('products', 'specs'));
    }

    /**
     * Show comparison history for the current user.
     */
    public function history()
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        $history = ComparisonHistory::where(function ($q) use ($userId, $sessionId) {
            if ($userId) {
                $q->where('user_id', $userId);
            } else {
                $q->where('session_id', $sessionId);
            }
        })
        ->orderByDesc('last_viewed_at')
        ->limit(20)
        ->get();

        // Pre-load product data for each history record
        $historyWithProducts = $history->map(function ($record) {
            $products = Product::whereIn('id', $record->product_ids ?? [])
                ->with(['category'])
                ->get();

            return [
                'id'           => $record->id,
                'products'     => $products,
                'share_token'  => $record->share_token,
                'view_count'   => $record->view_count,
                'last_viewed'  => $record->last_viewed_at,
                'created_at'   => $record->created_at,
                'compare_url'  => route('compare.index', ['ids' => $record->product_ids ?? []]),
            ];
        });

        return view('customer.compare.history', ['history' => $historyWithProducts]);
    }

    /**
     * AJAX: add product to comparison (stored in session).
     */
    public function add(Product $product)
    {
        $compareIds = session('compare_ids', []);
        $compareIds[] = $product->id;
        $compareIds = array_unique(array_slice(array_values($compareIds), 0, 4));
        session(['compare_ids' => $compareIds]);

        return response()->json([
            'success' => true,
            'count'   => count($compareIds),
            'ids'     => $compareIds,
            'message' => 'تمت الإضافة للمقارنة (' . count($compareIds) . '/4)',
        ]);
    }

    public function remove(Product $product)
    {
        $compareIds = array_values(array_diff(session('compare_ids', []), [$product->id]));
        session(['compare_ids' => $compareIds]);

        return response()->json([
            'success' => true,
            'count'   => count($compareIds),
        ]);
    }

    public function clear()
    {
        session()->forget('compare_ids');
        return response()->json(['success' => true]);
    }

    public function count()
    {
        return response()->json(['count' => count(session('compare_ids', []))]);
    }

    protected function buildSpecs($products): array
    {
        if ($products->isEmpty()) return [];

        $allSpecs = [
            'dosage_form'            => 'الشكل الدوائي',
            'strength'               => 'التركيز',
            'active_ingredient'      => 'المادة الفعالة',
            'concentration'          => 'التركيز',
            'route_of_administration' => 'طريقة الإعطاء',
            'package_size'           => 'حجم العبوة',
            'package_unit'           => 'وحدة العبوة',
            'prescription_required'  => 'يتطلب وصفة',
            'controlled_item'        => 'مادة خاضعة للرقابة',
            'refrigerated'           => 'يحتاج تبريد',
            'sales_price'            => 'السعر',
            'wholesale_price'        => 'سعر الجملة',
            'dosage'                 => 'الجرعة',
            'storage_instructions'   => 'تعليمات التخزين',
        ];

        $specs = [];
        foreach ($allSpecs as $key => $label) {
            $values = [];
            foreach ($products as $product) {
                $value = $product->{$key} ?? null;

                if (in_array($key, ['prescription_required', 'controlled_item', 'refrigerated'])) {
                    $value = $value ? 'نعم' : 'لا';
                }

                $values[] = $value ? (string) $value : '—';
            }

            // Only include spec if at least one product has a value
            if (collect($values)->contains(fn ($v) => $v !== '—')) {
                $specs[$label] = $values;
            }
        }

        return $specs;
    }
}
