<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialAuditRequest;
use App\Services\FinancialAuditService;
use App\Models\FinancialAuditLog;
use App\Http\Resources\FinancialAuditResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class FinancialAuditController extends Controller
{
    protected FinancialAuditService $auditService;

    public function __construct(FinancialAuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of financial audits.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;
        
        $audits = FinancialAuditLog::byBusiness($businessId)
            ->with(['user:id,name'])
            ->when($request->status, function ($query) use ($request) {
                return $query->byStatus($request->status);
            })
            ->when($request->audit_type, function ($query) use ($request) {
                return $query->byType($request->audit_type);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'message' => __('Financial audits fetched successfully.'),
            'audits' => FinancialAuditResource::collection($audits),
        ]);
    }

    /**
     * Store a newly created financial audit.
     */
    public function store(StoreFinancialAuditRequest $request)
    {
        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;
        
        $audit = $this->auditService->createAudit($data);

        return response()->json([
            'message' => __('Financial audit created successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ], 201);
    }

    /**
     * Display the specified financial audit.
     */
    public function show(FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $audit->load(['user:id,name']);

        return response()->json([
            'message' => __('Financial audit fetched successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ]);
    }

    /**
     * Start a financial audit.
     */
    public function start(FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $audit = $this->auditService->startAudit($audit, Auth::id());

        return response()->json([
            'message' => __('Financial audit started successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ]);
    }

    /**
     * Execute financial audit with balances.
     */
    public function execute(Request $request, FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $request->validate([
            'opening_balance' => 'required|numeric|min:0',
            'closing_balance' => 'required|numeric|min:0',
        ]);

        $audit = $this->auditService->executeAudit(
            $audit,
            $request->opening_balance,
            $request->closing_balance
        );

        return response()->json([
            'message' => __('Financial audit executed successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ]);
    }

    /**
     * Complete a financial audit.
     */
    public function complete(FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $audit = $this->auditService->completeAudit($audit);

        return response()->json([
            'message' => __('Financial audit completed successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ]);
    }

    /**
     * Cancel a financial audit.
     */
    public function cancel(Request $request, FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $reason = $request->input('reason');
        $audit = $this->auditService->cancelAudit($audit, $reason);

        return response()->json([
            'message' => __('Financial audit cancelled successfully.'),
            'audit' => new FinancialAuditResource($audit),
        ]);
    }

    /**
     * Get audit report.
     */
    public function report(FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $report = $this->auditService->getAuditReport($audit);

        return response()->json([
            'message' => __('Financial audit report generated successfully.'),
            'report' => $report,
        ]);
    }

    /**
     * Get transaction details by type.
     */
    public function transactionDetails(Request $request, FinancialAuditLog $audit)
    {
        $this->authorizeAuditAccess($audit);
        
        $request->validate([
            'type' => 'required|in:sales,purchases,income,expenses',
        ]);

        $details = $this->auditService->getTransactionDetails($audit, $request->type);

        return response()->json([
            'message' => __('Transaction details fetched successfully.'),
            'type' => $request->type,
            'details' => $details,
        ]);
    }

    /**
     * Get comparative report for multiple periods.
     */
    public function comparativeReport(Request $request)
    {
        $businessId = Auth::user()->business_id;
        
        $request->validate([
            'periods' => 'required|array|min:1',
            'periods.*.start_date' => 'required|date',
            'periods.*.end_date' => 'required|date|after_or_equal:periods.*.start_date',
        ]);

        $report = $this->auditService->getComparativeReport($businessId, $request->periods);

        return response()->json([
            'message' => __('Comparative report generated successfully.'),
            'report' => $report,
        ]);
    }

    /**
     * Get audit statistics.
     */
    public function statistics(Request $request)
    {
        $businessId = Auth::user()->business_id;
        
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $completedAudits = FinancialAuditLog::byBusiness($businessId)
            ->byStatus('completed')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->count();

        $pendingAudits = FinancialAuditLog::byBusiness($businessId)
            ->byStatus('pending')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->count();

        $totalVariance = FinancialAuditLog::byBusiness($businessId)
            ->byStatus('completed')
            ->whereBetween('start_date', [$startDate, $endDate])
            ->sum('variance');

        return response()->json([
            'message' => __('Audit statistics fetched successfully.'),
            'statistics' => [
                'completed_audits' => $completedAudits,
                'pending_audits' => $pendingAudits,
                'total_variance' => $totalVariance,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ],
        ]);
    }

    /**
     * Authorize access to audit.
     */
    private function authorizeAuditAccess(FinancialAuditLog $audit)
    {
        if ($audit->business_id !== Auth::user()->business_id) {
            abort(403, __('Unauthorized access to this audit.'));
        }
    }
}