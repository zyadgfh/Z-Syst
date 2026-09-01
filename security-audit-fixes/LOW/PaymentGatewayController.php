<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PaymentGatewayController — Security Audit Fix (LOW)
 *
 * Fixes applied:
 * 1. Added permission middleware for payment-gateways CRUD
 * 2. Added auth middleware to class constructor
 * 3. Removed company_id fallback to request parameter — always use resolved business ID
 * 4. Added structured logging for create/update/delete operations
 * 5. Added per_page validation
 */
class PaymentGatewayController extends Controller
{
    public function __construct(protected PaymentGatewayService $service)
    {
        $this->middleware(['auth', 'business.active']);
    }

    public function index(Request $request)
    {
        $this->authorize('permission', 'payment-gateways-read');

        $businessId = resolveBusinessId();

        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $gateways = $this->service->list($businessId, $perPage);

        return response()->json($gateways);
    }

    public function create()
    {
        $this->authorize('permission', 'payment-gateways-create');

        $businessId = resolveBusinessId();

        return response()->json($this->service->getCreateData($businessId));
    }

    public function store(Request $request)
    {
        $this->authorize('permission', 'payment-gateways-create');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'required|string|in:stripe,paypal,square,mollie,other',
            'api_key' => 'required|string|max:500',
            'api_secret' => 'required|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'test_mode' => 'sometimes|boolean',
        ]);

        $businessId = resolveBusinessId();

        $gateway = $this->service->create($businessId, $validated);

        StructuredLogger::info('payment_gateway_created', [
            'gateway_id' => $gateway->id ?? null,
            'provider' => $validated['provider'],
            'business_id' => $businessId,
            'user_id' => Auth::id(),
        ]);

        return response()->json($gateway, 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('permission', 'payment-gateways-update');

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'api_key' => 'sometimes|string|max:500',
            'api_secret' => 'sometimes|string|max:500',
            'webhook_secret' => 'nullable|string|max:500',
            'is_active' => 'sometimes|boolean',
            'test_mode' => 'sometimes|boolean',
        ]);

        $businessId = resolveBusinessId();

        $gateway = $this->service->update($businessId, $id, $validated);

        StructuredLogger::info('payment_gateway_updated', [
            'gateway_id' => $id,
            'business_id' => $businessId,
            'user_id' => Auth::id(),
            'changes' => array_keys($validated),
        ]);

        return response()->json($gateway);
    }

    public function destroy($id)
    {
        $this->authorize('permission', 'payment-gateways-delete');

        $businessId = resolveBusinessId();

        $this->service->delete($businessId, $id);

        StructuredLogger::warning('payment_gateway_deleted', [
            'gateway_id' => $id,
            'business_id' => $businessId,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Payment gateway deleted successfully']);
    }

    public function testConnection(Request $request)
    {
        $this->authorize('permission', 'payment-gateways-create');

        $validated = $request->validate([
            'gateway_id' => 'required|integer|exists:payment_gateways,id',
        ]);

        $businessId = resolveBusinessId();

        $result = $this->service->testConnection($businessId, $validated['gateway_id']);

        return response()->json($result);
    }

    public function bulkActions(Request $request)
    {
        $this->authorize('permission', 'payment-gateways-update');

        $validated = $request->validate([
            'action' => 'required|in:activate,deactivate,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:payment_gateways,id',
        ]);

        $businessId = resolveBusinessId();

        $result = $this->service->bulkActions($businessId, $validated);

        StructuredLogger::info('payment_gateway_bulk_action', [
            'action' => $validated['action'],
            'count' => count($validated['ids']),
            'business_id' => $businessId,
            'user_id' => Auth::id(),
        ]);

        return response()->json($result);
    }
}
