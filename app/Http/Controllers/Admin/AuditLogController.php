<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('permission:audit-logs-read')->only('index', 'show', 'statistics');
        $this->middleware('permission:audit-logs-delete')->only('destroy', 'cleanOldLogs');
    }

    /**
     * Display audit logs
     */
    public function index(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;

        // Super admins without a business can see all logs
        $filters = [
            'action' => $request->action,
            'user_id' => $request->user_id,
            'model_type' => $request->model_type,
            'model_id' => $request->model_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        if ($businessId) {
            $logs = $this->auditService->getLogsForBusiness((int) $businessId, $filters);
        } else {
            $logs = AuditLog::with(['user:id,name,email', 'business:id,companyName'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        }

        return view('admin.audit-logs.index', compact('logs'));
    }

    /**
     * Show audit log details
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['user:id,name,email', 'business:id,companyName']);

        return view('admin.audit-logs.show', compact('auditLog'));
    }

    /**
     * Get audit logs statistics
     */
    public function statistics(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;

        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->auditService->getStatistics($businessId, $filters);

        return response()->json($statistics);
    }

    /**
     * Get logs for specific model
     */
    public function modelLogs(Request $request)
    {
        $request->validate([
            'model_type' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $model = app($request->model_type)->findOrFail($request->model_id);
        $logs = $this->auditService->getLogsForModel($model);

        return response()->json($logs);
    }

    /**
     * Get logs for specific user
     */
    public function userLogs(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $limit = $request->limit ?? 100;
        $logs = $this->auditService->getLogsForUser($request->user_id, $limit);

        return response()->json($logs);
    }

    /**
     * Delete audit log
     */
    public function destroy(AuditLog $auditLog)
    {
        try {
            $auditLog->delete();

            return response()->json([
                'message' => __('Audit log deleted successfully'),
                'redirect' => route('admin.audit-logs.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting audit log: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clean old audit logs
     */
    public function cleanOldLogs(Request $request)
    {
        $request->validate([
            'days_to_keep' => 'nullable|integer|min:1',
        ]);

        $daysToKeep = $request->days_to_keep ?? 90;
        $deleted = $this->auditService->cleanOldLogs($daysToKeep);

        return response()->json([
            'message' => __('Deleted :count old audit logs', ['count' => $deleted]),
            'deleted_count' => $deleted,
        ]);
    }
}
