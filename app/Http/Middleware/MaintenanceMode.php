<?php

namespace App\Http\Middleware;

use App\Models\MaintenanceSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the current maintenance setting
        $maintenance = MaintenanceSetting::latest()->first();

        // If no maintenance setting or maintenance is not active, proceed normally
        if (!$maintenance || !$maintenance->isActive()) {
            return $next($request);
        }

        // Check if current user/IP is allowed
        $clientIp = $request->ip();
        $userId = Auth::check() ? Auth::id() : null;

        if ($maintenance->isAllowed($clientIp, $userId)) {
            return $next($request);
        }

        // Return maintenance page
        return response()->view('maintenance', [
            'title' => $maintenance->title,
            'message' => $maintenance->message,
            'estimated_completion' => $maintenance->estimated_completion,
            'started_at' => $maintenance->started_at,
        ])->setStatusCode(503);
    }
}