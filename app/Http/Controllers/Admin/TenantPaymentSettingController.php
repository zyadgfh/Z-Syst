<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Gateway;
use App\Models\TenantPaymentSetting;
use Illuminate\Http\Request;

class TenantPaymentSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tenants = Business::latest()->get();
        $gateways = Gateway::where('status', 1)->get();
        $settings = TenantPaymentSetting::with(['tenant', 'gateway'])->latest()->get();

        return view('admin.tenant-payment-settings.index', compact('tenants', 'gateways', 'settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tenants = Business::latest()->get();
        $gateways = Gateway::where('status', 1)->get();

        return view('admin.tenant-payment-settings.create', compact('tenants', 'gateways'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:businesses,id',
            'gateway_id' => 'required|exists:gateways,id',
            'is_active' => 'required|boolean',
            // Egyptian gateway specific fields
            'merchant_phone' => 'nullable|string|max:20',
            'merchant_name' => 'nullable|string|max:255',
            'merchant_code' => 'nullable|string|max:100',
            'merchant_key' => 'nullable|string|max:255',
            'merchant_instapay_id' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'branch_name' => 'nullable|string|max:255',
        ]);

        // Check if setting already exists for this tenant-gateway combination
        $existing = TenantPaymentSetting::where('tenant_id', $request->tenant_id)
                                      ->where('gateway_id', $request->gateway_id)
                                      ->where('branch_id', $request->branch_id ?: null)
                                      ->first();

        if ($existing) {
            return response()->json([
                'status' => 'error',
                'message' => __('Payment setting already exists for this tenant and gateway.')
            ], 400);
        }

        TenantPaymentSetting::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting created successfully.')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $setting = TenantPaymentSetting::findOrFail($id);
        $tenants = Business::latest()->get();
        $gateways = Gateway::where('status', 1)->get();

        return view('admin.tenant-payment-settings.edit', compact('setting', 'tenants', 'gateways'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tenant_id' => 'required|exists:businesses,id',
            'gateway_id' => 'required|exists:gateways,id',
            'is_active' => 'required|boolean',
            // Egyptian gateway specific fields
            'merchant_phone' => 'nullable|string|max:20',
            'merchant_name' => 'nullable|string|max:255',
            'merchant_code' => 'nullable|string|max:100',
            'merchant_key' => 'nullable|string|max:255',
            'merchant_instapay_id' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:50',
            'branch_name' => 'nullable|string|max:255',
        ]);

        $setting = TenantPaymentSetting::findOrFail($id);
        $setting->update($request->all());

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting updated successfully.')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $setting = TenantPaymentSetting::findOrFail($id);
        $setting->delete();

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting deleted successfully.')
        ]);
    }

    /**
     * Get tenant payment settings for a specific tenant
     */
    public function getTenantSettings($tenantId)
    {
        $settings = TenantPaymentSetting::where('tenant_id', $tenantId)
                                      ->with('gateway')
                                      ->active()
                                      ->get();

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        $setting = TenantPaymentSetting::findOrFail($id);
        $setting->update(['is_active' => !$setting->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting status updated successfully.')
        ]);
    }
}