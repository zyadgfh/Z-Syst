<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\ZSystSaleController;
use App\Http\Controllers\Controller;
use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use App\Services\BarcodeService;
use App\Services\DeviceRegistryService;
use App\Services\DeviceSettings;
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

        return view('admin.pos.index', [
            'device' => $device,
            'devicePayload' => $device?->clientPayload() ?? ['device' => null, 'printers' => ['barcode' => null, 'receipt' => null]],
            'settings' => DeviceSettings::clientPayload($businessId),
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
                    'stock' => $product ? (int) $product->stocks()->sum('productStock') : 0,
                ],
            ]);
        }

        // 2. The product's own printed code.
        $product = Product::where('business_id', $businessId)
            ->where(fn ($q) => $q->where('productCode', $code)->orWhere('barcode', $code))
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
                'stock' => (int) $product->stocks()->sum('productStock'),
            ],
        ]);
    }

    /**
     * Complete the sale through the hardened API store (server-side pricing
     * H-01, FEFO stock burn, transactional) — same code path as the POS app.
     */
    public function checkout(Request $request)
    {
        $payload = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer',
            'products.*.price' => 'required|numeric',
            'products.*.lossProfit' => 'required|numeric',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.batch_no' => 'nullable|string',
            'paidAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string',
        ]);

        $payload['saleDate'] = now()->toDateString();
        $payload['paidAmount'] ??= 0;
        $payload['dueAmount'] ??= 0;

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

        return response()->json([
            'success' => true,
            'message' => __('تم إتمام البيع بنجاح.'),
            'sale' => $body['data'] ?? null,
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
