<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('company_id', $request->user()->company_id)
            ->when($request->type, fn($q, $v) => $q->where('type', $v))
            ->when($request->is_read !== null, fn($q, $v) => $q->where('is_read', $v === 'true' || $v === '1'))
            ->orderByDesc('created_at')
            ->paginate($request->per_page ?? 25);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:notifications,id',
        ]);

        Notification::whereIn('id', $request->ids)
            ->where('company_id', $request->user()->company_id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Notifications marked as read']);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        Notification::where('company_id', $request->user()->company_id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        return response()->json([
            'total' => Notification::where('company_id', $companyId)->count(),
            'unread' => Notification::where('company_id', $companyId)->where('is_read', false)->count(),
            'by_type' => Notification::where('company_id', $companyId)
                ->selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ]);
    }

    public function destroy(Request $request, Notification $notification): JsonResponse
    {
        if ($notification->company_id !== $request->user()->company_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $notification->delete();

        return response()->json(['message' => 'Notification deleted']);
    }
}