<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Http\Requests\UpdateStockTransferRequest;
use App\Http\Requests\ApproveStockTransferRequest;
use App\Http\Requests\RejectStockTransferRequest;
use App\Http\Requests\ShipStockTransferRequest;
use App\Http\Requests\ReceiveStockTransferRequest;
use App\Http\Requests\CancelStockTransferRequest;
use App\Http\Resources\StockTransferResource;
use App\Http\Resources\StockTransferItemResource;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

/**
 * StockTransferController
 * 
 * Controller for managing stock transfer operations.
 * Handles the complete workflow: Request → Approve → Ship → Receive
 * 
 * @author Z-Syst Development Team
 * @version 1.0.0
 */
class StockTransferController extends Controller
{
    /**
     * The stock transfer service instance.
     *
     * @var \App\Services\StockTransferService
     */
    protected StockTransferService $stockTransferService;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\StockTransferService  $stockTransferService
     * @return void
     */
    public function __construct(StockTransferService $stockTransferService)
    {
        $this->stockTransferService = $stockTransferService;
    }

    /**
     * Display a listing of stock transfers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', StockTransfer::class);

        $filters = [
            'status' => $request->input('status'),
            'from_branch_id' => $request->input('from_branch_id'),
            'to_branch_id' => $request->input('to_branch_id'),
            'branch_id' => $request->input('branch_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'search' => $request->input('search'),
            'per_page' => $request->input('per_page', 25),
        ];

        $transfers = $this->stockTransferService->getTransfers(
            Auth::user()->company_id,
            $filters
        );

        return StockTransferResource::collection($transfers);
    }

    /**
     * Store a newly created stock transfer in storage.
     *
     * @param  \App\Http\Requests\StoreStockTransferRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        $this->authorize('create', StockTransfer::class);

        $transfer = $this->stockTransferService->createTransfer(
            $request->validated(),
            Auth::id()
        );

        return response()->json([
            'message' => 'Stock transfer created successfully.',
            'data' => new StockTransferResource($transfer),
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified stock transfer.
     *
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('view', $stockTransfer);

        $stockTransfer->load(['items.product', 'fromBranch', 'toBranch', 'requestedBy', 'approvedBy', 'shippedBy', 'receivedBy']);

        return response()->json([
            'data' => new StockTransferResource($stockTransfer),
        ]);
    }

    /**
     * Update the specified stock transfer in storage.
     *
     * @param  \App\Http\Requests\UpdateStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('update', $stockTransfer);

        $stockTransfer->update($request->validated());

        return response()->json([
            'message' => 'Stock transfer updated successfully.',
            'data' => new StockTransferResource($stockTransfer->fresh()),
        ]);
    }

    /**
     * Remove the specified stock transfer from storage (soft delete).
     *
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('delete', $stockTransfer);

        $stockTransfer->delete();

        return response()->json([
            'message' => 'Stock transfer deleted successfully.',
        ]);
    }

    /**
     * Approve the specified stock transfer.
     *
     * @param  \App\Http\Requests\ApproveStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve(ApproveStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('approve', $stockTransfer);

        $transfer = $this->stockTransferService->approveTransfer(
            $stockTransfer,
            Auth::id()
        );

        return response()->json([
            'message' => 'Stock transfer approved successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Reject the specified stock transfer.
     *
     * @param  \App\Http\Requests\RejectStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(RejectStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('reject', $stockTransfer);

        $transfer = $this->stockTransferService->rejectTransfer(
            $stockTransfer,
            Auth::id(),
            $request->input('rejection_reason')
        );

        return response()->json([
            'message' => 'Stock transfer rejected successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Ship the specified stock transfer.
     *
     * @param  \App\Http\Requests\ShipStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function ship(ShipStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('ship', $stockTransfer);

        $transfer = $this->stockTransferService->shipTransfer(
            $stockTransfer,
            Auth::id(),
            $request->input('items')
        );

        return response()->json([
            'message' => 'Stock transfer shipped successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Receive the specified stock transfer.
     *
     * @param  \App\Http\Requests\ReceiveStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function receive(ReceiveStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('receive', $stockTransfer);

        $transfer = $this->stockTransferService->receiveTransfer(
            $stockTransfer,
            Auth::id(),
            $request->input('items'),
            $request->input('notes')
        );

        return response()->json([
            'message' => 'Stock transfer received successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Cancel the specified stock transfer.
     *
     * @param  \App\Http\Requests\CancelStockTransferRequest  $request
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancel(CancelStockTransferRequest $request, StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('cancel', $stockTransfer);

        $transfer = $this->stockTransferService->cancelTransfer(
            $stockTransfer,
            Auth::id(),
            $request->input('cancellation_reason')
        );

        return response()->json([
            'message' => 'Stock transfer cancelled successfully.',
            'data' => new StockTransferResource($transfer),
        ]);
    }

    /**
     * Get stock transfer statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewStatistics', StockTransfer::class);

        $branchId = $request->input('branch_id');
        $period = $request->input('period', 'month');

        $statistics = $this->stockTransferService->getTransferStatistics(
            Auth::user()->company_id,
            $branchId,
            $period
        );

        return response()->json([
            'data' => $statistics,
        ]);
    }

    /**
     * Get items for a specific stock transfer.
     *
     * @param  \App\Models\StockTransfer  $stockTransfer
     * @return \Illuminate\Http\JsonResponse
     */
    public function items(StockTransfer $stockTransfer): JsonResponse
    {
        $this->authorize('view', $stockTransfer);

        $items = $stockTransfer->items()->with('product')->get();

        return response()->json([
            'data' => StockTransferItemResource::collection($items),
        ]);
    }
}
