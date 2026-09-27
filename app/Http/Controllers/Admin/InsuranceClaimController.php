<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InsuranceClaim;
use App\Services\InsuranceService;
use Illuminate\Http\Request;

class InsuranceClaimController extends Controller
{
    protected InsuranceService $insuranceService;

    public function __construct(InsuranceService $insuranceService)
    {
        $this->insuranceService = $insuranceService;
        $this->middleware('permission:insurance-claims-create')->only('create', 'store');
        $this->middleware('permission:insurance-claims-read')->only('index', 'show');
        $this->middleware('permission:insurance-claims-update')->only('edit', 'update', 'submit', 'process', 'processPayment');
        $this->middleware('permission:insurance-claims-delete')->only('destroy');
    }

    public function index(Request $request)
    {
        $claims = InsuranceClaim::with(['company:id,name', 'policy:id,policy_number', 'business:id,companyName', 'customer'])
            ->when($request->search, function ($q) use ($request) {
                $q->where('claim_number', 'like', '%' . $request->search . '%')
                    ->orWhere('external_reference', 'like', '%' . $request->search . '%');
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->company_id, function ($q) use ($request) {
                $q->where('insurance_company_id', $request->company_id);
            })
            ->latest()
            ->paginate(10);

        return view('admin.insurance.claims.index', compact('claims'));
    }

    public function create()
    {
        return view('admin.insurance.claims.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'nullable|integer|exists:businesses,id',
            'insurance_company_id' => 'required|exists:insurance_companies,id',
            'insurance_policy_id' => 'required|exists:insurance_policies,id',
            'sale_id' => 'nullable|exists:sales,id',
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'customer_id' => 'nullable|exists:parties,id',
            'user_id' => 'nullable|exists:users,id',
            'service_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'covered_amount' => 'nullable|numeric|min:0',
            'patient_responsibility' => 'nullable|numeric|min:0',
            'line_items' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $claim = $this->insuranceService->createClaim([
                'business_id' => auth()->user()->role === 'superadmin' ? $request->business_id : auth()->user()->business_id,
                'insurance_company_id' => $request->insurance_company_id,
                'insurance_policy_id' => $request->insurance_policy_id,
                'sale_id' => $request->sale_id,
                'prescription_id' => $request->prescription_id,
                'customer_id' => $request->customer_id,
                'service_date' => $request->service_date,
                'total_amount' => $request->total_amount,
                'covered_amount' => $request->covered_amount,
                'patient_responsibility' => $request->patient_responsibility,
                'line_items' => $request->line_items,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Insurance claim created successfully'),
                'redirect' => route('admin.insurance.claims.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating insurance claim: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function show(InsuranceClaim $claim)
    {
        $claim->load(['company', 'policy', 'business', 'customer', 'user', 'sale', 'prescription']);
        return view('admin.insurance.claims.show', compact('claim'));
    }

    public function edit(InsuranceClaim $claim)
    {
        if ($claim->isSubmitted()) {
            return response()->json([
                'message' => __('Cannot edit submitted claims'),
            ], 403);
        }

        $claim->load(['company', 'policy']);
        return view('admin.insurance.claims.edit', compact('claim'));
    }

    public function update(Request $request, InsuranceClaim $claim)
    {
        if ($claim->isSubmitted()) {
            return response()->json([
                'message' => __('Cannot edit submitted claims'),
            ], 403);
        }

        $request->validate([
            'service_date' => 'required|date',
            'total_amount' => 'required|numeric|min:0',
            'covered_amount' => 'nullable|numeric|min:0',
            'patient_responsibility' => 'nullable|numeric|min:0',
            'line_items' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        try {
            $claim->update([
                'service_date' => $request->service_date,
                'total_amount' => $request->total_amount,
                'covered_amount' => $request->covered_amount,
                'patient_responsibility' => $request->patient_responsibility,
                'line_items' => $request->line_items,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => __('Insurance claim updated successfully'),
                'redirect' => route('admin.insurance.claims.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating insurance claim: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(InsuranceClaim $claim)
    {
        if ($claim->isSubmitted()) {
            return response()->json([
                'message' => __('Cannot delete submitted claims'),
            ], 403);
        }

        try {
            $claim->delete();

            return response()->json([
                'message' => __('Insurance claim deleted successfully'),
                'redirect' => route('admin.insurance.claims.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting insurance claim: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Submit claim to insurance company
     */
    public function submit(InsuranceClaim $claim)
    {
        try {
            $claim = $this->insuranceService->submitClaim($claim);

            return response()->json([
                'message' => __('Insurance claim submitted successfully'),
                'redirect' => route('admin.insurance.claims.show', $claim),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error submitting insurance claim: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process claim approval/rejection
     */
    public function process(Request $request, InsuranceClaim $claim)
    {
        $request->validate([
            'status' => 'required|in:approved,partially_approved,rejected',
            'approved_amount' => 'nullable|numeric|min:0',
            'rejected_amount' => 'nullable|numeric|min:0',
            'rejection_reason' => 'nullable|string',
            'external_reference' => 'nullable|string',
        ]);

        try {
            $claim = $this->insuranceService->processClaim($claim, [
                'status' => $request->status,
                'approved_amount' => $request->approved_amount,
                'rejection_reason' => $request->rejection_reason,
                'external_reference' => $request->external_reference,
            ]);

            return response()->json([
                'message' => __('Insurance claim processed successfully'),
                'redirect' => route('admin.insurance.claims.show', $claim),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error processing insurance claim: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process claim payment
     */
    public function processPayment(Request $request, InsuranceClaim $claim)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $claim = $this->insuranceService->processPayment($claim, $request->amount);

            return response()->json([
                'message' => __('Insurance claim payment processed successfully'),
                'redirect' => route('admin.insurance.claims.show', $claim),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error processing insurance claim payment: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get claim statistics
     */
    public function statistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'company_id' => $request->company_id,
        ];

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->insuranceService->getClaimStatistics($businessId, $filters);

        return response()->json($statistics);
    }
}
