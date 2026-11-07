<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BarcodeRequest;
use App\Http\Resources\BarcodeResource;
use App\Models\Barcode;
use App\Models\Product;
use App\Models\Stock;
use App\Services\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class BarcodeController extends Controller
{
    protected BarcodeService $barcodeService;

    public function __construct(BarcodeService $barcodeService)
    {
        $this->barcodeService = $barcodeService;
        $this->middleware('permission:barcodes-read')->only('page', 'pageShow', 'labels', 'image');
        $this->middleware('permission:barcodes-create')->only('pageCreate', 'pageStore');
    }

    /*
     * ---------------------------------------------------------------------
     * Admin web pages (Blade) — the JSON API surface lives in Api\BarcodeController.
     * ---------------------------------------------------------------------
     */

    /** Barcode list page. */
    public function page(Request $request)
    {
        $businessId = $request->user()->business_id;

        $barcodes = Barcode::query()
            ->with(['product', 'batch'])
            ->forBusiness($businessId)
            ->byType($request->type)
            ->byPrintStatus($request->print_status)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.barcodes.index', [
            'barcodes' => $barcodes,
            'types' => [Barcode::TYPE_CODE128, Barcode::TYPE_EAN13, Barcode::TYPE_UPC, Barcode::TYPE_QR],
            'statuses' => [Barcode::STATUS_NOT_PRINTED, Barcode::STATUS_PRINTED, Barcode::STATUS_REPRINTED],
        ]);
    }

    /** Generate form. */
    public function pageCreate(Request $request)
    {
        $products = Product::query()
            ->forBusiness($request->user()->business_id)
            ->orderBy('productName')
            ->get(['id', 'productName', 'productCode', 'sales_price']);

        return view('admin.barcodes.create', ['products' => $products]);
    }

    /** Handle the generate form. */
    public function pageStore(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'batch_id' => 'nullable|integer',
            'type' => 'required|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $businessId = (int) $request->user()->business_id;

        $product = Product::forBusiness($businessId)->findOrFail($validated['product_id']);
        $options = [
            'barcode_type' => $validated['type'],
            'size' => $validated['size'] ?? Barcode::SIZE_STANDARD,
        ];

        if (! empty($validated['batch_id'])) {
            $batch = Stock::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->findOrFail($validated['batch_id']);
            $created = $this->barcodeService->generateMultipleForBatch($batch, $validated['quantity'], $options);
        } else {
            $created = $this->barcodeService->generateMultipleForProduct($product, $validated['quantity'], $options);
        }

        return redirect()
            ->route('admin.barcodes.labels', ['ids' => implode(',', array_map(fn ($b) => $b->id, $created))])
            ->with('success', __('تم توليد :n باركود بنجاح.', ['n' => count($created)]));
    }

    /** Single barcode page (label preview). */
    public function pageShow(Request $request, Barcode $barcode)
    {
        abort_unless((int) $barcode->business_id === (int) $request->user()->business_id, 404);
        $barcode->load(['product', 'batch']);

        return view('admin.barcodes.show', ['barcode' => $barcode]);
    }

    /** Printable label sheet (?ids=1,2,3 or product_id + quantity). */
    public function labels(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        if ($request->filled('ids')) {
            $ids = collect(explode(',', (string) $request->input('ids')))
                ->map(fn ($v) => (int) trim($v))
                ->filter()
                ->unique()
                ->take(200)
                ->all();

            $barcodes = Barcode::with(['product', 'batch'])
                ->forBusiness($businessId)
                ->whereIn('id', $ids)
                ->get();
        } else {
            $request->validate([
                'product_id' => 'required|integer',
                'quantity' => 'required|integer|min:1|max:100',
            ]);

            $product = Product::forBusiness($businessId)->findOrFail($request->input('product_id'));
            $barcodes = collect($this->barcodeService->generateMultipleForProduct(
                $product,
                (int) $request->input('quantity'),
                ['barcode_type' => $request->input('type', Barcode::TYPE_CODE128)]
            ));
            $barcodes->each->load(['product', 'batch']);
        }

        abort_if($barcodes->isEmpty(), 404);

        return view('admin.barcodes.labels', ['barcodes' => $barcodes]);
    }

    /** Stream the stored label image (PNG or SVG); lazily re-renders when
     *  the artifact was pruned from disk (e.g. snapshot refresh). */
    public function image(Request $request, Barcode $barcode)
    {
        abort_unless((int) $barcode->business_id === (int) $request->user()->business_id, 404);

        $path = (string) $barcode->barcode_image;
        if ($path !== '' && ! str_contains($path, '..') && str_starts_with($path, 'barcodes/')
            && Storage::disk('public')->exists($path)) {
            $mime = str_ends_with($path, '.svg') ? 'image/svg+xml' : 'image/png';

            return response(Storage::disk('public')->get($path), 200)
                ->header('Content-Type', $mime)
                ->header('Cache-Control', 'public, max-age=3600');
        }

        // Lazy render: the stored artifact is gone — draw it from the number.
        $renderer = new \App\Support\Barcode\BarcodeRenderer();
        $png = $renderer->png($barcode->barcode_type, $barcode->barcode_number, $barcode->size ?? 'standard');
        if ($png !== null) {
            return response($png, 200)->header('Content-Type', 'image/png')->header('Cache-Control', 'public, max-age=3600');
        }

        return response($renderer->svg($barcode->barcode_type, $barcode->barcode_number, $barcode->size ?? 'standard'), 200)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'public, max-age=3600');
    }


    /**
     * Display a listing of barcodes.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Barcode::query()
            ->with(['product', 'batch', 'printedBy'])
            ->forBusiness($request->user()->business_id);

        if ($request->has('product_id')) {
            $query->forProduct($request->product_id);
        }

        if ($request->has('batch_id')) {
            $query->forBatch($request->batch_id);
        }

        if ($request->has('print_status')) {
            $query->byPrintStatus($request->print_status);
        }

        if ($request->has('type')) {
            $query->byType($request->type);
        }

        $barcodes = $query->latest()->paginate($request->per_page ?? 15);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Store a newly created barcode.
     */
    public function store(BarcodeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($request->has('batch_id')) {
            $batch = Stock::findOrFail($validated['batch_id']);
            $barcode = $this->barcodeService->generateForBatch($batch, $validated);
        } else {
            $product = Product::findOrFail($validated['product_id']);
            $barcode = $this->barcodeService->generateForProduct($product, $validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barcode generated successfully',
            'data' => new BarcodeResource($barcode),
        ], 201);
    }

    /**
     * Display the specified barcode.
     */
    public function show(Barcode $barcode): JsonResponse
    {
        $barcode->load(['product', 'batch', 'printedBy', 'business', 'branch']);

        return response()->json([
            'success' => true,
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Update the specified barcode.
     */
    public function update(BarcodeRequest $request, Barcode $barcode): JsonResponse
    {
        $validated = $request->validated();
        
        $barcode->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Barcode updated successfully',
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Remove the specified barcode.
     */
    public function destroy(Barcode $barcode): JsonResponse
    {
        $this->barcodeService->deleteBarcode($barcode);

        return response()->json([
            'success' => true,
            'message' => 'Barcode deleted successfully',
        ]);
    }

    /**
     * Generate multiple barcodes for a product.
     */
    public function generateMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
            'type' => 'nullable|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
        ]);

        $product = Product::findOrFail($request->product_id);
        $barcodes = $this->barcodeService->generateMultipleForProduct(
            $product,
            $request->quantity,
            $request->only(['type', 'size', 'print_settings'])
        );

        return response()->json([
            'success' => true,
            'message' => "Generated {$request->quantity} barcodes successfully",
            'data' => BarcodeResource::collection($barcodes),
        ], 201);
    }

    /**
     * Generate multiple barcodes for a batch.
     */
    public function generateForBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1|max:100',
            'type' => 'nullable|in:CODE128,EAN13,UPC,QR',
            'size' => 'nullable|in:small,standard,large',
        ]);

        $batch = Stock::findOrFail($request->batch_id);
        $barcodes = $this->barcodeService->generateMultipleForBatch(
            $batch,
            $request->quantity,
            $request->only(['type', 'size', 'print_settings'])
        );

        return response()->json([
            'success' => true,
            'message' => "Generated {$request->quantity} barcodes successfully",
            'data' => BarcodeResource::collection($barcodes),
        ], 201);
    }

    /**
     * Print a single barcode.
     */
    public function print(Request $request, Barcode $barcode): JsonResponse
    {
        $pdfPath = $this->barcodeService->printBarcode($barcode, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Barcode printed successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => route('barcodes.download', $pdfPath),
            ],
        ]);
    }

    /**
     * Print multiple barcodes.
     */
    public function printMultiple(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_ids' => 'required|array',
            'barcode_ids.*' => 'exists:barcodes,id',
        ]);

        $pdfPath = $this->barcodeService->printMultipleBarcodes(
            $request->barcode_ids,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Barcodes printed successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => route('barcodes.download', $pdfPath),
            ],
        ]);
    }

    /**
     * Print barcodes for a product.
     */
    public function printForProduct(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($request->product_id);
        $pdfPath = $this->barcodeService->printForProduct(
            $product,
            $request->quantity,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => "Generated and printed {$request->quantity} barcodes successfully",
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => route('barcodes.download', $pdfPath),
            ],
        ]);
    }

    /**
     * Print barcodes for a batch.
     */
    public function printForBatch(Request $request): JsonResponse
    {
        $request->validate([
            'batch_id' => 'required|exists:stocks,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $batch = Stock::findOrFail($request->batch_id);
        $pdfPath = $this->barcodeService->printForBatch(
            $batch,
            $request->quantity,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => "Generated and printed {$request->quantity} barcodes successfully",
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => route('barcodes.download', $pdfPath),
            ],
        ]);
    }

    /**
     * Reprint a barcode.
     */
    public function reprint(Request $request, Barcode $barcode): JsonResponse
    {
        $pdfPath = $this->barcodeService->reprintBarcode($barcode, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Barcode reprinted successfully',
            'data' => [
                'pdf_url' => Storage::url($pdfPath),
                'download_url' => route('barcodes.download', $pdfPath),
            ],
        ]);
    }

    /**
     * Download barcode PDF.
     */
    public function download(Request $request, string $filename)
    {
        $path = storage_path('app/public/' . $filename);
        
        if (!file_exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found',
            ], 404);
        }

        return response()->download($path);
    }

    /**
     * Search barcode by number.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_number' => 'required|string',
        ]);

        $barcode = $this->barcodeService->searchByNumber(
            $request->barcode_number,
            $request->user()->business_id
        );

        if (!$barcode) {
            return response()->json([
                'success' => false,
                'message' => 'Barcode not found',
            ], 404);
        }

        $barcode->load(['product', 'batch']);

        return response()->json([
            'success' => true,
            'data' => new BarcodeResource($barcode),
        ]);
    }

    /**
     * Get barcode settings.
     */
    public function settings(Request $request): JsonResponse
    {
        $settings = $this->barcodeService->getBarcodeSettings($request->only([
        'product_id',
        'batch_id',
        'barcode_number',
        'barcode_type',
        'barcode_image',
        'print_status',
        'printed_at',
        'printed_by',
        'print_count',
        'size',
        'print_settings',
        'is_active',
        'business_id',
        'branch_id',
        'created_by',
        'updated_by'
    ]));

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Get not printed barcodes.
     */
    public function notPrinted(Request $request): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getNotPrinted($request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Get barcodes by product.
     */
    public function byProduct(Request $request, int $productId): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getByProduct($productId, $request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Get barcodes by batch.
     */
    public function byBatch(Request $request, int $batchId): AnonymousResourceCollection
    {
        $barcodes = $this->barcodeService->getByBatch($batchId, $request->user()->business_id);

        return BarcodeResource::collection($barcodes);
    }

    /**
     * Restore deleted barcode.
     */
    public function restore(Request $request, int $barcodeId): JsonResponse
    {
        $this->barcodeService->restoreBarcode($barcodeId);

        return response()->json([
            'success' => true,
            'message' => 'Barcode restored successfully',
        ]);
    }
}
