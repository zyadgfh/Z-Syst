<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PushNotificationPreference;
use App\Models\PushToken;
use Illuminate\Http\Request;

class PushNotificationPreferencesController extends Controller
{
    /**
     * Show the push notification preferences and device management page.
     */
    public function index()
    {
        $user = auth()->user();
        $businessId = $user->business_id;

        // Get all users in this business with their push tokens
        $devices = PushToken::where('business_id', $businessId)
            ->with('user:id,name,email,image')
            ->orderByDesc('last_used_at')
            ->get();

        // Get the current user's preferences
        $preferences = PushNotificationPreference::where('user_id', $user->id)
            ->pluck('is_enabled', 'notification_type')
            ->toArray();

        $notificationTypes = PushNotificationPreference::availableTypes();

        return view('admin.push-notifications.index', compact('devices', 'preferences', 'notificationTypes'));
    }

    /**
     * Save notification type preferences for the current user.
     */
    public function updatePreferences(Request $request)
    {
        $request->validate([
            'types' => 'required|array',
            'types.*' => 'string|in:' . implode(',', array_keys(PushNotificationPreference::availableTypes())),
        ]);

        $userId = auth()->id();
        $types = $request->input('types', []);

        $allTypes = array_keys(PushNotificationPreference::availableTypes());
        foreach ($allTypes as $type) {
            PushNotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'notification_type' => $type],
                ['is_enabled' => in_array($type, $types)]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ تفضيلات الإشعارات بنجاح',
        ]);
    }

    /**
     * Toggle a single notification type.
     */
    public function toggleType(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(PushNotificationPreference::availableTypes())),
        ]);

        $enabled = PushNotificationPreference::toggle(auth()->id(), $request->input('type'));

        return response()->json([
            'success' => true,
            'enabled' => $enabled,
            'message' => $enabled ? 'تم تفعيل الإشعار' : 'تم تعطيل الإشعار',
        ]);
    }

    /**
     * Deactivate a specific device (push token).
     */
    public function deactivateDevice(PushToken $device)
    {
        $device->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل الجهاز بنجاح',
        ]);
    }

    /**
     * Remove a specific device (push token).
     */
    public function removeDevice(PushToken $device)
    {
        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الجهاز بنجاح',
        ]);
    }

    /**
     * Deactivate all devices for a specific user.
     */
    public function deactivateAllDevices(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        PushToken::where('user_id', $request->input('user_id'))
            ->where('business_id', auth()->user()->business_id)
            ->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'تم تعطيل جميع أجهزة هذا المستخدم',
        ]);
    }
}
