<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierPaymentRequest;
use App\Http\Resources\SupplierPaymentResource;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierPaymentController extends Controller
{
    protected SupplierPaymentService $paymentService;

    public function __construct(SupplierPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = SupplierPayment::query()
            ->with(['supplier', 'createdBy', 'approvedBy'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate($request->per_page ?? 15);

        if ($request->wantsJson()) {
            return SupplierPaymentResource::collection($payments);
        }

        return view('admin.supplier-payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::forBusiness($request->user()->business_id)->active()->get();

        return view('admin.supplier-payments.create', compact('suppliers'));
    }

    public function store(SupplierPaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['branch_id'] = $request->user()->branch_id;
        $validated['created_by'] = $request->user()->id;

        $payment = $this->paymentService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => new SupplierPaymentResource($payment),
        ], 201);
    }

    public function show(Request $request, SupplierPayment $payment)
    {
        $payment->load(['supplier', 'createdBy', 'approvedBy']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => new SupplierPaymentResource($payment),
            ]);
        }

        return view('admin.supplier-payments.show', compact('payment'));
    }

    public function edit(Request $request, SupplierPayment $payment)
    {
        $suppliers = Supplier::forBusiness($request->user()->business_id)->active()->get();

        return view('admin.supplier-payments.edit', compact('payment', 'suppliers'));
    }

    public function update(SupplierPaymentRequest $request, SupplierPayment $payment): JsonResponse
    {
        $validated = $request->validated();
        $payment->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => new SupplierPaymentResource($payment),
        ]);
    }

    public function destroy(SupplierPayment $payment): JsonResponse
    {
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
        ]);
    }

    public function approve(Request $request, SupplierPayment $payment): JsonResponse
    {
        $payment = $this->paymentService->approve($payment, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'data' => new SupplierPaymentResource($payment),
        ]);
    }

    public function generateAgingReport(Request $request): JsonResponse
    {
        $this->paymentService->generateAgingReport($request->user()->business_id);

        return response()->json([
            'success' => true,
            'message' => 'Aging report generated successfully',
        ]);
    }
}
