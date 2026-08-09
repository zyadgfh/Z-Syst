<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Receipt;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    protected ReceiptService $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
        $this->middleware('permission:receipts-read')->only('index', 'settings', 'show');
        $this->middleware('permission:receipts-create')->only('generateSale', 'generatePurchase');
        $this->middleware('permission:receipts-update')->only('updateSettings', 'markPrinted', 'regenerate');
        $this->middleware('permission:receipts-delete')->only('destroy');
    }

    public function index()
    {
        $businessId = auth()->user()->business_id;
        $receipts = Receipt::forBusiness($businessId)
            ->with(['sale:id,receipt_number,totalAmount', 'purchase:id,receipt_number,totalAmount', 'user:id,name']) // Fix N+1 with selective loading
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.receipts.index', compact('receipts'));
    }

    public function settings()
    {
        $businessId = auth()->user()->business_id;
        $settings = $this->receiptService->getSettings($businessId);

        return view('admin.receipts.settings', compact('settings'));
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['sale', 'purchase', 'user']);
        $settings = $this->receiptService->getSettings($receipt->business_id);

        return view('admin.receipts.show', compact('receipt', 'settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'receipt_header' => 'nullable|string|max:255',
            'receipt_footer' => 'nullable|string|max:500',
            'tax_number' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'show_qr' => 'nullable|in:0,1',
            'show_barcode' => 'nullable|in:0,1',
            'paper_size' => 'nullable|in:a4,a5,letter',
            'is_active' => 'boolean',
        ]);

        $businessId = auth()->user()->business_id ?? 1;
        $setting = $this->receiptService->updateSettings($businessId, $validated);

        return back()->with('success', __('Receipt settings updated successfully.'));
    }

    /**
     * Generate sale receipt
     */
    public function generateSale(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'format' => 'required|in:pdf,html,thermal',
        ]);

        try {
            $receipt = $this->receiptService->generateSaleReceipt(
                $request->sale_id,
                $request->format
            );

            return response()->json([
                'message' => __('Sale receipt generated successfully'),
                'receipt' => $receipt,
                'redirect' => route('admin.receipts.show', $receipt),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating sale receipt: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate purchase receipt
     */
    public function generatePurchase(Request $request)
    {
        $request->validate([
            'purchase_id' => 'required|exists:purchases,id',
            'format' => 'required|in:pdf,html,thermal',
        ]);

        try {
            $receipt = $this->receiptService->generatePurchaseReceipt(
                $request->purchase_id,
                $request->format
            );

            return response()->json([
                'message' => __('Purchase receipt generated successfully'),
                'receipt' => $receipt,
                'redirect' => route('admin.receipts.show', $receipt),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating purchase receipt: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download PDF receipt
     */
    public function downloadPdf(Receipt $receipt)
    {
        try {
            return $this->receiptService->generatePdf($receipt);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating PDF: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * View HTML receipt
     */
    public function viewHtml(Receipt $receipt)
    {
        try {
            $html = $this->receiptService->generateHtml($receipt);

            return response($html)->header('Content-Type', 'text/html');
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error generating HTML: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark receipt as printed
     */
    public function markPrinted(Receipt $receipt)
    {
        try {
            $receipt = $this->receiptService->markAsPrinted($receipt);

            return response()->json([
                'message' => __('Receipt marked as printed successfully'),
                'receipt' => $receipt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error marking receipt as printed: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Regenerate receipt
     */
    public function regenerate(Receipt $receipt)
    {
        try {
            $receipt = $this->receiptService->regenerateReceipt($receipt);

            return response()->json([
                'message' => __('Receipt regenerated successfully'),
                'receipt' => $receipt,
                'redirect' => route('admin.receipts.show', $receipt),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error regenerating receipt: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete receipt
     */
    public function destroy(Receipt $receipt)
    {
        try {
            $receipt->delete();

            return response()->json([
                'message' => __('Receipt deleted successfully'),
                'redirect' => route('admin.receipts.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting receipt: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get receipt statistics
     */
    public function statistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'type' => $request->type,
        ];

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->receiptService->getStatistics($businessId, $filters);

        return response()->json($statistics);
    }
}
