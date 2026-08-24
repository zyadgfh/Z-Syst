<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;

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

        return view('landing::web.catalog.index', compact('products', 'categories'));
    }

    /**
     * Show a single product detail page.
     */
    public function show($id)
    {
        $product = Product::active()
            ->with(['category', 'manufacturer', 'medicine_type'])
            ->findOrFail($id);

        return view('landing::web.catalog.show', compact('product'));
    }
}
