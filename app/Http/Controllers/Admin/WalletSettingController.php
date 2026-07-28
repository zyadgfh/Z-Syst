<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * WalletSettingController
 *
 * إدارة إعدادات المحفظة الإلكترونية (رقم المحفظة، اسم المحفظة)
 * متاح فقط للمشرفين (Admin) مع صلاحية manage_wallet_settings
 */
class WalletSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:manage_wallet_settings');
    }

    /**
     * Get wallet settings for the company.
     *
     * GET /api/v1/admin/wallet-settings
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $settings = [
            'wallet_provider' => Setting::getValue('wallet_provider', 'vodafone_cash', $companyId),
            'wallet_phone' => Setting::getValue('wallet_phone', '', $companyId),
            'wallet_name' => Setting::getValue('wallet_name', '', $companyId),
            'wallet_qr_code' => Setting::getValue('wallet_qr_code', '', $companyId),
            'wallet_enabled' => Setting::getValue('wallet_enabled', true, $companyId),
            'wallet_instructions' => Setting::getValue('wallet_instructions', 'يرجى تحويل المبلغ إلى رقم المحفظة الظاهر في الإيصال', $companyId),
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update wallet settings.
     *
     * PUT /api/v1/admin/wallet-settings
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_provider' => ['nullable', 'string', 'in:vodafone_cash,orange_cash,etisalat_cash,instapay,we_pay'],
            'wallet_phone' => ['nullable', 'string', 'max:20'],
            'wallet_name' => ['nullable', 'string', 'max:255'],
            'wallet_qr_code' => ['nullable', 'string', 'max:1000'],
            'wallet_enabled' => ['nullable', 'boolean'],
            'wallet_instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $companyId = $request->user()->company_id;

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'key' => $key,
                    'group' => 'wallet',
                ],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'type' => is_bool($value) ? 'boolean' : 'string',
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => __('Wallet settings updated successfully'),
        ]);
    }

    /**
     * Get wallet phone number (public - used by POS and receipts).
     *
     * GET /api/v1/wallet-phone
     */
    public function getWalletPhone(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $phone = Setting::getValue('wallet_phone', '', $companyId);
        $provider = Setting::getValue('wallet_provider', 'vodafone_cash', $companyId);
        $name = Setting::getValue('wallet_name', '', $companyId);
        $enabled = Setting::getValue('wallet_enabled', true, $companyId);

        return response()->json([
            'success' => true,
            'data' => [
                'wallet_phone' => $phone,
                'wallet_provider' => $provider,
                'wallet_name' => $name,
                'wallet_enabled' => (bool) $enabled,
            ],
        ]);
    }
}

