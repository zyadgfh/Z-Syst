<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierInvoicePaymentRequest;
use App\Http\Requests\SupplierInvoiceRequest;
use App\Http\Resources\SupplierInvoicePaymentResource;
use App\Http\Resources\SupplierInvoiceResource;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoicePayment;
use App\Services\SupplierInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierInvoiceController extends Controller
{
    protected SupplierInvoiceService $invoiceService;

    public function __construct(SupplierInvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of supplier invoices.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = SupplierInvoice::query()
            ->with(['supplier', 'items.product', 'payments'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('supplier_id')) {
            $query->forSupplier($request->supplier_id);
        }

        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        $invoices = $query->latest()->paginate($request->per_page ?? 15);

        return SupplierInvoiceResource::collection($invoices);
    }

    /**
     * Store a newly created supplier invoice.
     */
    public function store(SupplierInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $validated['business_id'] = $request->user()->business_id;
        $validated['branch_id'] = $request->user()->branch_id;

        $invoice = $this->invoiceService->create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice created successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ], 201);
    }

    /**
     * Display the specified supplier invoice.
     */
    public function show(SupplierInvoice $supplierInvoice): JsonResponse
    {
        $supplierInvoice->load(['supplier', 'items.product', 'payments', 'createdBy', 'approvedBy']);

        return response()->json([
            'success' => true,
            'data' => new SupplierInvoiceResource($supplierInvoice),
        ]);
    }

    /**
     * Update the specified supplier invoice.
     */
    public function update(SupplierInvoiceRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $validated = $request->validated();
        
        $invoice = $this->invoiceService->update($supplierInvoice, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice updated successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Remove the specified supplier invoice.
     */
    public function destroy(SupplierInvoice $supplierInvoice): JsonResponse
    {
        $this->invoiceService->delete($supplierInvoice);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice deleted successfully',
        ]);
    }

    /**
     * Approve supplier invoice.
     */
    public function approve(Request $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $invoice = $this->invoiceService->approve($supplierInvoice, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice approved successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Reject supplier invoice.
     */
    public function reject(Request $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $invoice = $this->invoiceService->reject($supplierInvoice, $request->user()->id, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice rejected successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Cancel supplier invoice.
     */
    public function cancel(Request $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $invoice = $this->invoiceService->cancel($supplierInvoice, $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Supplier invoice cancelled successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ]);
    }

    /**
     * Add payment to invoice.
     */
    public function addPayment(SupplierInvoicePaymentRequest $request, SupplierInvoice $supplierInvoice): JsonResponse
    {
        $payment = $this->invoiceService->addPayment($supplierInvoice, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Payment added successfully',
            'data' => new SupplierInvoicePaymentResource($payment),
        ], 201);
    }

    /**
     * Approve payment.
     */
    public function approvePayment(Request $request, $paymentId): JsonResponse
    {
        $payment = SupplierInvoicePayment::findOrFail($paymentId);
        $payment = $this->invoiceService->approvePayment($payment, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Payment approved successfully',
            'data' => new SupplierInvoicePaymentResource($payment),
        ]);
    }

    /**
     * Get pending invoices.
     */
    public function pending(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->invoiceService->getPending($request->user()->business_id);

        return SupplierInvoiceResource::collection($invoices);
    }

    /**
     * Get overdue invoices.
     */
    public function overdue(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->invoiceService->getOverdue($request->user()->business_id);

        return SupplierInvoiceResource::collection($invoices);
    }

    /**
     * Get unpaid invoices.
     */
    public function unpaid(Request $request): AnonymousResourceCollection
    {
        $invoices = $this->invoiceService->getUnpaid($request->user()->business_id);

        return SupplierInvoiceResource::collection($invoices);
    }

    /**
     * Get invoice statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->invoiceService->getStatistics($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get aging report.
     */
    public function agingReport(Request $request): JsonResponse
    {
        $report = $this->invoiceService->getAgingReport($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    /**
     * Create invoice from purchase.
     */
    public function createFromPurchase(Request $request): JsonResponse
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
        ]);

        $purchase = \App\Models\Purchase::findOrFail($request->purchase_id);
        $invoice = $this->invoiceService->createFromPurchase($purchase);

        return response()->json([
            'success' => true,
            'message' => 'Invoice created from purchase successfully',
            'data' => new SupplierInvoiceResource($invoice),
        ], 201);
    }
}
