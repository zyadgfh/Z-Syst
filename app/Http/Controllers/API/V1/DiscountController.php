<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscountRequest;
use App\Http\Requests\UpdateDiscountRequest;
use App\Http\Resources\DiscountResource;
use App\Models\Discount;
use App\Services\DiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function __construct(
        protected DiscountService $discountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->search,
            'is_active' => $request->is_active,
            'type' => $request->type,
            'per_page' => $request->per_page ?? 15,
        ];

        $discounts = $this->discountService->list($filters);

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => DiscountResource::collection($discounts),
        ]);
    }

    public function store(StoreDiscountRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id ?? app('tenant.company_id');

        $discount = $this->discountService->create($data);

        return response()->json([
            'success' => true,
            'message' => __('Data saved successfully.'),
            'data' => new DiscountResource($discount),
        ], 201);
    }

    public function show(Discount $discount): JsonResponse
    {
        $discount->load(['products', 'categories']);

        return response()->json([
            'success' => true,
            'message' => __('Data fetched successfully.'),
            'data' => new DiscountResource($discount),
        ]);
    }

    public function update(UpdateDiscountRequest $request, Discount $discount): JsonResponse
    {
        $data = $request->validated();
        $discount = $this->discountService->update($discount, $data);

        return response()->json([
            'success' => true,
            'message' => __('Data updated successfully.'),
            'data' => new DiscountResource($discount),
        ]);
    }

    public function destroy(Discount $discount): JsonResponse
    {
        $this->discountService->delete($discount);

        return response()->json([
            'success' => true,
            'message' => __('Data deleted successfully.'),
        ]);
    }

    /**
     * Calculate applicable discount for a product.
     */
    public function calculateProductDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = \App\Models\Product::findOrFail($request->product_id);
        $discounts = $this->discountService->calculateProductDiscount(
            $product,
            (float) $request->price,
            (int) $request->quantity
        );

        return response()->json([
            'success' => true,
            'data' => $discounts,
        ]);
    }
}

