<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockAuditDetailRequest;
use App\Http\Requests\StoreStockAuditRequest;
use App\Http\Requests\UpdateStockReconciliationRequest;
use App\Http\Resources\StockAuditDetailResource;
use App\Http\Resources\StockAuditResource;
use App\Http\Resources\StockReconciliationResource;
use App\Models\StockAudit;
use App\Models\StockAuditDetail;
use App\Models\StockReconciliation;
use App\Services\StockAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockAuditController extends Controller
{
    protected StockAuditService $auditService;

    public function __construct(StockAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of stock audits.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $audits = StockAudit::byBusiness($businessId)
            ->with(['user:id,name', 'details'])
            ->when($request->status, function ($query) use ($request) {
                return $query->byStatus($request->status);
            })
            ->when($request->audit_type, function ($query) use ($request) {
                return $query->byType($request->audit_type);
            })
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Stock audits fetched successfully.'),
            'audits' => StockAuditResource::collection($audits),
        ]);
    }

    /**
     * Store a newly created stock audit.
     */
    public function store(StoreStockAuditRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;

        $audit = $this->auditService->createAudit($data);

        return response()->json([
            'message' => __('Stock audit created successfully.'),
            'audit' => new StockAuditResource($audit),
        ], 201);
    }

    /**
     * Display the specified stock audit.
     */
    public function show(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $audit->load(['user:id,name', 'details.product', 'details.stock', 'reconciliations']);

        $summary = $this->auditService->getAuditSummary($audit);

        return response()->json([
            'message' => __('Stock audit fetched successfully.'),
            'audit' => new StockAuditResource($audit),
            'summary' => $summary,
        ]);
    }

    /**
     * Start a stock audit.
     */
    public function start(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $audit = $this->auditService->startAudit($audit, Auth::id());

        return response()->json([
            'message' => __('Stock audit started successfully.'),
            'audit' => new StockAuditResource($audit),
        ]);
    }

    /**
     * Complete a stock audit.
     */
    public function complete(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $audit = $this->auditService->completeAudit($audit);

        return response()->json([
            'message' => __('Stock audit completed successfully.'),
            'audit' => new StockAuditResource($audit),
        ]);
    }

    /**
     * Cancel a stock audit.
     */
    public function cancel(Request $request, StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $reason = $request->input('reason');
        $audit = $this->auditService->cancelAudit($audit, $reason);

        return response()->json([
            'message' => __('Stock audit cancelled successfully.'),
            'audit' => new StockAuditResource($audit),
        ]);
    }

    /**
     * Auto-populate audit with current stock.
     */
    public function autoPopulate(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $details = $this->auditService->autoPopulateAudit($audit);

        return response()->json([
            'message' => __('Audit auto-populated with current stock successfully.'),
            'details_count' => count($details),
            'details' => StockAuditDetailResource::collection($details),
        ]);
    }

    /**
     * Add audit detail.
     */
    public function addDetail(StoreStockAuditDetailRequest $request)
    {
        $data = $request->validated();

        $audit = StockAudit::findOrFail($data['stock_audit_id']);
        $this->authorizeAuditAccess($audit);

        $detail = $this->auditService->addAuditDetail($audit, $data);

        return response()->json([
            'message' => __('Audit detail added successfully.'),
            'detail' => new StockAuditDetailResource($detail),
        ], 201);
    }

    /**
     * Add multiple audit details in bulk.
     */
    public function addBulkDetails(Request $request, StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $request->validate([
            'details' => 'required|array|min:1',
            'details.*.product_id' => 'required|exists:products,id',
            'details.*.stock_id' => 'nullable|exists:stocks,id',
            'details.*.physical_quantity' => 'required|integer|min:0',
            'details.*.notes' => 'nullable|string|max:500',
        ]);

        $details = $this->auditService->addBulkAuditDetails($audit, $request->details);

        return response()->json([
            'message' => __('Audit details added successfully.'),
            'details_count' => count($details),
            'details' => StockAuditDetailResource::collection($details),
        ], 201);
    }

    /**
     * Get variance report for audit.
     */
    public function varianceReport(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $report = $this->auditService->getVarianceReport($audit);

        return response()->json([
            'message' => __('Variance report generated successfully.'),
            'report' => $report,
        ]);
    }

    /**
     * Create reconciliation from audit detail.
     */
    public function createReconciliation(Request $request, StockAuditDetail $detail)
    {
        $this->authorizeDetailAccess($detail);

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reconciliation = $this->auditService->createReconciliation(
            $detail,
            Auth::id(),
            $request->reason
        );

        return response()->json([
            'message' => __('Reconciliation created successfully.'),
            'reconciliation' => new StockReconciliationResource($reconciliation),
        ], 201);
    }

    /**
     * Post reconciliation to stock.
     */
    public function postReconciliation(StockReconciliation $reconciliation)
    {
        $this->authorizeReconciliationAccess($reconciliation);

        $reconciliation = $this->auditService->postReconciliation($reconciliation);

        return response()->json([
            'message' => __('Reconciliation posted successfully.'),
            'reconciliation' => new StockReconciliationResource($reconciliation),
        ]);
    }

    /**
     * Post all reconciliations for an audit.
     */
    public function postAllReconciliations(StockAudit $audit)
    {
        $this->authorizeAuditAccess($audit);

        $reconciliations = $this->auditService->postAllReconciliations($audit);

        return response()->json([
            'message' => __('All reconciliations posted successfully.'),
            'reconciliations_count' => count($reconciliations),
            'reconciliations' => StockReconciliationResource::collection($reconciliations),
        ]);
    }

    /**
     * Update reconciliation.
     */
    public function updateReconciliation(UpdateStockReconciliationRequest $request, StockReconciliation $reconciliation)
    {
        $this->authorizeReconciliationAccess($reconciliation);

        if ($reconciliation->is_posted) {
            return response()->json([
                'message' => __('Cannot update posted reconciliation.'),
            ], 400);
        }

        $reconciliation->update($request->validated());

        return response()->json([
            'message' => __('Reconciliation updated successfully.'),
            'reconciliation' => new StockReconciliationResource($reconciliation->fresh()),
        ]);
    }

    /**
     * Delete reconciliation.
     */
    public function deleteReconciliation(StockReconciliation $reconciliation)
    {
        $this->authorizeReconciliationAccess($reconciliation);

        if ($reconciliation->is_posted) {
            return response()->json([
                'message' => __('Cannot delete posted reconciliation.'),
            ], 400);
        }

        $reconciliation->delete();

        return response()->json([
            'message' => __('Reconciliation deleted successfully.'),
        ]);
    }

    /**
     * Delete audit detail.
     */
    public function deleteDetail(StockAuditDetail $detail)
    {
        $this->authorizeDetailAccess($detail);

        $audit = $detail->stockAudit;

        if ($audit->status === 'completed') {
            return response()->json([
                'message' => __('Cannot delete detail from completed audit.'),
            ], 400);
        }

        $detail->delete();

        return response()->json([
            'message' => __('Audit detail deleted successfully.'),
        ]);
    }

    /**
     * Authorize access to audit.
     */
    private function authorizeAuditAccess(StockAudit $audit)
    {
        if ($audit->business_id !== Auth::user()->business_id) {
            abort(403, __('Unauthorized access to this audit.'));
        }
    }

    /**
     * Authorize access to audit detail.
     */
    private function authorizeDetailAccess(StockAuditDetail $detail)
    {
        if ($detail->business_id !== Auth::user()->business_id) {
            abort(403, __('Unauthorized access to this audit detail.'));
        }
    }

    /**
     * Authorize access to reconciliation.
     */
    private function authorizeReconciliationAccess(StockReconciliation $reconciliation)
    {
        if ($reconciliation->business_id !== Auth::user()->business_id) {
            abort(403, __('Unauthorized access to this reconciliation.'));
        }
    }
}
