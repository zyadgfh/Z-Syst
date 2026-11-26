<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Gateway;
use App\Models\TenantPaymentSetting;
use Illuminate\Http\Request;

class TenantPaymentSettingController extends Controller
{
    public function __construct()
    {
        // Every action is gated: reading another tenant's gateway credentials
        // (merchant_code/merchant_key) is as sensitive as writing them.
        $this->middleware('permission:settings-read')->only(['index', 'show', 'create', 'edit', 'getTenantSettings']);
        $this->middleware('permission:settings-update')->only(['store', 'update', 'destroy', 'toggleStatus']);
    }

    /**
     * Ownership boundary: a non-superadmin may only ever touch their OWN
     * tenant's payment settings. TenantPaymentSetting is intentionally not
     * TenantScoped (it is platform infrastructure), so this controller is the
     * isolation layer — the tenant id always comes from the verified
     * principal, never from the client.
     */
    private function authorizeTenantAccess(int $tenantId): void
    {
        $user = request()->user();

        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        if (($user->role ?? null) === 'superadmin') {
            return; // reviewed administrative exception
        }

        $own = isset($user->business_id) && $user->business_id !== null
            ? (int) $user->business_id
            : 0;

        if ($own !== $tenantId) {
            abort(403, 'You do not have permission to access this tenant data.');
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = request()->user();
        $isSuperadmin = ($user->role ?? null) === 'superadmin';

        $tenantsQuery = Business::latest();
        $settingsQuery = TenantPaymentSetting::with(['tenant', 'gateway'])->latest();

        if (! $isSuperadmin) {
            // Non-superadmins see only their own tenant.
            $tenantsQuery->where('id', (int) $user->business_id);
            $settingsQuery->where('tenant_id', (int) $user->business_id);
        }

        $tenants = $tenantsQuery->get();
        $gateways = Gateway::where('status', 1)->get();
        $settings = $settingsQuery->get();

        return view('admin.tenant-payment-settings.index', compact('tenants', 'gateways', 'settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = request()->user();
        $tenantsQuery = Business::latest();
        if (($user->role ?? null) !== 'superadmin') {
            $tenantsQuery->where('id', (int) $user->business_id);
        }

        $tenants = $tenantsQuery->get();
        $gateways = Gateway::where('status', 1)->get();

        return view('admin.tenant-payment-settings.create', compact('tenants', 'gateways'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|integer|exists:businesses,id',
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

        $this->authorizeTenantAccess((int) $request->input('tenant_id'));

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

        TenantPaymentSetting::create($request->only([
        'tenant_id',
        'branch_id',
        'gateway_id',
        'settings',
        'is_active',
        'merchant_phone',
        'merchant_name',
        'merchant_code',
        'merchant_key',
        'merchant_instapay_id',
        'bank_name',
        'account_number',
        'branch_name'
    ]));

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
        $this->authorizeTenantAccess((int) $setting->tenant_id);

        $user = request()->user();
        $tenantsQuery = Business::latest();
        if (($user->role ?? null) !== 'superadmin') {
            $tenantsQuery->where('id', (int) $user->business_id);
        }

        $tenants = $tenantsQuery->get();
        $gateways = Gateway::where('status', 1)->get();

        return view('admin.tenant-payment-settings.edit', compact('setting', 'tenants', 'gateways'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tenant_id' => 'required|integer|exists:businesses,id',
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
        // Both the current owner and the requested target tenant must be
        // reachable — otherwise a non-superadmin could re-home the row.
        $this->authorizeTenantAccess((int) $setting->tenant_id);
        $this->authorizeTenantAccess((int) $request->input('tenant_id'));

        $setting->update($request->only([
        'tenant_id',
        'branch_id',
        'gateway_id',
        'settings',
        'is_active',
        'merchant_phone',
        'merchant_name',
        'merchant_code',
        'merchant_key',
        'merchant_instapay_id',
        'bank_name',
        'account_number',
        'branch_name'
    ]));

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting updated successfully.')
        ]);
    }

    /**
     * Remove the specified resource in storage.
     */
    public function destroy($id)
    {
        $setting = TenantPaymentSetting::findOrFail($id);
        $this->authorizeTenantAccess((int) $setting->tenant_id);
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
        $this->authorizeTenantAccess((int) $tenantId);

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
        $this->authorizeTenantAccess((int) $setting->tenant_id);
        $setting->update(['is_active' => !$setting->is_active]);

        return response()->json([
            'status' => 'success',
            'message' => __('Payment setting status updated successfully.')
        ]);
    }
}
