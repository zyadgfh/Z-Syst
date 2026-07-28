<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\FefoStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FefoStockController - FEFO (First Expiry, First Out) Stock Management Endpoints
 *
 * المسؤول عن عرض وإدارة المخزون بناءً على FEFO
 * يستخدم FefoStockService لتوفير معلومات دقيقة عن الدفعات وتواريخ الصلاحية
 */
class FefoStockController extends Controller
{
    public function __construct(
        private readonly FefoStockService $fefoStockService
    ) {}

    /**
     * Get FEFO priority list for a product at a branch.
     * Used by POS to display nearest-expiry batches first.
     *
     * GET /api/v1/fefo/priority/{productId}?branch_id=xxx
     */
    public function priority(Request $request, string $productId): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchId = $request->query('branch_id');

        $priorities = $this->fefoStockService->getFefoPriorityList($productId, $branchId);

        return response()->json([
            'success' => true,
            'data' => $priorities,
            'product_id' => $productId,
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Get complete stock overview for a product at a branch.
     *
     * GET /api/v1/fefo/overview/{productId}?branch_id=xxx
     */
    public function overview(Request $request, string $productId): JsonResponse
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $branchId = $request->query('branch_id');

        $overview = $this->fefoStockService->getStockOverview($productId, $branchId);

        return response()->json([
            'success' => true,
            'data' => $overview,
        ]);
    }

    /**
     * Check if stock is available for a product at a branch.
     *
     * GET /api/v1/fefo/check-availability?product_id=xxx&branch_id=xxx&quantity=10
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'quantity' => 'required|numeric|min:0.001',
        ]);

        $isAvailable = $this->fefoStockService->isAvailable(
            $validated['product_id'],
            $validated['branch_id'],
            (float) $validated['quantity']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'is_available' => $isAvailable,
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['branch_id'],
                'quantity_requested' => (float) $validated['quantity'],
            ],
        ]);
    }

    /**
     * Get all expiring batches for a company.
     *
     * GET /api/v1/fefo/expiring?days=30&branch_id=xxx
     */
    public function expiring(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $companyId = $request->user()->company_id;

        $batches = $this->fefoStockService->getExpiringBatches(
            $companyId,
            $validated['days'] ?? 30,
            $validated['branch_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $batches->map(fn($inv) => [
                'inventory_id' => $inv->id,
                'product_id' => $inv->product_id,
                'product_name' => $inv->product->name ?? $inv->product->generic_name ?? 'Unknown',
                'product_barcode' => $inv->product->barcode,
                'batch_number' => $inv->batch_number,
                'expiry_date' => $inv->expiry_date?->toDateString(),
                'quantity' => (float) $inv->quantity,
                'branch_name' => $inv->branch->name ?? 'Unknown',
                'days_until_expiry' => $inv->expiry_date ? now()->diffInDays($inv->expiry_date, false) : null,
            ]),
        ]);
    }

    /**
     * Get low stock alerts using FEFO-aware quantities.
     *
     * GET /api/v1/fefo/low-stock?branch_id=xxx
     */
    public function lowStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $companyId = $request->user()->company_id;

        $lowStockProducts = $this->fefoStockService->getLowStockAlerts(
            $companyId,
            $validated['branch_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => $lowStockProducts->map(fn($product) => [
                'product_id' => $product->id,
                'name' => $product->name,
                'generic_name' => $product->generic_name,
                'barcode' => $product->barcode,
                'product_code' => $product->product_code,
                'sku' => $product->sku,
                'reorder_point' => (float) $product->reorder_point,
                'total_available' => (float) ($product->total_available ?? 0),
                'min_stock' => (float) ($product->min_stock ?? 0),
                'stock_status' => $this->getStockStatus(
                    (float) ($product->total_available ?? 0),
                    (float) $product->reorder_point
                ),
            ]),
        ]);
    }

    private function getStockStatus(float $available, float $reorderPoint): string
    {
        if ($available <= 0) {
            return 'out_of_stock';
        }
        if ($available <= $reorderPoint * 0.5) {
            return 'critical';
        }
        if ($available <= $reorderPoint) {
            return 'low';
        }
        return 'sufficient';
    }
}

