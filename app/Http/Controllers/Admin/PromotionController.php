<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:promotions-read')->only('index');
        $this->middleware('permission:promotions-create')->only(['store', 'toggle']);
        $this->middleware('permission:promotions-update')->only('update');
        $this->middleware('permission:promotions-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        return view('admin.promotions.index', [
            'promotions' => Promotion::forBusiness($businessId)->latest('id')->paginate(15),
            'products' => Product::where('business_id', $businessId)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('business_id', $businessId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['business_id'] = (int) $request->user()->business_id;
        Promotion::create($data);

        return back()->with('success', __('تم إنشاء العرض'));
    }

    public function update(Request $request, Promotion $promotion)
    {
        $promotion->update($this->validated($request));

        return back()->with('success', __('تم تحديث العرض'));
    }

    public function toggle(Request $request, Promotion $promotion)
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        return back()->with('success', $promotion->is_active ? __('العرض مفعّل') : __('العرض موقوف'));
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return back()->with('success', __('تم حذف العرض'));
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:percent,fixed,qty_bonus,near_expiry',
            'value' => 'nullable|numeric|min:0',
            'min_qty' => 'nullable|integer|min:1',
            'bonus_qty' => 'nullable|integer|min:0',
            'applies_to' => 'required|in:all,product,category',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'coupon_code' => 'nullable|string|max:40|unique:promotions,coupon_code',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'max_uses' => 'nullable|integer|min:1',
        ]);
    }
}
