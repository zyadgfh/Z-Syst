<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::query()->with('user');

        if ($request->has('user_id')) {
            $query->forUser($request->user_id);
        }

        if ($request->has('action')) {
            $query->action($request->action);
        }

        if ($request->has('days')) {
            $query->recent($request->days);
        }

        $logs = $query->orderByDesc('performed_at')->paginate(50);

        return response()->json($logs);
    }

    public function show(ActivityLog $activityLog): JsonResponse
    {
        $activityLog->load('user');

        return response()->json($activityLog);
    }
}
