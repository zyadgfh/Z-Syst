<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateRecallRequest;
use App\Http\Requests\StoreBatchLotRequest;
use App\Http\Requests\UpdateBatchLotRequest;
use App\Models\BatchLot;
use App\Models\RecallEvent;
use App\Services\TraceabilityService;
use Illuminate\Http\Request;

class TraceabilityController extends Controller
{
    protected TraceabilityService $traceabilityService;

    public function __construct(TraceabilityService $traceabilityService)
    {
        $this->traceabilityService = $traceabilityService;
        $this->middleware('permission:traceability-read')->only('index', 'show', 'batchLots', 'recalls');
        $this->middleware('permission:traceability-create')->only('createBatchLot', 'initiateRecall');
        $this->middleware('permission:traceability-update')->only('updateBatchLot', 'resolveRecall');
        $this->middleware('permission:traceability-delete')->only('destroyBatchLot', 'destroyRecall');
    }

    public function index()
    {
        return view('admin.traceability.index');
    }

    /**
     * Get batch lots
     */
    public function batchLots(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;

        $batchLots = BatchLot::forBusiness($businessId)
            ->with('product:id,name')
            ->when($request->search, function ($q) use ($request) {
                $q->where('batch_number', 'like', '%'.$request->search.'%')
                    ->orWhere('lot_number', 'like', '%'.$request->search.'%');
            })
            ->when($request->status, function ($q) use ($request) {
                if ($request->status === 'expired') {
                    $q->expired();
                } elseif ($request->status === 'expiring_soon') {
                    $q->expiringSoon();
                } elseif ($request->status === 'recalled') {
                    $q->recalled();
                }
            })
            ->latest()
            ->paginate(10);

        return view('admin.traceability.batch-lots', compact('batchLots'));
    }

    /**
     * Get recalls
     */
    public function recalls(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;

        $recalls = RecallEvent::forBusiness($businessId)
            ->with(['product:id,name', 'user:id,name'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('reason', 'like', '%'.$request->search.'%')
                    ->orWhere('batch_lot_number', 'like', '%'.$request->search.'%');
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest('initiated_at')
            ->paginate(10);

        return view('admin.traceability.recalls', compact('recalls'));
    }

    /**
     * Create batch lot
     */
    public function createBatchLot(StoreBatchLotRequest $request)
    {
        try {
            $batchLot = $this->traceabilityService->createBatchLot($request->validated());

            return response()->json([
                'message' => __('Batch lot created successfully'),
                'redirect' => route('admin.traceability.batch-lots'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating batch lot: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update batch lot
     */
    public function updateBatchLot(UpdateBatchLotRequest $request, BatchLot $batchLot)
    {
        try {
            $batchLot = $this->traceabilityService->updateBatchLot($batchLot, $request->validated());

            return response()->json([
                'message' => __('Batch lot updated successfully'),
                'redirect' => route('admin.traceability.batch-lots'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating batch lot: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete batch lot
     */
    public function destroyBatchLot(BatchLot $batchLot)
    {
        try {
            $batchLot->delete();

            return response()->json([
                'message' => __('Batch lot deleted successfully'),
                'redirect' => route('admin.traceability.batch-lots'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting batch lot: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate recall
     */
    public function initiateRecall(InitiateRecallRequest $request)
    {
        try {
            $data = $request->validated();
            $data['user_id'] = auth()->id();
            $recall = $this->traceabilityService->initiateRecall($data);

            return response()->json([
                'message' => __('Recall initiated successfully'),
                'redirect' => route('admin.traceability.recalls'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error initiating recall: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get recall summary with affected batch details
     */
    public function recallSummary(RecallEvent $recall)
    {
        $summary = $this->traceabilityService->getRecallSummary($recall);

        return response()->json($summary);
    }

    /**
     * Quarantine an affected batch in a recall
     */
    public function quarantineBatch(Request $request, RecallEvent $recall)
    {
        $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $batchLot = \App\Models\BatchLot::findOrFail($request->batch_lot_id);
            $this->traceabilityService->quarantineAffectedBatch($recall, $batchLot, $request->notes);

            return response()->json([
                'message' => __('Batch quarantined successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error quarantining batch: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Release an affected batch from quarantine
     */
    public function releaseBatch(Request $request, RecallEvent $recall)
    {
        $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
        ]);

        try {
            $batchLot = \App\Models\BatchLot::findOrFail($request->batch_lot_id);
            $this->traceabilityService->releaseAffectedBatch($recall, $batchLot);

            return response()->json([
                'message' => __('Batch released from quarantine successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error releasing batch: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dispose of an affected batch
     */
    public function disposeBatch(Request $request, RecallEvent $recall)
    {
        $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $batchLot = \App\Models\BatchLot::findOrFail($request->batch_lot_id);
            $this->traceabilityService->disposeAffectedBatch($recall, $batchLot, $request->notes);

            return response()->json([
                'message' => __('Batch disposed successfully'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error disposing batch: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect affected batches for a product/batch scenario
     */
    public function detectAffectedBatches(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'product_id' => 'nullable|exists:products,id',
            'batch_lot_number' => 'nullable|string|max:255',
        ]);

        $batches = $this->traceabilityService->detectAffectedBatches(
            $request->business_id,
            $request->product_id,
            $request->batch_lot_number
        );

        return response()->json([
            'data' => $batches,
            'total' => $batches->count(),
        ]);
    }

    /**
     * Resolve recall
     */
    public function resolveRecall(RecallEvent $recall)
    {
        try {
            $recall = $this->traceabilityService->resolveRecall($recall);

            return response()->json([
                'message' => __('Recall resolved successfully'),
                'redirect' => route('admin.traceability.recalls'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error resolving recall: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete recall
     */
    public function destroyRecall(RecallEvent $recall)
    {
        try {
            $recall->delete();

            return response()->json([
                'message' => __('Recall deleted successfully'),
                'redirect' => route('admin.traceability.recalls'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting recall: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product traceability
     */
    public function getProductTraceability(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_lot_number' => 'nullable|string',
        ]);

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $traceability = $this->traceabilityService->getProductTraceability(
            $businessId,
            $request->product_id,
            $request->batch_lot_number
        );

        return response()->json($traceability);
    }

    /**
     * Get traceability statistics
     */
    public function statistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'product_id' => $request->product_id,
        ];

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->traceabilityService->getTraceabilityStatistics($businessId, $filters);

        return response()->json($statistics);
    }

    /**
     * Get recall statistics
     */
    public function recallStatistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->traceabilityService->getRecallStatistics($businessId, $filters);

        return response()->json($statistics);
    }

    /**
     * Get expiring batches
     */
    public function expiringBatches(Request $request)
    {
        $days = $request->days ?? 30;
        $businessId = $request->business_id ?? auth()->user()->business_id;

        $expiring = $this->traceabilityService->getExpiringBatches($businessId, $days);

        return response()->json($expiring);
    }

    /**
     * Get expired batches
     */
    public function expiredBatches(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;

        $expired = $this->traceabilityService->getExpiredBatches($businessId);

        return response()->json($expired);
    }
}
