<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
        $businessId = auth()->user()->role === 'superadmin' && $request->filled('business_id')
            ? (int) $request->business_id
            : (int) auth()->user()->business_id;
        
        $batchLots = BatchLot::forBusiness($businessId)
            ->with('product:id,name')
            ->when($request->search, function ($q) use ($request) {
                $q->where('batch_number', 'like', '%' . $request->search . '%')
                    ->orWhere('lot_number', 'like', '%' . $request->search . '%');
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
                $q->where('reason', 'like', '%' . $request->search . '%')
                    ->orWhere('batch_lot_number', 'like', '%' . $request->search . '%');
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
    public function createBatchLot(Request $request)
    {
        $request->validate([
            'business_id' => 'nullable|integer|exists:businesses,id',
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'manufacture_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacture_date',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $batchLot = $this->traceabilityService->createBatchLot([
                'business_id' => auth()->user()->role === 'superadmin' ? $request->business_id : auth()->user()->business_id,
                'product_id' => $request->product_id,
                'batch_number' => $request->batch_number,
                'lot_number' => $request->lot_number,
                'manufacture_date' => $request->manufacture_date,
                'expiry_date' => $request->expiry_date,
                'supplier_name' => $request->supplier_name,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Batch lot created successfully'),
                'redirect' => route('admin.traceability.batch-lots'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating batch lot: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update batch lot
     */
    public function updateBatchLot(Request $request, BatchLot $batchLot)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'nullable|string|max:255',
            'lot_number' => 'nullable|string|max:255',
            'manufacture_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:manufacture_date',
            'supplier_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            $batchLot = $this->traceabilityService->updateBatchLot($batchLot, [
                'product_id' => $request->product_id,
                'batch_number' => $request->batch_number,
                'lot_number' => $request->lot_number,
                'manufacture_date' => $request->manufacture_date,
                'expiry_date' => $request->expiry_date,
                'supplier_name' => $request->supplier_name,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Batch lot updated successfully'),
                'redirect' => route('admin.traceability.batch-lots'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating batch lot: ') . $e->getMessage(),
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
                'message' => __('Error deleting batch lot: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate recall
     */
    public function initiateRecall(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'product_id' => 'nullable|exists:products,id',
            'batch_lot_number' => 'nullable|string|max:255',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        try {
            $recall = $this->traceabilityService->initiateRecall([
                'business_id' => auth()->user()->role === 'superadmin' ? $request->business_id : auth()->user()->business_id,
                'product_id' => $request->product_id,
                'batch_lot_number' => $request->batch_lot_number,
                'reason' => $request->reason,
                'description' => $request->description,
            ]);

            return response()->json([
                'message' => __('Recall initiated successfully'),
                'redirect' => route('admin.traceability.recalls'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error initiating recall: ') . $e->getMessage(),
            ], 500);
        }
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
                'message' => __('Error resolving recall: ') . $e->getMessage(),
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
                'message' => __('Error deleting recall: ') . $e->getMessage(),
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
