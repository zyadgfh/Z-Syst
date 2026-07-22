<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductPriceRequest;
use App\Http\Requests\UpdateProductPriceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductPriceController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        $prices = ProductPrice::where('product_id', $product->id)
            ->with(['variant'])
            ->when($request->filled('tier_name'), fn ($q) => $q->where('tier_name', $request->tier_name))
            ->when($request->filled('include_expired'), fn ($q) => $q, fn ($q) => $q->active())
            ->orderBy('min_quantity', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => ProductPriceResource::collection($prices),
        ]);
    }

    public function store(StoreProductPriceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');
        $data['created_by'] = $request->user()->id;

        $price = ProductPrice::create($data);

        return response()->json([
            'success' => true,
            'message' => __('Data saved successfully.'),
            'data' => new ProductPriceResource($price),
        ], 201);
    }

    public function update(UpdateProductPriceRequest $request, ProductPrice $productPrice): JsonResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $productPrice->update($data);

        return response()->json([
            'success' => true,
            'message' => __('Data updated successfully.'),
            'data' => new ProductPriceResource($productPrice->fresh()),
        ]);
    }

    public function destroy(ProductPrice $productPrice): JsonResponse
    {
        $productPrice->delete();

        return response()->json([
            'success' => true,
            'message' => __('Data deleted successfully.'),
        ]);
    }

    /**
     * Get best price for a product based on quantity.
     */
    public function bestPrice(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric|min:1',
        ]);

        $price = ProductPrice::getBestPrice(
            $request->product_id,
            (float) $request->quantity,
            $request->variant_id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'price' => $price,
            ],
        ]);
    }
}

