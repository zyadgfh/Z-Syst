<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Permission;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:settings-read,settings-update']);
    }

    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }

        // Return settings for the authenticated user's business
        $settings = $this->getSettingsForBusiness($businessId);

        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $businessId = $request->user()->business_id;

        if (!$businessId) {
            return response()->json(['error' => 'Business context required'], 403);
        }

        $validated = $request->validate([
            'generalSettings' => 'nullable|array',
            'taxSettings' => 'nullable|array',
            'receiptSettings' => 'nullable|array',
            'hardwareSettings' => 'nullable|array',
            'taxSettings.taxRate' => 'nullable|numeric|min:0|max:100',
            'receiptSettings.header' => 'nullable|string|max:255',
            'receiptSettings.footer' => 'nullable|string|max:255',
        ]);

        $this->updateSettingsForBusiness($businessId, $validated);

        return response()->json(['message' => 'Settings updated successfully']);
    }

    private function getSettingsForBusiness($businessId)
    {
        // Implementation to fetch settings for specific business
        // Ensure business_id is always in the query
        return [
            'generalSettings' => $this->getGeneralSettings($businessId),
            'taxSettings' => $this->getTaxSettings($businessId),
            'receiptSettings' => $this->getReceiptSettings($businessId),
            'hardwareSettings' => $this->getHardwareSettings($businessId),
        ];
    }

    private function updateSettingsForBusiness($businessId, $data)
    {
        // Implementation to update settings for specific business
        // Always include business_id in updates
        foreach ($data as $type => $settings) {
            $this->updateSettingByType($businessId, $type, $settings);
        }
    }

    private function updateSettingByType($businessId, $type, $settings)
    {
        // Validate and update specific setting type
        // Log the change
        \App\Helpers\StructuredLogger::log('settings.update', [
            'business_id' => $businessId,
            'type' => $type,
            'changes' => $settings,
            'updated_by' => Auth::id(),
        ]);
    }

    private function getGeneralSettings($businessId)
    {
        // Fetch general settings for business
        return [];
    }

    private function getTaxSettings($businessId)
    {
        // Fetch tax settings for business
        return [];
    }

    private function getReceiptSettings($businessId)
    {
        // Fetch receipt settings for business
        return [];
    }

    private function getHardwareSettings($businessId)
    {
        // Fetch hardware settings for business
        return [];
    }
}