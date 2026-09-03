<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentGatewayRequest;
use App\Models\Branch;
use App\Models\CompanyPaymentGateway;
use App\Models\PaymentTransaction;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentGatewayController extends Controller
{
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display a listing of payment gateways for the current company.
     */
    public function index(Request $request)
    {
        $companyId = Auth::user()->business_id ?? $request->input('company_id');
        $branchId = $request->input('branch_id');

        if (! $companyId) {
            return redirect()->back()->with('error', 'Company ID is required');
        }

        $gateways = CompanyPaymentGateway::byCompany($companyId)
            ->when($branchId, fn ($q) => $q->byBranch($branchId))
            ->with(['company', 'branch'])
            ->orderBy('sort_order')
            ->get();

        $gatewayTypes = CompanyPaymentGateway::getGatewayTypes();
        $branches = Branch::byCompany($companyId)->active()->get();

        return view('admin.payment-gateways.index', compact('gateways', 'gatewayTypes', 'branches', 'companyId', 'branchId'));
    }

    /**
     * Show the form for creating a new payment gateway.
     */
    public function create(Request $request)
    {
        $companyId = Auth::user()->business_id ?? $request->input('company_id');
        $branchId = $request->input('branch_id');

        if (! $companyId) {
            return redirect()->back()->with('error', 'Company ID is required');
        }

        $gatewayTypes = CompanyPaymentGateway::getGatewayTypes();
        $branches = Branch::byCompany($companyId)->active()->get();
        $selectedType = $request->input('gateway_type');

        $requiredFields = [];
        if ($selectedType) {
            $requiredFields = $this->paymentGatewayService->getRequiredConfigFields($selectedType);
        }

        return view('admin.payment-gateways.create', compact(
            'gatewayTypes',
            'branches',
            'companyId',
            'branchId',
            'selectedType',
            'requiredFields'
        ));
    }

    /**
     * Store a newly created payment gateway.
     */
    public function store(PaymentGatewayRequest $request)
    {
        try {
            $data = $request->validated();
            $data['company_id'] = $data['company_id'] ?? Auth::user()->business_id;

            // Validate the gateway configuration
            $validation = $this->paymentGatewayService->validateGatewayConfig(
                $data['gateway_type'],
                $data['config_data'] ?? []
            );

            if (! $validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid gateway configuration: '.$validation['message']);
            }

            $gateway = $this->paymentGatewayService->upsertGatewayConfig($data);

            return redirect()->route('admin.payment-gateways.index', [
                'company_id' => $gateway->company_id,
                'branch_id' => $gateway->branch_id,
            ])->with('success', 'Payment gateway created successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error creating payment gateway: '.$e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified payment gateway.
     */
    public function edit(Request $request, $id)
    {
        $gateway = CompanyPaymentGateway::with(['company', 'branch'])->findOrFail($id);

        $gatewayTypes = CompanyPaymentGateway::getGatewayTypes();
        $branches = Branch::byCompany($gateway->company_id)->active()->get();
        $requiredFields = $this->paymentGatewayService->getRequiredConfigFields($gateway->gateway_type);

        return view('admin.payment-gateways.edit', compact(
            'gateway',
            'gatewayTypes',
            'branches',
            'requiredFields'
        ));
    }

    /**
     * Update the specified payment gateway.
     */
    public function update(PaymentGatewayRequest $request, $id)
    {
        try {
            $gateway = CompanyPaymentGateway::findOrFail($id);
            $data = $request->validated();
            $data['company_id'] = $gateway->company_id;
            $data['gateway_type'] = $gateway->gateway_type;

            // Validate the gateway configuration
            $validation = $this->paymentGatewayService->validateGatewayConfig(
                $gateway->gateway_type,
                $data['config_data'] ?? []
            );

            if (! $validation['valid']) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid gateway configuration: '.$validation['message']);
            }

            $gateway = $this->paymentGatewayService->upsertGatewayConfig($data);

            return redirect()->route('admin.payment-gateways.index', [
                'company_id' => $gateway->company_id,
                'branch_id' => $gateway->branch_id,
            ])->with('success', 'Payment gateway updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error updating payment gateway: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified payment gateway.
     */
    public function destroy($id)
    {
        try {
            $gateway = CompanyPaymentGateway::findOrFail($id);

            // Check if there are any pending transactions
            $pendingTransactions = PaymentTransaction::where('gateway_id', $id)
                ->where('status', PaymentTransaction::STATUS_PENDING)
                ->count();

            if ($pendingTransactions > 0) {
                return redirect()->back()->with('error',
                    'Cannot delete gateway with pending transactions');
            }

            $gateway->delete();

            return redirect()->back()->with('success', 'Payment gateway deleted successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting payment gateway: '.$e->getMessage());
        }
    }

    /**
     * Toggle gateway active status.
     */
    public function toggleStatus($id)
    {
        try {
            $gateway = CompanyPaymentGateway::findOrFail($id);
            $gateway->is_active = ! $gateway->is_active;
            $gateway->save();

            return response()->json([
                'success' => true,
                'message' => $gateway->is_active ? 'Gateway activated' : 'Gateway deactivated',
                'is_active' => $gateway->is_active,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show transactions for a specific gateway.
     */
    public function transactions(Request $request, $id)
    {
        $gateway = CompanyPaymentGateway::findOrFail($id);

        $query = PaymentTransaction::where('gateway_id', $id);

        if ($request->input('status')) {
            $query->byStatus($request->input('status'));
        }

        if ($request->input('transaction_type')) {
            $query->byType($request->input('transaction_type'));
        }

        if ($request->input('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->input('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        $transactions = $query->latest()->paginate(20);

        $stats = $this->paymentGatewayService->getTransactionStats(
            $gateway->company_id,
            $gateway->branch_id,
            ['gateway_type' => $gateway->gateway_type]
        );

        return view('admin.payment-gateways.transactions', compact(
            'gateway',
            'transactions',
            'stats'
        ));
    }

    /**
     * Get required configuration fields for a gateway type (AJAX).
     */
    public function getRequiredFields(Request $request)
    {
        $gatewayType = $request->input('gateway_type');
        $fields = $this->paymentGatewayService->getRequiredConfigFields($gatewayType);

        return response()->json([
            'success' => true,
            'fields' => $fields,
        ]);
    }

    /**
     * Test gateway configuration (AJAX).
     */
    public function testConfiguration(Request $request)
    {
        try {
            $gatewayType = $request->input('gateway_type');
            $configData = $request->input('config_data', []);

            $validation = $this->paymentGatewayService->validateGatewayConfig($gatewayType, $configData);

            return response()->json($validation);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
