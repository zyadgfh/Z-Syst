<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * MaintenanceController — Security Audit Fix (LOW)
 *
 * Fixes applied:
 * 1. Added permission middleware for maintenance-settings CRUD
 * 2. Added auth middleware to class constructor
 * 3. Added auth check on getStatus method
 * 4. Added structured logging for all state-changing operations
 * 5. Added input validation for schedule method
 */
class MaintenanceController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'business.active']);
    }

    public function getStatus()
    {
        $this->authorize('permission', 'maintenance-settings-read');

        $setting = MaintenanceSetting::first();

        return response()->json([
            'maintenance_mode' => $setting?->is_active ?? false,
            'scheduled_at' => $setting?->scheduled_at,
            'message' => $setting?->message,
            'allowed_ips' => $setting?->allowed_ips ?? [],
        ]);
    }

    public function activate(Request $request)
    {
        $this->authorize('permission', 'maintenance-settings-update');

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
        ]);

        $setting = MaintenanceSetting::firstOrCreate([], [
            'is_active' => false,
        ]);

        $setting->update([
            'is_active' => true,
            'message' => $validated['message'] ?? 'System is currently under maintenance.',
            'allowed_ips' => $validated['allowed_ips'] ?? [],
        ]);

        StructuredLogger::warning('maintenance_mode_activated', [
            'user_id' => Auth::id(),
            'business_id' => resolveBusinessId(),
            'allowed_ips' => $validated['allowed_ips'] ?? [],
        ]);

        return response()->json(['message' => 'Maintenance mode activated']);
    }

    public function deactivate()
    {
        $this->authorize('permission', 'maintenance-settings-update');

        $setting = MaintenanceSetting::first();

        if ($setting) {
            $setting->update(['is_active' => false]);
        }

        StructuredLogger::info('maintenance_mode_deactivated', [
            'user_id' => Auth::id(),
            'business_id' => resolveBusinessId(),
        ]);

        return response()->json(['message' => 'Maintenance mode deactivated']);
    }

    public function update(Request $request)
    {
        $this->authorize('permission', 'maintenance-settings-update');

        $validated = $request->validate([
            'message' => 'nullable|string|max:1000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'ip',
            'is_active' => 'sometimes|boolean',
        ]);

        $setting = MaintenanceSetting::firstOrCreate([], [
            'is_active' => false,
        ]);

        $setting->update($validated);

        StructuredLogger::info('maintenance_settings_updated', [
            'user_id' => Auth::id(),
            'business_id' => resolveBusinessId(),
            'changes' => array_keys($validated),
        ]);

        return response()->json(['message' => 'Maintenance settings updated']);
    }

    public function schedule(Request $request)
    {
        $this->authorize('permission', 'maintenance-settings-update');

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
            'message' => 'nullable|string|max:1000',
            'duration_minutes' => 'required|integer|min:1|max:480',
        ]);

        $setting = MaintenanceSetting::firstOrCreate([], [
            'is_active' => false,
        ]);

        $setting->update([
            'scheduled_at' => $validated['scheduled_at'],
            'message' => $validated['message'] ?? 'Scheduled maintenance window',
        ]);

        StructuredLogger::info('maintenance_scheduled', [
            'user_id' => Auth::id(),
            'business_id' => resolveBusinessId(),
            'scheduled_at' => $validated['scheduled_at'],
            'duration_minutes' => $validated['duration_minutes'],
        ]);

        return response()->json(['message' => 'Maintenance scheduled successfully']);
    }
}
