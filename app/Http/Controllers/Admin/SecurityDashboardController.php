<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityDashboardController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        // ── Audit Log Summary (last 30 days) ──
        $recentAuditLogs = AuditLog::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->with('user:id,name')
            ->latest()
            ->paginate(20, ['*'], 'audit_page');

        $auditStats = [
            'total_30d' => AuditLog::where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))->count(),
            'today' => AuditLog::where('business_id', $businessId)
                ->whereDate('created_at', today())->count(),
            'unique_users' => AuditLog::where('business_id', $businessId)
                ->where('created_at', '>=', now()->subDays(30))
                ->distinct('user_id')->count('user_id'),
        ];

        // ── Action distribution (last 30 days) ──
        $actionDistribution = AuditLog::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->select('action', DB::raw('count(*) as count'))
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        // ── Daily activity trend (last 14 days) ──
        $dailyActivity = AuditLog::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(14))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Users with most activity ──
        $topUsers = AuditLog::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(30))
            ->join('users', 'audit_logs.user_id', '=', 'users.id')
            ->select('users.name', DB::raw('count(*) as activity_count'))
            ->groupBy('users.name')
            ->orderByDesc('activity_count')
            ->limit(10)
            ->get();

        // ── Permission changes (role/permission related logs) ──
        $permissionChanges = AuditLog::where('business_id', $businessId)
            ->where(function ($q) {
                $q->where('action', 'like', '%permission%')
                    ->orWhere('action', 'like', '%role%')
                    ->orWhere('action', 'like', '%user%create%')
                    ->orWhere('action', 'like', '%user%delete%');
            })
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get();

        // ── User accounts summary ──
        $userStats = [
            'total' => User::where('business_id', $businessId)->count(),
            'active' => User::where('business_id', $businessId)
                ->where('email_verified_at', '!=', null)->count(),
            'recent_logins' => User::where('business_id', $businessId)
                ->where('last_login_at', '>=', now()->subDays(7))->count(),
        ];

        // ── Product security (items with prescriptions, controlled items) ──
        $productSecurity = [
            'prescription_items' => Product::where('business_id', $businessId)
                ->where('prescription_required', true)->count(),
            'controlled_items' => Product::where('business_id', $businessId)
                ->where('controlled_item', true)->count(),
            'active_items' => Product::where('business_id', $businessId)
                ->where('active', true)->count(),
        ];

        // ── Unusual activity indicators ──
        $unusualActivity = AuditLog::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subDays(7))
            ->where(function ($q) {
                $q->where('action', 'like', '%delete%')
                    ->orWhere('action', 'like', '%archive%')
                    ->orWhere('action', 'like', '%restore%')
                    ->orWhere('action', 'like', '%export%');
            })
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        return view('admin.security-dashboard.index', compact(
            'recentAuditLogs',
            'auditStats',
            'actionDistribution',
            'dailyActivity',
            'topUsers',
            'permissionChanges',
            'userStats',
            'productSecurity',
            'unusualActivity'
        ));
    }
}
