<?php

namespace App\Http\Controllers\Api;

use App\Models\FefoSetting;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class FefoConfigController extends Controller
{
    /**
     * Get FEFO settings for the current business.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $businessId = auth()->user()->business_id;
        $settings = FefoSetting::getForBusiness($businessId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $settings,
        ]);
    }

    /**
     * Update FEFO settings.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'fefo_enabled' => 'boolean',
            'deduction_mode' => 'in:automatic,manual_suggestion',
            'expiry_grace_days' => 'integer|min:1|max:365',
            'auto_deduct_expired_stock' => 'boolean',
            'notify_on_fefo_deduction' => 'boolean',
            'min_stock_for_fefo' => 'integer|min:0',
        ]);

        $settings = FefoSetting::updateOrCreate(
            ['business_id' => $businessId],
            $request->only([
                'fefo_enabled',
                'deduction_mode',
                'expiry_grace_days',
                'auto_deduct_expired_stock',
                'notify_on_fefo_deduction',
                'min_stock_for_fefo',
            ])
        );

        return response()->json([
            'message' => __('Settings updated successfully.'),
            'data' => $settings,
        ]);
    }
}

