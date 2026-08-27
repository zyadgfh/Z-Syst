<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiateRecallRequest;
use App\Http\Resources\BatchLotResource;
use App\Http\Resources\RecallEventResource;
use App\Http\Resources\TraceabilityLogResource;
use App\Models\BatchLot;
use App\Models\RecallEvent;
use App\Models\TraceabilityLog;
use App\Services\TraceabilityService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TraceabilityController extends Controller
{
    protected TraceabilityService $traceabilityService;

    public function __construct(TraceabilityService $traceabilityService)
    {
        $this->traceabilityService = $traceabilityService;
    }

    public function batchLots(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $batchLots = BatchLot::where('business_id', $request->user()->business_id)
            ->when($validated['product_id'] ?? null, function ($query, $productId) {
                $query->where('product_id', $productId);
            })
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => BatchLotResource::collection($batchLots),
        ]);
    }

    public function expiringBatches(Request $request)
    {
        $validated = $request->validate([
            'days' => 'nullable|integer|min:1|max:365',
        ]);

        $batches = $this->traceabilityService->getExpiringBatches(
            $request->user()->business_id,
            $validated['days'] ?? 30
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $batches,
        ]);
    }

    public function expiredBatches(Request $request)
    {
        $batches = $this->traceabilityService->getExpiredBatches(
            $request->user()->business_id
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $batches,
        ]);
    }

    public function recalls(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'resolved', 'all'])],
        ]);

        $query = RecallEvent::where('business_id', $request->user()->business_id)
            ->with(['product', 'user']);

        if (isset($validated['status']) && $validated['status'] !== 'all') {
            $query->where('status', $validated['status']);
        }

        $recalls = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => RecallEventResource::collection($recalls),
        ]);
    }

    public function initiateRecall(InitiateRecallRequest $request)
    {
        $validated = $request->validated();

        $validated['business_id'] = $request->user()->business_id;
        $validated['user_id'] = $request->user()->id;

        $recall = $this->traceabilityService->initiateRecall($validated);

        return response()->json([
            'message' => __('Recall initiated successfully.'),
            'data' => new RecallEventResource($recall->fresh(['product', 'user'])),
        ], 201);
    }

    public function resolveRecall(Request $request, RecallEvent $recall)
    {
        if ($recall->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $recall = $this->traceabilityService->resolveRecall($recall);

        return response()->json([
            'message' => __('Recall resolved successfully.'),
            'data' => new RecallEventResource($recall->fresh(['product', 'user'])),
        ]);
    }

    public function traceability(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_lot_number' => 'nullable|string|max:255',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $logs = TraceabilityLog::where('business_id', $request->user()->business_id)
            ->where('product_id', $validated['product_id']);

        if (isset($validated['batch_lot_number'])) {
            $logs->where('batch_lot_number', $validated['batch_lot_number']);
        }

        if (isset($validated['from_date']) && isset($validated['to_date'])) {
            $logs->whereBetween('created_at', [$validated['from_date'], $validated['to_date']]);
        }

        $logs = $logs->orderBy('created_at', 'desc')->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => TraceabilityLogResource::collection($logs),
        ]);
    }

    public function recallSummary(Request $request, RecallEvent $recall)
    {
        if ($recall->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $summary = $this->traceabilityService->getRecallSummary($recall);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $summary,
        ]);
    }

    public function quarantineBatch(Request $request, RecallEvent $recall)
    {
        if ($recall->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $validated = $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $batchLot = BatchLot::where('business_id', $request->user()->business_id)
            ->findOrFail($validated['batch_lot_id']);

        $this->traceabilityService->quarantineAffectedBatch($recall, $batchLot, $validated['notes'] ?? null);

        return response()->json([
            'message' => __('Batch quarantined successfully.'),
        ]);
    }

    public function releaseBatch(Request $request, RecallEvent $recall)
    {
        if ($recall->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $validated = $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
        ]);

        $batchLot = BatchLot::where('business_id', $request->user()->business_id)
            ->findOrFail($validated['batch_lot_id']);

        $this->traceabilityService->releaseAffectedBatch($recall, $batchLot);

        return response()->json([
            'message' => __('Batch released from quarantine.'),
        ]);
    }

    public function disposeBatch(Request $request, RecallEvent $recall)
    {
        if ($recall->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $validated = $request->validate([
            'batch_lot_id' => 'required|exists:batch_lots,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $batchLot = BatchLot::where('business_id', $request->user()->business_id)
            ->findOrFail($validated['batch_lot_id']);

        $this->traceabilityService->disposeAffectedBatch($recall, $batchLot, $validated['notes'] ?? null);

        return response()->json([
            'message' => __('Batch disposed successfully.'),
        ]);
    }

    public function detectAffectedBatches(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'batch_lot_number' => 'nullable|string|max:255',
        ]);

        $batches = $this->traceabilityService->detectAffectedBatches(
            $request->user()->business_id,
            $validated['product_id'] ?? null,
            $validated['batch_lot_number'] ?? null
        );

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => BatchLotResource::collection($batches),
            'total' => $batches->count(),
        ]);
    }
}
