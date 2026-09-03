<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEInvoiceRequest;
use App\Http\Requests\UpdateEInvoiceRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\EInvoicingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EInvoicingController extends Controller
{
    protected EInvoicingService $eInvoicingService;

    public function __construct(EInvoicingService $eInvoicingService)
    {
        $this->eInvoicingService = $eInvoicingService;
    }

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $data = Invoice::select('id', 'business_id', 'branch_id', 'invoice_number', 'invoice_date', 'total_amount', 'balance', 'status', 'einvoice_status')
            ->with('party:id,name,tax_id')
            ->where('business_id', Auth::user()->business_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('invoice_number', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhereHas('party', function ($q) use ($term) {
                            $q->where('name', 'like', $term)
                                ->orWhere('tax_id', 'like', $term);
                        });
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('einvoice_status'), function ($query) use ($request) {
                $query->where('einvoice_status', $request->input('einvoice_status'));
            })
            ->when($request->filled('from_date') || $request->filled('to_date'), function ($query) use ($request) {
                $query->whereBetween('invoice_date', [$request->input('from_date'), $request->input('to_date')]);
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreEInvoiceRequest $request)
    {
        $this->authorize('create', Invoice::class);

        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;

        // Generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $data['invoice_number'] = $this->generateInvoiceNumber($data['business_id']);
        }

        // Calculate balance
        $data['balance'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);

        $invoice = Invoice::create($data);

        // Create invoice items
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'business_id' => $data['business_id'],
                    'product_id' => $itemData['product_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'tax_id' => $itemData['tax_id'] ?? null,
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'tax_amount' => ($itemData['quantity'] * $itemData['unit_price']) * ($itemData['tax_rate'] ?? 0) / 100,
                    'unit_of_measure' => $itemData['unit_of_measure'] ?? 'EA',
                ]);
            }
        }

        $invoice->load('items');

        return response()->json([
            'message' => __('Invoice created successfully.'),
            'data' => $invoice,
        ], 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $invoice->load(['party', 'branch', 'tax', 'items.tax', 'payments']);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $invoice,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateEInvoiceRequest $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $data = $request->validated();
        $data['balance'] = $data['total_amount'] - ($data['paid_amount'] ?? 0);

        $invoice->update($data);

        // Update items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $invoice->items()->delete();
            foreach ($data['items'] as $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'business_id' => $invoice->business_id,
                    'product_id' => $itemData['product_id'] ?? null,
                    'product_name' => $itemData['product_name'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'tax_id' => $itemData['tax_id'] ?? null,
                    'tax_rate' => $itemData['tax_rate'] ?? 0,
                    'tax_amount' => ($itemData['quantity'] * $itemData['unit_price']) * ($itemData['tax_rate'] ?? 0) / 100,
                    'unit_of_measure' => $itemData['unit_of_measure'] ?? 'EA',
                ]);
            }
        }

        $invoice->load('items');

        return response()->json([
            'message' => __('Invoice updated successfully.'),
            'data' => $invoice->fresh(),
        ]);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);

        // Prevent deletion if submitted to e-invoicing
        if ($invoice->einvoice_status && in_array($invoice->einvoice_status, ['submitted', 'validated', 'accepted'])) {
            return response()->json([
                'message' => __('Cannot delete invoice that has been submitted to e-invoicing portal.'),
            ], 422);
        }

        $invoice->items()->delete();
        $invoice->delete();

        return response()->json([
            'message' => __('Invoice deleted successfully.'),
        ]);
    }

    /**
     * Validate invoice for e-invoicing compliance.
     */
    public function validateInvoice(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $validation = $this->eInvoicingService->validateInvoice($invoice);

        return response()->json([
            'message' => $validation['is_valid'] ? 'Invoice is valid for e-invoicing' : 'Invoice validation failed',
            'data' => $validation,
        ]);
    }

    /**
     * Submit invoice to e-invoicing portal.
     */
    public function submit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        if ($invoice->einvoice_status && in_array($invoice->einvoice_status, ['submitted', 'validated', 'accepted'])) {
            return response()->json([
                'message' => 'Invoice already submitted to e-invoicing portal',
                'data' => [
                    'einvoice_status' => $invoice->einvoice_status,
                    'einvoice_uuid' => $invoice->einvoice_uuid,
                    'einvoice_submitted_at' => $invoice->einvoice_submitted_at,
                ],
            ], 422);
        }

        $result = $this->eInvoicingService->submitInvoice($invoice);

        return response()->json([
            'message' => $result['success'] ? 'Invoice submitted successfully' : 'Invoice submission failed',
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Sync invoice status from e-invoicing portal.
     */
    public function sync(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        if (empty($invoice->einvoice_uuid)) {
            return response()->json([
                'message' => 'Invoice not submitted to e-invoicing portal',
            ], 422);
        }

        $result = $this->eInvoicingService->syncInvoiceStatus($invoice);

        return response()->json([
            'message' => $result['success'] ? 'Status synced successfully' : 'Status sync failed',
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Cancel invoice in e-invoicing portal.
     */
    public function cancel(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (empty($invoice->einvoice_uuid)) {
            return response()->json([
                'message' => 'Invoice not submitted to e-invoicing portal',
            ], 422);
        }

        $result = $this->eInvoicingService->cancelInvoice($invoice, $request->input('reason'));

        return response()->json([
            'message' => $result['success'] ? 'Invoice cancelled successfully' : 'Invoice cancellation failed',
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Generate QR code for invoice.
     */
    public function qrCode(Invoice $invoice)
    {
        $this->authorize('view', $invoice);

        $qrCode = $this->eInvoicingService->generateQrCode($invoice);

        return response()->json([
            'message' => 'QR code generated successfully',
            'data' => [
                'qr_code' => 'data:image/png;base64,' . base64_encode($qrCode),
            ],
        ]);
    }

    /**
     * Get e-invoicing portal status.
     */
    public function portalStatus()
    {
        $this->authorize('viewAny', Invoice::class);

        $status = $this->eInvoicingService->getPortalStatus();

        return response()->json([
            'message' => 'Portal status fetched successfully',
            'data' => $status,
        ]);
    }

    /**
     * Batch submit invoices to e-invoicing portal.
     */
    public function batchSubmit(Request $request)
    {
        $this->authorize('viewAny', Invoice::class);

        $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        $result = $this->eInvoicingService->batchSubmitInvoices($request->input('invoice_ids'));

        return response()->json([
            'message' => 'Batch submission completed',
            'data' => $result,
        ]);
    }

    /**
     * Generate invoice number.
     */
    protected function generateInvoiceNumber(int $businessId): string
    {
        $count = Invoice::where('business_id', $businessId)->count() + 1;
        return 'INV-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }
}