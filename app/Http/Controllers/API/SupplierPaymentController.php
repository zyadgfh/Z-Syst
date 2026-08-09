<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierPaymentRequest;
use App\Http\Resources\SupplierPaymentResource;
use App\Models\SupplierPayment;
use App\Services\SupplierPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierPaymentController extends Controller
{
    protected SupplierPaymentService $paymentService;

    public function __construct(SupplierPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SupplierPayment::query()
            ->with(['supplier', 'createdBy', 'approvedBy'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate($request->per_page ?? 15);

        return SupplierPaymentResource::collection($payments);
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

    public function show(SupplierPayment $payment): JsonResponse
    {
        $payment->load(['supplier', 'createdBy', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => new SupplierPaymentResource($payment),
        ]);
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
