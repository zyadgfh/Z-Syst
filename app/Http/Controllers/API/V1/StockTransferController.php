<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(
        private readonly StockTransferService $stockTransferService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'from_branch_id', 'to_branch_id', 'branch_id',
            'date_from', 'date_to', 'search', 'per_page'
        ]);

        $transfers = $this->stockTransferService->getTransfers(
            $request->user()->company_id,
            $filters
        );

        return response()->json($transfers);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_stock_id' => 'nullable|exists:product_stocks,id',
            'items.*.quantity_requested' => 'required|numeric|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            $transfer = $this->stockTransferService->createTransfer(
                $request->all(),
                $request->user()->id
            );

            return response()->json($transfer, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json($stockTransfer->load([
            'fromBranch:id,name',
            'toBranch:id,name',
            'requestedBy:id,name',
            'approvedBy:id,name',
            'shippedBy:id,name',
            'receivedBy:id,name',
            'items.product:id,name,sku',
        ]));
    }

    public function approve(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $transfer = $this->stockTransferService->approveTransfer(
                $stockTransfer,
                $request->user()->id
            );

            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function reject(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate(['reason' => 'required|string']);

        try {
            $transfer = $this->stockTransferService->rejectTransfer(
                $stockTransfer,
                $request->user()->id,
                $request->reason
            );

            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function ship(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_sent' => 'required|numeric|min:1',
            'items.*.batch_number' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        try {
            $transfer = $this->stockTransferService->shipTransfer(
                $stockTransfer,
                $request->user()->id,
                $request->items
            );

            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function receive(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:stock_transfer_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        try {
            $transfer = $this->stockTransferService->receiveTransfer(
                $stockTransfer,
                $request->user()->id,
                $request->items,
                $request->notes
            );

            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function cancel(Request $request, StockTransfer $stockTransfer): JsonResponse
    {
        if ($stockTransfer->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $transfer = $this->stockTransferService->cancelTransfer(
                $stockTransfer,
                $request->user()->id,
                $request->reason
            );

            return response()->json($transfer);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->stockTransferService->getTransferStatistics(
            $request->user()->company_id,
            $request->branch_id,
            $request->period ?? 'month'
        );

        return response()->json($stats);
    }
}