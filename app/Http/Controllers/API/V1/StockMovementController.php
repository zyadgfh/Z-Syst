<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\StockMovementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockMovementService
    ) {}

    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
            'limit' => 'nullable|integer|min:1|max:200',
        ]);

        $history = $this->stockMovementService->getMovementHistory(
            $request->product_id,
            $request->branch_id,
            $request->limit ?? 50
        );

        return response()->json([
            'data' => $history,
            'product_id' => $request->product_id,
            'branch_id' => $request->branch_id,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->stockMovementService->getMovementSummary(
            $request->user()->company_id,
            $request->period ?? 'month'
        );

        return response()->json($summary);
    }
}