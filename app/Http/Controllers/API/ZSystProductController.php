<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\StockNotFoundException;
use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ZSystProductController extends Controller
{
    use HasUploader, WithTransactionalOperations;

    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Product::select('id', 'productName', 'productCode', 'purchase_with_tax', 'sales_price')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where('productName', 'like', $term)
                    ->orWhere('productCode', 'like', $term);
            })
            ->when($request->filled('expire_date'), function ($query) use ($request) {
                $query->whereHas('stocks', function ($query) use ($request) {
                    $query->whereBetween('expire_date', [today(), $request->input('expire_date')]);
                });
            })
            ->when($request->input('expired') == 'true', function ($query) {
                $query->whereHas('stocks', function ($query) {
                    $query->where('expire_date', '<', today())
                        ->where('productStock', '>', 0);
                });
            })
            ->withSum('stocks', 'productStock')
            ->with(['expiringItem' => function ($query) {
                $query->select('expire_date', 'product_id')
                    ->where('productStock', '>', 0)
                    ->whereNotNull('expire_date');
            }])
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $business_id = auth()->user()->business_id;

        try {
            $validated = $request->validate([
                'productName' => 'required|string',
                'category_id' => 'required|integer|exists:categories,id',
                'type_id' => 'nullable|integer|exists:medicine_types,id',
                'unit_id' => 'nullable|integer|exists:units,id',
                'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
                'box_size_id' => 'nullable|integer|exists:box_sizes,id',
                'productCode' => [
                    'nullable',
                    Rule::unique('products')->where(function ($query) use ($business_id) {
                        return $query->where('business_id', $business_id);
                    }),
                ],
                'batch_no' => [
                    'nullable',
                    Rule::unique('stocks')->where(function ($query) use ($business_id) {
                        return $query->where('business_id', $business_id);
                    }),
                ],
            ]);

            $data = $validated + [
                'images' => $request->images ? $this->multipleUpload($request, 'images') : null,
            ];

            $product = $this->productService->createProduct($data, $business_id);

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $product,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('Validation failed.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Internal server error.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $data = Product::query()
            ->with('unit:id,unitName', 'medicine_type:id,name', 'manufacturer:id,name', 'box_size:id,name', 'category:id,categoryName', 'stocks:id,expire_date,product_id,batch_no,productStock', 'tax:id,rate')
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $business_id = auth()->user()->business_id;
        $stock = Stock::where('product_id', $product->id)->first();

        try {
            $validated = $request->validate([
                'productName' => 'required|string',
                'category_id' => 'required|integer|exists:categories,id',
                'type_id' => 'nullable|integer|exists:medicine_types,id',
                'unit_id' => 'nullable|integer|exists:units,id',
                'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
                'box_size_id' => 'nullable|integer|exists:box_sizes,id',
                'productCode' => [
                    'nullable',
                    'unique:products,productCode,'.$product->id.',id,business_id,'.$business_id,
                ],
                'batch_no' => [
                    'nullable',
                    'unique:stocks,batch_no,'.$stock->id.',id,business_id,'.$business_id,
                ],
            ]);

            $data = $validated + [
                'images' => $request->images ? $this->multipleUpload($request, 'images') : [],
                'removed_images' => $request->removed_images ?? null,
                'qty' => $request->qty ?? 0,
                'batch_no' => $request->batch_no ?? null,
                'expire_date' => $request->expire_date ?? null,
            ];

            $updatedProduct = $this->productService->updateProduct($product, $data, $business_id);

            return response()->json([
                'message' => __('Data updated successfully.'),
                'data' => $updatedProduct,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('Validation failed.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Internal server error.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateStock(Request $request, $id)
    {
        try {
            $validated = $request->validate([
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

            $data = $validated + [
                'batch_no' => $request->batch_no ?? null,
                'expire_date' => $request->expire_date ?? null,
                'qty' => $request->qty ?? 0,
            ];

            $product = $this->productService->updateStock($id, $data, auth()->user()->business_id);

            return response()->json([
                'message' => __('Stock updated successfully.'),
                'data' => $product,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => __('Validation failed.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (InsufficientStockException $e) {
            return response()->json([
                'message' => __('Insufficient stock available.'),
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Internal server error.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Product $product)
    {
        $this->productService->deleteProduct($product);

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }

    public function stocksWithProduct(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'check_stock' => $request->input('check_stock'),
        ];

        $data = $this->productService->getProductsWithStock(
            $filters,
            auth()->user()->business_id,
            $request->input('per_page', 10)
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
