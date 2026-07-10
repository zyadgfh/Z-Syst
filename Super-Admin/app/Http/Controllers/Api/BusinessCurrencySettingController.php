<?php

namespace App\Http\Controllers\Api;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;


class BusinessCurrencySettingController extends Controller
{
    public function index()
    {
        $businessId = auth()->user()->business_id;

        $currency_setting_key = 'currency_setting_' . $businessId;
        $currency_setting = Option::where('key', $currency_setting_key)->first();

        if ($currency_setting) {
            return response()->json([
                'message' => __('Currency data fetched successfully.'),
                'currency_data' => $currency_setting->value,
            ]);
        } else {
            return response()->json([
                'message' => __('Currency not found.'),
                'currency_data' => null,
            ], 404);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency' => 'required|string|max:100|in:us,european',
        ]);

        $key = 'currency_setting_' . auth()->user()->business_id;

        Option::updateOrCreate(
            ['key' => $key],
            ['value' => $request->currency]
        );

        Cache::forget($key);

        return response()->json(__('Currency setting updated successfully.'));

    }
}
