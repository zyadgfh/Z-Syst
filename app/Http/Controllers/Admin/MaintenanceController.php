<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceSetting;
use App\Logging\StructuredLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    /**
     * Display maintenance management page
     */
    public function index()
    {
        return view('admin.maintenance.index');
    }

    /**
     * Get current maintenance status (API)
     */
    public function getStatus()
    {
        $maintenance = MaintenanceSetting::latest()->first();
        
        return response()->json([
            'success' => true,
            'maintenance' => $maintenance,
            'status' => $maintenance ? ($maintenance->isActive() ? 'active' : ($maintenance->isScheduled() ? 'scheduled' : 'inactive')) : 'inactive',
        ]);
    }

    /**
     * Activate maintenance mode
     */
    public function activate(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'estimated_completion' => 'nullable|string|max:255',
                'allowed_ips' => 'nullable|array',
                'allowed_users' => 'nullable|array',
                'ended_at' => 'nullable|date',
            ]);

            // Deactivate any existing maintenance
            MaintenanceSetting::where('is_enabled', true)->update(['is_enabled' => false, 'ended_at' => now()]);
            
            // Create new maintenance setting
            $maintenance = MaintenanceSetting::create([
                'is_enabled' => true,
                'title' => $request->title ?? 'System Maintenance',
                'message' => $request->message,
                'estimated_completion' => $request->estimated_completion,
                'allowed_ips' => $request->allowed_ips,
                'allowed_users' => $request->allowed_users,
                'started_at' => now(),
                'ended_at' => $request->ended_at,
                'created_by' => Auth::id(),
            ]);

            // Log the action
            StructuredLogger::logSecurityEvent('maintenance_activated', [
                'maintenance_id' => $maintenance->id,
                'title' => $maintenance->title,
                'created_by' => Auth::id(),
            ]);

            // Notify all tenants about maintenance
            $this->notifyTenants($maintenance);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode activated successfully',
                'maintenance' => $maintenance,
            ], 200);

        } catch (\Exception $e) {
            StructuredLogger::logSecurityEvent('maintenance_activation_failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to activate maintenance mode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate maintenance mode
     */
    public function deactivate()
    {
        try {
            $maintenance = MaintenanceSetting::where('is_enabled', true)->first();
            
            if (!$maintenance) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active maintenance mode found',
                ], 404);
            }

            $maintenance->deactivate();

            // Log the action
            StructuredLogger::logSecurityEvent('maintenance_deactivated', [
                'maintenance_id' => $maintenance->id,
                'deactivated_by' => Auth::id(),
            ]);

            // Notify all tenants that maintenance is over
            $this->notifyTenantsMaintenanceEnded($maintenance);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode deactivated successfully',
                'maintenance' => $maintenance,
            ], 200);

        } catch (\Exception $e) {
            StructuredLogger::logSecurityEvent('maintenance_deactivation_failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to deactivate maintenance mode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Schedule maintenance mode
     */
    public function schedule(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'message' => 'required|string',
                'estimated_completion' => 'nullable|string|max:255',
                'allowed_ips' => 'nullable|array',
                'allowed_users' => 'nullable|array',
                'scheduled_for' => 'required|date',
            ]);

            $scheduledFor = $request->scheduled_for 
                ? \Carbon\Carbon::parse($request->scheduled_for) 
                : now()->addHours(1);

            // Deactivate any existing maintenance
            MaintenanceSetting::where('is_enabled', true)->update(['is_enabled' => false, 'ended_at' => now()]);
            
            // Create scheduled maintenance
            $maintenance = MaintenanceSetting::create([
                'is_enabled' => false,
                'title' => $request->title ?? 'System Maintenance',
                'message' => $request->message,
                'estimated_completion' => $request->estimated_completion,
                'allowed_ips' => $request->allowed_ips,
                'allowed_users' => $request->allowed_users,
                'scheduled_for' => $scheduledFor,
                'created_by' => Auth::id(),
            ]);

            // Log the action
            StructuredLogger::logSecurityEvent('maintenance_scheduled', [
                'maintenance_id' => $maintenance->id,
                'scheduled_for' => $scheduledFor,
                'created_by' => Auth::id(),
            ]);

            // Notify admins about scheduled maintenance
            $this->notifyAdminsScheduledMaintenance($maintenance);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance mode scheduled successfully',
                'maintenance' => $maintenance,
            ], 200);

        } catch (\Exception $e) {
            StructuredLogger::logSecurityEvent('maintenance_scheduling_failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule maintenance mode',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update maintenance settings
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'title' => 'nullable|string|max:255',
                'message' => 'nullable|string',
                'estimated_completion' => 'nullable|string|max:255',
                'allowed_ips' => 'nullable|array',
                'allowed_users' => 'nullable|array',
                'ended_at' => 'nullable|date',
            ]);

            $maintenance = MaintenanceSetting::findOrFail($id);
            
            $maintenance->update([
                'title' => $request->title ?? $maintenance->title,
                'message' => $request->message ?? $maintenance->message,
                'estimated_completion' => $request->estimated_completion ?? $maintenance->estimated_completion,
                'allowed_ips' => $request->allowed_ips ?? $maintenance->allowed_ips,
                'allowed_users' => $request->allowed_users ?? $maintenance->allowed_users,
                'ended_at' => $request->ended_at ?? $maintenance->ended_at,
            ]);

            // Log the action
            StructuredLogger::logSecurityEvent('maintenance_updated', [
                'maintenance_id' => $maintenance->id,
                'updated_by' => Auth::id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Maintenance settings updated successfully',
                'maintenance' => $maintenance,
            ], 200);

        } catch (\Exception $e) {
            StructuredLogger::logSecurityEvent('maintenance_update_failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update maintenance settings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get maintenance history
     */
    public function history()
    {
        $history = MaintenanceSetting::orderBy('created_at', 'desc')
            ->with('creator')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Notify tenants about maintenance
     */
    protected function notifyTenants(MaintenanceSetting $maintenance): void
    {
        // Send notifications to all active businesses
        // This would integrate with your notification system
        // For now, we'll log the notification
        StructuredLogger::logBusinessEvent(0, 'maintenance_notification', [
            'maintenance_id' => $maintenance->id,
            'title' => $maintenance->title,
            'message' => $maintenance->message,
            'started_at' => $maintenance->started_at,
        ]);
    }

    /**
     * Notify tenants that maintenance has ended
     */
    protected function notifyTenantsMaintenanceEnded(MaintenanceSetting $maintenance): void
    {
        StructuredLogger::logBusinessEvent(0, 'maintenance_ended_notification', [
            'maintenance_id' => $maintenance->id,
            'ended_at' => $maintenance->ended_at,
        ]);
    }

    /**
     * Notify admins about scheduled maintenance
     */
    protected function notifyAdminsScheduledMaintenance(MaintenanceSetting $maintenance): void
    {
        StructuredLogger::logUserAction(Auth::id(), 'scheduled_maintenance_notification', [
            'maintenance_id' => $maintenance->id,
            'scheduled_for' => $maintenance->scheduled_for,
        ]);
    }
}