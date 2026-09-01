<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\CustomerOrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CatalogController extends Controller
{
    /**
     * Display a public product catalog for pharmacy items.
     */
    public function index()
    {
        $query = Product::active()
            ->where('archived', false)
            ->where('discontinued', false)
            ->with(['category', 'manufacturer', 'medicine_type'])
            ->select([
                'id', 'productName', 'scientific_name', 'commercial_name',
                'short_name', 'description', 'category_id', 'manufacturer_id',
                'type_id', 'dosage_form', 'strength', 'sales_price',
                'wholesale_price', 'prescription_required', 'controlled_item',
                'images', 'barcode', 'sku', 'productCode',
                'active_ingredient', 'concentration', 'dosage',
                'package_size', 'package_unit', 'refrigerated',
                'stock_status', 'created_at',
            ]);

        // Search
        if ($search = request('search')) {
            $query->search($search);
        }

        // Filter by category
        if ($categoryId = request('category')) {
            $query->byCategory($categoryId);
        }

        // Filter by stock status
        if ($status = request('status')) {
            $query->byStockStatus($status);
        }

        // Price range
        if ($request->filled('price_min')) {
            $query->where('sales_price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('sales_price', '<=', $request->input('price_max'));
        }

        // Prescription filter
        if ($request->boolean('prescription')) {
            $query->prescriptionRequired();
        }

        // Sort
        $sort = request('sort', 'newest');
        $query = match ($sort) {
            'price_low' => $query->orderBy('sales_price', 'asc'),
            'price_high' => $query->orderBy('sales_price', 'desc'),
            'name' => $query->orderBy('productName', 'asc'),
            default => $query->latest(),
        };

        $products = $query->paginate(12)->withQueryString();
        $categories = Category::where('status', 1)->orderBy('name')->get();

        // Get price range for filter UI
        $priceRange = Product::active()->selectRaw('MIN(sales_price) as min_price, MAX(sales_price) as max_price')->first();

        return view('landing::web.catalog.index', compact('products', 'categories', 'priceRange'));
    }

    /**
     * Show a single product detail page.
     */
    public function show($id)
    {
        $product = Product::active()
            ->with(['category', 'manufacturer', 'medicine_type'])
            ->findOrFail($id);

        // ── Recommendations ──
        // 1) Same category products (excluding current)
        $sameCategory = Product::active()
            ->where('id', '!=', $product->id)
            ->where('category_id', $product->category_id)
            ->orderByDesc('sales_price')
            ->limit(4)
            ->get();

        // 2) Products frequently bought together (via order items)
        $boughtTogetherIds = CustomerOrderItem::where('product_id', $product->id)
            ->pluck('customer_order_id')
            ->take(20);

        $boughtTogether = CustomerOrderItem::whereIn('customer_order_id', $boughtTogetherIds)
            ->where('product_id', '!=', $product->id)
            ->select('product_id', DB::raw('COUNT(*) as times_bought'))
            ->groupBy('product_id')
            ->orderByDesc('times_bought')
            ->take(8)
            ->pluck('product_id');

        $recommendedProducts = collect();

        if ($boughtTogether->isNotEmpty()) {
            $recommendedProducts = Product::active()
                ->whereIn('id', $boughtTogether)
                ->with(['category', 'manufacturer'])
                ->get()
                ->sortBy(fn ($p) => $boughtTogether->search($p->id))
                ->values();
        }

        // 3) Fill remaining slots with same-category products
        $excludeIds = $recommendedProducts->pluck('id')->push($product->id);
        $remaining = $sameCategory->reject(fn ($p) => $excludeIds->contains($p->id));
        $recommendedProducts = $recommendedProducts->concat($remaining)->take(8);

        return view('landing::web.catalog.show', compact('product', 'recommendedProducts'));
    }

    /**
     * AJAX autocomplete for product search.
     */
    public function autocomplete(): JsonResponse
    {
        $term = request('q', '');
        if (strlen($term) < 2) {
            return response()->json(['data' => []]);
        }

        $products = Product::active()
            ->where('archived', false)
            ->search($term)
            ->select('id', 'productName', 'scientific_name', 'sales_price', 'images', 'dosage_form', 'strength')
            ->limit(8)
            ->get()
            ->map(function ($p) {
                return [
                    'id'       => $p->id,
                    'name'     => $p->productName,
                    'subtitle' => collect([$p->scientific_name, $p->strength, $p->dosage_form])->filter()->implode(' · '),
                    'price'    => $p->sales_price ? number_format($p->sales_price, 2) : null,
                    'image'    => $p->images && is_array($p->images) && count($p->images) > 0 ? asset($p->images[0]) : null,
                    'url'      => route('catalog.show', $p->id),
                ];
            });

        return response()->json(['data' => $products]);
    }
}
