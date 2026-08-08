<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FefoLog;
use App\Models\Product;
use App\Models\Stock;
use App\Services\FefoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FefoController extends Controller
{
    protected FefoService $fefoService;

    public function __construct(FefoService $fefoService)
    {
        $this->fefoService = $fefoService;
    }

    /**
     * Get FEFO suggestions for a product (batches sorted by nearest expiry).
     *
     * @return JsonResponse
     */
    public function suggestions(Request $request, int $productId)
    {
        $businessId = auth()->user()->business_id;
        $quantity = $request->input('quantity', 1);

        $product = Product::where('business_id', $businessId)->findOrFail($productId);

        $batches = $this->fefoService->getBestBatches($productId, (int) $quantity, $businessId);
        $totalAvailable = $batches->sum('productStock');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->productName,
                'requested_qty' => (int) $quantity,
                'available_qty' => $totalAvailable,
                'sufficient' => $totalAvailable >= (int) $quantity,
                'batches' => $batches->map(function ($batch) {
                    return [
                        'stock_id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'expire_date' => $batch->expire_date,
                        'available_qty' => $batch->productStock,
                        'allocated_qty' => $batch->allocated_quantity ?? 0,
                        'fefo_score' => $this->fefoService->calculateFefoScore($batch),
                        'is_expiring_soon' => $this->fefoService->isExpiringSoon($batch, auth()->user()->business_id),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get all batches for a product sorted by FEFO.
     *
     * @return JsonResponse
     */
    public function productBatches(Request $request, int $productId)
    {
        $businessId = auth()->user()->business_id;
        $includeExpired = $request->input('include_expired', false) === 'true';

        $product = Product::where('business_id', $businessId)->findOrFail($productId);

        $batches = $this->fefoService->getFefoSortedBatches($productId, $businessId, $includeExpired);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'product_id' => $product->id,
                'product_name' => $product->productName,
                'batches' => $batches->map(function ($batch) {
                    return [
                        'stock_id' => $batch->id,
                        'batch_no' => $batch->batch_no,
                        'expire_date' => $batch->expire_date,
                        'available_qty' => $batch->productStock,
                        'fefo_score' => $this->fefoService->calculateFefoScore($batch),
                        'is_valid' => $this->fefoService->isBatchValidForSale($batch),
                        'is_expiring_soon' => $this->fefoService->isExpiringSoon($batch, auth()->user()->business_id),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get FEFO sale suggestions for a full cart.
     *
     * @return JsonResponse
     */
    public function saleSuggestions(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
        ]);

        $businessId = auth()->user()->business_id;
        $suggestions = $this->fefoService->getSaleSuggestions($request->products, $businessId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $suggestions,
        ]);
    }

    /**
     * Get FEFO report (statistics and priority products).
     *
     * @return JsonResponse
     */
    public function report()
    {
        $businessId = auth()->user()->business_id;
        $stats = $this->fefoService->getFefoStatistics($businessId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $stats,
        ]);
    }

    /**
     * Get FEFO logs.
     *
     * @return JsonResponse
     */
    public function logs(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $logs = FefoLog::with(['product:id,productName', 'sale:id,invoiceNumber'])
            ->where('business_id', $businessId)
            ->when($request->input('product_id'), function ($q) use ($request) {
                $q->where('product_id', $request->input('product_id'));
            })
            ->when($request->input('from_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->input('from_date'));
            })
            ->when($request->input('to_date'), function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->input('to_date'));
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $logs,
        ]);
    }

    /**
     * Run auto-removal of expired stock.
     *
     * @return JsonResponse
     */
    public function removeExpired()
    {
        $businessId = auth()->user()->business_id;
        $count = $this->fefoService->autoRemoveExpiredStock($businessId);

        return response()->json([
            'message' => __('Expired stock removed successfully.'),
            'data' => [
                'batches_affected' => $count,
            ],
        ]);
    }
}
