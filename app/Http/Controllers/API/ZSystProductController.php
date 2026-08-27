<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InsufficientStockException;
use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Products', description: 'Product management endpoints')]
class ZSystProductController extends Controller
{
    use HasUploader;

    public function __construct(
        private ProductService $productService
    ) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);
        
        $filters = $request->only(['search', 'expire_date', 'expired']);
        $perPage = $request->input('per_page', 10);
        
        $data = $this->productService->list($filters, auth()->user()->business_id, $perPage);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $this->authorize('create', Product::class);

        try {
            $data = $request->validated() + [
                'images' => $request->images ? $this->multipleUpload($request, 'images') : null,
            ];

            $product = $this->productService->createProduct($data, $request->user()->business_id);

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
        $product = clone(Product::findOrFail($id)); // Check existence
        $this->authorize('view', $product);

        $data = $this->productService->show($id, auth()->user()->business_id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $this->authorize('update', $product);

        try {
            $data = $request->validated() + [
                'images' => $request->images ? $this->multipleUpload($request, 'images') : [],
                'removed_images' => $request->removed_images ?? null,
                'qty' => $request->qty ?? 0,
                'batch_no' => $request->batch_no ?? null,
                'expire_date' => $request->expire_date ?? null,
            ];

            $updatedProduct = $this->productService->updateProduct($product, $data, $request->user()->business_id);

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
        $product = Product::findOrFail($id);
        $this->authorize('updateStock', $product);
        
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
        $this->authorize('delete', $product);
        
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
