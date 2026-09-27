<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptSettingRequest;
use App\Http\Resources\ReceiptResource;
use App\Http\Resources\ReceiptSettingResource;
use App\Models\Purchase;
use App\Models\Receipt;
use App\Models\Sale;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    protected ReceiptService $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    public function settings(Request $request)
    {
        $settings = $this->receiptService->getOrCreateSettings($request->user()->business_id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => new ReceiptSettingResource($settings),
        ]);
    }

    public function updateSettings(StoreReceiptSettingRequest $request)
    {
        $validated = $request->validated();

        $settings = $this->receiptService->getOrCreateSettings($request->user()->business_id);
        $settings->update($validated);

        return response()->json([
            'message' => __('Settings updated successfully.'),
            'data' => new ReceiptSettingResource($settings),
        ]);
    }

    public function generateForSale(Request $request, Sale $sale)
    {
        if ($sale->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $receipt = $this->receiptService->generateForSale($sale);

        return response()->json([
            'message' => __('Receipt generated successfully.'),
            'data' => new ReceiptResource($receipt),
        ], 201);
    }

    public function generateForPurchase(Request $request, Purchase $purchase)
    {
        if ($purchase->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $receipt = $this->receiptService->generateForPurchase($purchase);

        return response()->json([
            'message' => __('Receipt generated successfully.'),
            'data' => new ReceiptResource($receipt),
        ], 201);
    }

    public function show(Request $request, Receipt $receipt)
    {
        if ($receipt->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $receipt->load(['sale', 'purchase', 'user']);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => new ReceiptResource($receipt),
        ]);
    }

    public function download(Request $request, Receipt $receipt)
    {
        if ($receipt->business_id !== $request->user()->business_id) {
            abort(403, __('Forbidden.'));
        }

        $receipt->load(['sale', 'purchase', 'user']);

        $pdf = $this->receiptService->renderPdf($receipt);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf;
        }, $receipt->receipt_number.'.pdf', [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt->receipt_number.'.pdf"',
        ]);
    }
}
