<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\ZSystSaleController;
use App\Http\Controllers\Controller;
use App\Models\Barcode;
use App\Models\DrugInteraction;
use App\Models\Product;
use App\Models\Stock;
use App\Services\AccountingService;
use App\Services\BarcodeService;
use App\Services\DeviceRegistryService;
use App\Services\DeviceSettings;
use App\Services\PromotionEngine;
use App\Services\ShiftService;
use Illuminate\Http\Request;

/**
 * PosController — the web sales board ("لوحة المبيعات").
 *
 * Scanner integration: the page captures keyboard-wedge input (fast burst
 * ending with Enter, the universal HID scanner behavior) and auto-resolves
 * the scanned code via lookup() — so a scanner "pops" the product straight
 * into the cart. Which printers to use and whether anything auto-prints
 * after checkout come from the device assignment + DeviceSettings.
 */
class PosController extends Controller
{
    public function __construct(
        protected DeviceRegistryService $registry,
        protected BarcodeService $barcodeService,
        protected PromotionEngine $promotions,
        protected ShiftService $shifts,
        protected AccountingService $accounting,
    ) {
        $this->middleware('permission:dashboard-read')->only('page');
        $this->middleware('permission:sales-create')->only('checkout');
    }

    /** The sales board. */
    public function page(Request $request)
    {
        $businessId = (int) $request->user()->business_id;
        $device = $this->registry->resolve($request);
        $device?->load(['barcodePrinter', 'receiptPrinter']);

        $openShift = $this->shifts->currentOpen($businessId);

        return view('admin.pos.index', [
            'device' => $device,
            'shiftOpen' => (bool) $openShift,
            'shiftNumber' => $openShift?->shift_number,
            'devicePayload' => $device?->clientPayload() ?? ['device' => null, 'printers' => ['barcode' => null, 'receipt' => null]],
            'settings' => DeviceSettings::clientPayload($businessId),
            'schedules' => \App\Http\Controllers\Admin\DoseLabelController::schedulesForClient(),
            'approved' => (bool) ($device?->is_approved ?? true),
        ]);
    }

    /**
     * Resolve a scanned/typed code to an add-to-cart payload.
     * (Barcodes lookup with product-code fallback, scoped to the tenant.)
     */
    public function lookup(Request $request)
    {
        $request->validate(['code' => 'required|string|max:64']);
        $businessId = (int) $request->user()->business_id;
        $code = trim((string) $request->input('code'));

        // 1. Minted barcode (label on the box).
        $barcode = Barcode::forBusiness($businessId)->active()
            ->where('barcode_number', $code)->first();
        if ($barcode) {
            $product = $barcode->product;
            $batch = $barcode->batch;

            return response()->json([
                'success' => true,
                'data' => [
                    'source' => 'barcode',
                    'product_id' => $product?->id,
                    'name' => $product?->name,
                    'price' => (float) ($product?->sales_price ?? 0),
                    'batch_no' => $batch?->batch_no,
                    'batch_id' => $batch?->id,
                    'expiry' => $batch?->expire_date?->toDateString(),
                    'stock' => $product ? (int) $product->stocks()->sum('product_stock') : 0,
                ],
            ]);
        }

        // 2. The product's own printed code.
        $product = Product::where('business_id', $businessId)
            ->where(fn ($q) => $q->where('product_code', $code)->orWhere('barcode', $code))
            ->first();

        if (! $product) {
            return response()->json(['success' => false, 'message' => __('كود غير معروف: :code', ['code' => $code])], 404);
        }

        $batch = $product->stocks()->orderByRaw('expire_date IS NULL, expire_date ASC')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'source' => 'product_code',
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) ($product->sales_price ?? 0),
                'batch_no' => $batch?->batch_no,
                'batch_id' => $batch?->id,
                'expiry' => $batch?->expire_date?->toDateString(),
                'stock' => (int) $product->stocks()->sum('product_stock'),
            ],
        ]);
    }

    /**
     * Drug–drug interaction screening for the current cart
     * (pharmacist safety net; returns warnings, never blocks — the
     * pharmacist decides, but every warning is shown loudly).
     */
    public function interactions(Request $request)
    {
        $data = $request->validate(['product_ids' => 'required|array|max:50']);
        $ids = array_map('intval', $data['product_ids']);
        $products = Product::whereIn('id', $ids)->get(['id', 'name']);
        $names = $products->pluck('name', 'id');

        $warnings = [];
        $seen = [];
        foreach ($products as $a) {
            foreach ($products as $b) {
                if ($a->id >= $b->id) {
                    continue;
                }
                $key = $a->id.':'.$b->id;
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;

                $hit = DrugInteraction::where(fn ($q) => $q
                    ->where('drug_a_name', 'like', '%'.$a->name.'%')->where('drug_b_name', 'like', '%'.$b->name.'%'))
                    ->orWhere(fn ($q) => $q
                    ->where('drug_a_name', 'like', '%'.$b->name.'%')->where('drug_b_name', 'like', '%'.$a->name.'%'))
                    ->first();

                if ($hit) {
                    $warnings[] = [
                        'severity' => $hit->severity,
                        'between' => [$a->name, $b->name],
                        'description' => $hit->description,
                        'recommendation' => $hit->recommendation,
                    ];
                }
            }
        }

        return response()->json(['success' => true, 'data' => $warnings]);
    }

    /**
     * Complete the sale through the hardened API store (server-side pricing
     * H-01, FEFO stock burn, transactional) — same code path as the POS app.
     * Promotions are evaluated SERVER-SIDE and applied as sale-level
     * discount (line prices stay at list price), then the sale is posted to
     * the open shift drawer + the light ledger.
     */
    public function checkout(Request $request)
    {
        $payload = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.price' => 'required|numeric',
            'products.*.loss_profit' => 'required|numeric',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.batch_no' => 'nullable|string',
            'products.*.expire_date' => 'nullable|string',
            'products.*.dose' => 'nullable|string|max:160',
            'products.*.dose_code' => 'nullable|string|max:24',
            'coupon_code' => 'nullable|string|max:40',
            'paid_amount' => 'nullable|numeric',
            'due_amount' => 'nullable|numeric',
            'is_paid' => 'nullable|boolean',
            'payment_type' => 'nullable|string',
        ]);

        $payload['sale_date'] = now()->toDateString();
        $payload['paid_amount'] ??= 0;
        $payload['due_amount'] ??= 0;
        $businessId0 = (int) $request->user()->business_id;

        // Server-side promotion evaluation (client never dictates discounts).
        $quote = $this->promotions->evaluate($businessId0, $payload['products'], $payload['coupon_code'] ?? null);
        if ($quote['discount'] > 0) {
            $payload['discount_amount'] = min(
                $quote['discount'],
                collect($payload['products'])->sum(fn ($l) => (float) $l['price'] * (int) $l['quantities'])
            );
        }

        $apiRequest = Request::create('/api/v1/sales', 'POST', $payload);
        $apiRequest->setUserResolver(fn () => $request->user());
        $apiRequest->setLaravelSession($request->session());
        $apiRequest->headers->set('Accept', 'application/json');
        $apiRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = app(ZSystSaleController::class)->store($apiRequest);
        $status = $response->getStatusCode();

        if ($status >= 300) {
            return response()->json(json_decode($response->getContent(), true), $status);
        }

        $body = json_decode($response->getContent(), true);
        $businessId = (int) $request->user()->business_id;
        $settings = DeviceSettings::all($businessId);

        // Cash drawer: attribute the sale + cash to the open shift.
        $shift = $this->shifts->currentOpen($businessId);
        $saleId = $body['data']['id'] ?? null;
        if ($shift && $saleId) {
            $this->shifts->addCash($shift, 'sale_cash', (float) ($payload['paid_amount'] ?? 0), 'POS sale', (int) $saleId);
            \App\Models\Sale::whereKey($saleId)->update(['shift_id' => $shift->id]);
        }

        // Light accounting: auto journal entry for the sale.
        if ($saleId) {
            try {
                $this->accounting->postSale(\App\Models\Sale::with('details')->find($saleId));
            } catch (\Throwable $e) {
                report($e); // ledger failure must not fail the sale
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('تم إتمام البيع بنجاح.'),
            'sale' => $body['data'] ?? null,
            'promotion' => $quote,
            'shift' => ['number' => $shift?->shift_number, 'open' => (bool) $shift],
            'print' => [
                // Per owner preference: sales never prompt for barcode labels
                // unless explicitly enabled; receipt follows its own rule.
                'barcode_url' => $settings['print']['barcode_on_sale']
                    ? $this->lastSaleLabelsUrl($payload, $businessId)
                    : null,
                'receipt_on_sale' => $settings['print']['receipt_on_sale'],
                'receipt_url' => in_array($settings['print']['receipt_on_sale'], ['auto', 'ask'], true)
                    ? ($body['data']['id'] ?? null ? route('admin.receipts.generate-sale') : null)
                    : null,
            ],
            // Dose labels — only when the pharmacist captured a schedule.
            'dose_labels_url' => (isset($settings['print']['dose_labels_on_sale']) && $settings['print']['dose_labels_on_sale']
                && collect($payload['products'])->contains(fn ($l) => filled($l['dose'] ?? null))
                && ($body['data']['id'] ?? null))
                ? route('admin.pos.dose-labels', ['sale' => $body['data']['id']])
                : null,
        ], 200);
    }

    private function lastSaleLabelsUrl(array $payload, int $businessId): ?string
    {
        $ids = Barcode::forBusiness($businessId)
            ->whereIn('product_id', collect($payload['products'])->pluck('product_id'))
            ->pluck('id')->take(20);

        return $ids->isNotEmpty() ? route('admin.barcodes.labels', ['ids' => $ids->implode(',')]) : null;
    }
}
