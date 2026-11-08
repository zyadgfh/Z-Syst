<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Minimal admin product management endpoints (route: admin.products.store)
 * guarded by the products-create permission.
 */
class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:products-create')->only('store');
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'productName' => 'required|string|max:190',
            'productCode' => 'nullable|string|max:60',
            'category_id' => 'nullable|integer',
            'unit_id' => 'nullable|integer',
            'purchase_without_tax' => 'nullable|numeric',
            'purchase_with_tax' => 'nullable|numeric',
            'sales_price' => 'nullable|numeric',
            'name' => 'nullable|string|max:190',
            'price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
        ]);

        $product = Product::create($request->only([
            'business_id',
            'productName',
            'productCode',
            'category_id',
            'unit_id',
            'purchase_without_tax',
            'purchase_with_tax',
            'sales_price',
            'name',
            'price',
            'stock',
        ]));

        return response()->json([
            'success' => true,
            'message' => __('Product created successfully.'),
            'data' => $product,
        ], 201);
    }
}
