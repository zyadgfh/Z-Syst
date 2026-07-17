<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductStoreRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\LegacyProductResource;
use App\Http\Resources\ProductCollection;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class AcnooProductController extends Controller
{
    protected ProductService $productService;

public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $filters = [
            'search' => request('search'),
            'barcode' => request('barcode'),
            'expire_date' => request('expire_date'),
            'expired' => request('expired'),
        ];

        $data = $this->productService->index($filters);

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => new ProductCollection($data),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductStoreRequest $request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');

        $product = $this->productService->store($data);

        return response()->json([
            'success' => true,
            'message' => __('Data saved successfully.'),
            'data' => new LegacyProductResource($product),
        ], 201);
    }

    public function show($id)
    {
        $data = Product::query()
                    ->with('unit:id,unitName', 'medicine_type:id,name', 'manufacterer:id,name', 'box_size:id,name', 'category:id,categoryName', 'stocks:id,expire_date,product_id,batch_no,productStock', 'tax:id,rate')
                    ->withSum('stocks', 'productStock')
                    ->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(ProductUpdateRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');

        $product = $this->productService->update($product, $data);

        return response()->json([
            'success' => true,
            'message' => __('Data updated successfully.'),
            'data' => new LegacyProductResource($product),
        ]);
    }

    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'batch_no' => 'nullable|string',
            'tax_type' => 'nullable|string',
            'expire_date' => 'nullable|string',
            'tax_id' => 'nullable|exists:taxes,id',
            'purchase_without_tax' => 'required|numeric',
            'purchase_with_tax' => 'required|numeric',
            'profit_percent' => 'nullable|numeric',
            'sales_price' => 'required|numeric',
            'wholesale_price' => 'required|numeric',
            'qty' => 'required|integer',
        ]);

        $product = Product::findOrFail($id);
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');

        $product = $this->productService->updateStock($product, $data);

        return response()->json([
            'success' => true,
            'message' => __('Stock updated successfully.'),
            'data' => new LegacyProductResource($product),
        ]);
    }

    public function destroy(Product $product)
    {
        $this->productService->delete($product);

        return response()->json([
            'success' => true,
            'message' => __('Data deleted successfully.'),
        ]);
    }

    public function stocksWithProduct()
    {
        $filters = [
            'search' => request('search'),
            'check_stock' => request('check_stock'),
        ];

        $data = $this->productService->stocksWithProduct($filters);

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
