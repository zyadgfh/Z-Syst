<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\ItemPrintHistory;
use App\Models\ItemPriceHistory;
use App\Models\Manufacturer;
use App\Models\Party;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $productService
    ) {}

    /**
     * Display the items management list page.
     */
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $filters = $request->only([
            'search', 'category_id', 'subcategory_id', 'brand_id', 'manufacturer_id',
            'active', 'stock_status', 'prescription_required', 'tax_id',
            'min_price', 'max_price', 'expire_date', 'expired', 'expiring_soon',
            'sort', 'direction', 'branch_id',
        ]);

        $products = $this->productService->list($filters, $businessId, $request->input('per_page', 15));

        // Dropdown data
        $categories = Category::where('business_id', $businessId)->where('status', 1)->orderBy('categoryName')->get();
        $manufacturers = Manufacturer::where('business_id', $businessId)->where('status', 1)->orderBy('name')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
        }

        return view('admin.products.index', compact('products', 'categories', 'manufacturers', 'filters'));
    }

    /**
     * Show the create product form.
     */
    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        $categories = Category::where('business_id', $businessId)->where('status', 1)->orderBy('categoryName')->get();
        $units = Unit::where('business_id', $businessId)->where('status', 1)->orderBy('unitName')->get();
        $manufacturers = Manufacturer::where('business_id', $businessId)->where('status', 1)->orderBy('name')->get();
        $suppliers = Party::where('business_id', $businessId)->where('type', 'supplier')->orderBy('name')->get();

        return view('admin.products.create', compact('categories', 'units', 'manufacturers', 'suppliers'));
    }

    /**
     * Store a new product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $validated['business_id'] = $request->user()->business_id;

            if ($request->has('branch_id')) {
                $validated['branch_id'] = $request->user()->branch_id;
            }

            $product = $this->productService->createProduct($validated, $request->user()->business_id);

            return response()->json([
                'success' => true,
                'message' => __('Product created successfully.'),
                'data' => $product,
                'redirect' => route('admin.items.show', $product->id),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('Validation failed.'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while creating the product.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the item details page.
     */
    public function show(Request $request, int $id)
    {
        $businessId = $request->user()->business_id;

        $product = $this->productService->show($id, $businessId);
        $kpis = $this->productService->getProductKPIs($id, $businessId);

        // Stock movements (filtered)
        $movementQuery = \App\Models\StockMovement::where('product_id', $id)
            ->with(['user:id,name', 'stock:id,batch_no']);

        if ($request->filled('movement_type')) {
            $movementQuery->where('movement_type', $request->input('movement_type'));
        }
        if ($request->filled('movement_from')) {
            $movementQuery->whereDate('created_at', '>=', $request->input('movement_from'));
        }
        if ($request->filled('movement_to')) {
            $movementQuery->whereDate('created_at', '<=', $request->input('movement_to'));
        }
        if ($request->filled('movement_user_id')) {
            $movementQuery->where('user_id', $request->input('movement_user_id'));
        }

        $stockMovements = $movementQuery->latest()->paginate(15);

        // Stock movement summary stats
        $stockMovementStats = [
            'total_in' => \App\Models\StockMovement::where('product_id', $id)
                ->where('movement_type', 'in')->sum('quantity'),
            'total_out' => \App\Models\StockMovement::where('product_id', $id)
                ->where('movement_type', 'out')->sum('quantity'),
            'total_adjustments' => \App\Models\StockMovement::where('product_id', $id)
                ->where('movement_type', 'adjustment')->count(),
            'total_movements' => \App\Models\StockMovement::where('product_id', $id)->count(),
        ];
        $stockMovementStats['net_change'] = $stockMovementStats['total_in'] - $stockMovementStats['total_out'];

        // Unique users who performed movements (for filter dropdown)
        $movementUsers = \App\Models\StockMovement::where('product_id', $id)
            ->join('users', 'stock_movements.user_id', '=', 'users.id')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();

        // Recent sales
        $recentSales = \App\Models\SaleDetails::where('product_id', $id)
            ->with('sale:id,invoiceNumber,saleDate,totalAmount')
            ->latest()
            ->limit(10)
            ->get();

        // Recent purchases
        $recentPurchases = \App\Models\PurchaseDetails::where('product_id', $id)
            ->with('purchase:id,invoiceNumber,purchaseDate,totalAmount')
            ->latest()
            ->limit(10)
            ->get();

        // Audit logs (stored in meta column)
        $auditLogs = \App\Models\AuditLog::where('action', 'like', '%Product%')
            ->whereRaw("JSON_EXTRACT(meta, '$.model_id') = ?", [$id])
            ->whereRaw("JSON_EXTRACT(meta, '$.model_type') = ?", [Product::class])
            ->with('user:id,name')
            ->latest()
            ->limit(20)
            ->get();

        // Print history
        $printHistory = ItemPrintHistory::where('product_id', $id)
            ->with('user:id,name')
            ->latest()
            ->limit(30)
            ->get();

        // Price history (for chart)
        $priceHistory = ItemPriceHistory::where('product_id', $id)
            ->with('user:id,name')
            ->latest()
            ->limit(50)
            ->get();

        // Unified timeline (audit + stock movements + price changes + print history)
        $timelineEvents = $this->buildTimeline($id, $auditLogs, $stockMovements, $priceHistory, $printHistory);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $product,
                'kpis' => $kpis,
            ]);
        }        return view('admin.products.show', compact(
            'product', 'kpis', 'stockMovements', 'stockMovementStats', 'movementUsers',
            'recentSales', 'recentPurchases', 'auditLogs', 'printHistory',
            'priceHistory', 'timelineEvents'
        ));
    }

    /**
     * Show the edit product form.
     */
    public function edit(Request $request, int $id)
    {
        $businessId = $request->user()->business_id;
        $product = $this->productService->show($id, $businessId);

        $categories = Category::where('business_id', $businessId)->where('status', 1)->orderBy('categoryName')->get();
        $units = Unit::where('business_id', $businessId)->where('status', 1)->orderBy('unitName')->get();
        $manufacturers = Manufacturer::where('business_id', $businessId)->where('status', 1)->orderBy('name')->get();
        $suppliers = Party::where('business_id', $businessId)->where('type', 'supplier')->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'units', 'manufacturers', 'suppliers'));
    }

    /**
     * Update a product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        // Business ownership check: prevent cross-tenant modification
        if ($product->business_id !== $request->user()->business_id) {
            return response()->json([
                'success' => false,
                'message' => __('Not authorized to update this item.'),
            ], 403);
        }

        try {
            $validated = $request->validated();
            $updatedProduct = $this->productService->updateProduct($product, $validated, $request->user()->business_id);

            return response()->json([
                'success' => true,
                'message' => __('Product updated successfully.'),
                'data' => $updatedProduct,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while updating the product.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete/archive a product.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        // Business ownership check: prevent cross-tenant deletion
        if ($product->business_id !== $request->user()->business_id) {
            return response()->json([
                'success' => false,
                'message' => __('Not authorized to delete this item.'),
            ], 403);
        }

        try {
            $this->productService->deleteProduct($product);

            $message = $product->archived
                ? __('Product archived successfully (has transaction history).')
                : __('Product deleted successfully.');

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred while deleting the product.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check for duplicate products.
     */
    public function checkDuplicates(Request $request): JsonResponse
    {
        $data = $request->only([
            'productName', 'barcode', 'sku', 'productCode', 'scientific_name',
        ]);

        $excludeId = $request->input('exclude_id');
        $duplicates = $this->productService->checkDuplicates($data, $excludeId);

        return response()->json([
            'success' => true,
            'has_duplicates' => !empty($duplicates),
            'duplicates' => array_map(function ($product) {
                return [
                    'id' => $product->id,
                    'productName' => $product->productName,
                    'productCode' => $product->productCode,
                    'barcode' => $product->barcode,
                    'sku' => $product->sku,
                ];
            }, $duplicates),
        ]);
    }

    /**
     * Search products (used by barcode scanner and product search).
     */
    public function search(Request $request): JsonResponse
    {
        $term = $request->input('q', '');
        $businessId = $request->user()->business_id;
        $branchId = $request->user()->branch_id;

        $products = $this->productService->searchProducts($term, $businessId, $branchId);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Search by barcode.
     */
    public function searchByBarcode(Request $request): JsonResponse
    {
        $barcode = $request->input('barcode', '');
        $businessId = $request->user()->business_id;

        $product = $this->productService->searchByBarcode($barcode, $businessId);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => __('No product found with this barcode.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    /**
     * Bulk update products.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'integer|exists:products,id',
            'data' => 'required|array',
        ]);

        try {
            $result = $this->productService->bulkUpdate(
                $request->product_ids,
                $request->data,
                $request->user()->business_id
            );

            return response()->json([
                'success' => true,
                'message' => __('Bulk update completed. :success succeeded, :failed failed.', [
                    'success' => $result['success_count'],
                    'failed' => $result['failed_count'],
                ]),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred during bulk update.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export products to CSV.
     */
    public function export(Request $request)
    {
        $businessId = $request->user()->business_id;
        $filters = $request->only(['category_id', 'brand_id', 'active']);

        $data = $this->productService->exportProducts($businessId, $filters);

        if (empty($data)) {
            return response()->json([
                'success' => false,
                'message' => __('No products to export.'),
            ], 404);
        }

        $headers = array_keys($data[0]);

        $callback = function () use ($data, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);

            foreach ($data as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        };

        $filename = 'products_export_' . now()->format('Y-m-d_His') . '.csv';

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Import products from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            $headers = fgetcsv($handle);

            $rows = [];
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = array_combine($headers, $row);
            }
            fclose($handle);

            $result = $this->productService->importProducts($rows, $request->user()->business_id);

            return response()->json([
                'success' => true,
                'message' => __('Import completed. :success succeeded, :failed failed.', [
                    'success' => $result['success_count'],
                    'failed' => $result['failed_count'],
                ]),
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('An error occurred during import.'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate internal code for the next product.
     */
    public function generateInternalCode(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        $code = Product::generateInternalCode($businessId);

        return response()->json([
            'success' => true,
            'internal_code' => $code,
        ]);
    }

    /**
     * Get product statistics for the dashboard.
     */
    public function statistics(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;

        $totalProducts = Product::where('business_id', $businessId)->count();
        $activeProducts = Product::where('business_id', $businessId)->where('active', true)->count();
        $lowStockProducts = Product::where('business_id', $businessId)->lowStock()->count();
        $outOfStockProducts = Product::where('business_id', $businessId)->outOfStock()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalProducts,
                'active' => $activeProducts,
                'low_stock' => $lowStockProducts,
                'out_of_stock' => $outOfStockProducts,
            ],
        ]);
    }

    /**
     * Adjust stock for a product (stock in, stock out, or set exact quantity).
     */
    public function stockAdjust(Request $request, int $id)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'adjustment_type' => 'required|in:in,out,adjustment',
            'quantity' => 'required|integer|min:0',
            'stock_id' => 'nullable|integer|exists:stocks,id',
            'batch_no' => 'nullable|string|max:100',
            'expire_date' => 'nullable|date|after_or_equal:today',
            'purchase_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->firstOrFail();

        try {
            $stockService = app(\App\Services\Stock\StockAllocationService::class);
            $userId = $request->user()->id;
            $movement = null;

            DB::beginTransaction();

            switch ($validated['adjustment_type']) {
                case 'in':
                    // Stock In: find or create a stock batch, then add
                    $stock = $this->findOrCreateStockBatch(
                        $product, $businessId, $validated, $userId
                    );
                    $movement = $stockService->addStock(
                        $stock, $validated['quantity'],
                        'App\\Models\\Product', $product->id,
                        $userId, $validated['notes'] ?? null
                    );
                    break;

                case 'out':
                    // Stock Out: must select an existing batch
                    if (empty($validated['stock_id'])) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => __('Please select a batch to remove stock from.'),
                        ], 422);
                    }
                    $stock = Stock::where('id', $validated['stock_id'])
                        ->where('business_id', $businessId)
                        ->where('product_id', $product->id)
                        ->firstOrFail();

                    if ($stock->productStock < $validated['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => __('Insufficient stock. Available: :available', ['available' => $stock->productStock]),
                        ], 422);
                    }

                    $movement = $stockService->allocate(
                        $stock, $validated['quantity'],
                        'App\\Models\\Product', $product->id,
                        $userId, $validated['notes'] ?? null
                    );
                    break;

                case 'adjustment':
                    // Set Exact Quantity: find or create a stock batch
                    $stock = $this->findOrCreateStockBatch(
                        $product, $businessId, $validated, $userId
                    );
                    $movement = $stockService->adjustStock(
                        $stock, $validated['quantity'],
                        'App\\Models\\Product', $product->id,
                        $userId, $validated['notes'] ?? null
                    );
                    break;
            }

            DB::commit();

            // Refresh product to get updated stock
            $product->refresh();
            $newTotal = $product->stocks()->sum('productStock');

            // Broadcast real-time stock update
            if ($movement) {
                \App\Events\StockUpdated::dispatch($movement, $newTotal);
            }

            return response()->json([
                'success' => true,
                'message' => __('Stock adjusted successfully.'),
                'data' => [
                    'new_total_stock' => $newTotal,
                    'movement_id' => $movement?->id,
                    'stock_id' => $stock->id ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => __('Error adjusting stock: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find an existing stock batch or create a new one for stock-in/adjustment.
     */
    protected function findOrCreateStockBatch(
        Product $product,
        int $businessId,
        array $validated,
        int $userId
    ): Stock {
        $batchNo = $validated['batch_no'] ?? null;

        // If a batch_no is provided, try to find it
        if ($batchNo) {
            $stock = Stock::where('business_id', $businessId)
                ->where('product_id', $product->id)
                ->where('batch_no', $batchNo)
                ->first();

            if ($stock) {
                return $stock;
            }
        }

        // Find any existing stock for this product
        $stock = Stock::where('business_id', $businessId)
            ->where('product_id', $product->id)
            ->first();

        if ($stock) {
            return $stock;
        }

        // Create a new stock batch
        return Stock::create([
            'business_id' => $businessId,
            'product_id' => $product->id,
            'productStock' => 0,
            'batch_no' => $batchNo ?? 'BATCH-' . strtoupper(uniqid()),
            'expire_date' => $validated['expire_date'] ?? null,
            'purchase_price' => $validated['purchase_price'] ?? $product->purchase_without_tax ?? 0,
        ]);
    }

    /**
     * Generate and download a barcode label PDF for a product.
     */
    public function printBarcode(Request $request, int $id)
    {
        $businessId = $request->user()->business_id;

        $request->validate([
            'quantity' => 'required|integer|min:1|max:200',
            'size' => 'nullable|in:small,standard,large',
            'show_price' => 'nullable|boolean',
            'show_expiry' => 'nullable|boolean',
            'show_batch' => 'nullable|boolean',
            'show_code' => 'nullable|boolean',
            'show_scientific' => 'nullable|boolean',
            'batch_id' => 'nullable|integer|exists:stocks,id',
        ]);

        $product = Product::where('id', $id)
            ->where('business_id', $businessId)
            ->with('allStocks')
            ->firstOrFail();

        // Determine the barcode number
        $barcodeNumber = $product->barcode;
        if (empty($barcodeNumber)) {
            // Try to generate one
            $barcodeNumber = \App\Models\Barcode::generateBarcodeNumber(
                $request->input('barcode_type', \App\Models\Barcode::TYPE_CODE128)
            );
        }

        // Determine batch info
        $batchNo = null;
        $batchExpireDate = null;

        if ($request->filled('batch_id')) {
            $stock = $product->allStocks->firstWhere('id', $request->batch_id);
            if ($stock) {
                $batchNo = $stock->batch_no;
                $batchExpireDate = $stock->expire_date?->format('Y-m-d');
            }
        } elseif ($product->allStocks->isNotEmpty()) {
            $stock = $product->allStocks->first();
            $batchNo = $stock->batch_no;
            $batchExpireDate = $stock->expire_date?->format('Y-m-d');
        }

        $data = [
            'product' => $product,
            'barcodeNumber' => $barcodeNumber,
            'barcodeType' => $product->barcode_type ?? 'CODE128',
            'quantity' => $request->input('quantity', 1),
            'size' => $request->input('size', 'standard'),
            'showPrice' => $request->boolean('show_price', false),
            'showExpiry' => $request->boolean('show_expiry', true),
            'showBatch' => $request->boolean('show_batch', true),
            'showCode' => $request->boolean('show_code', true),
            'showScientific' => $request->boolean('show_scientific', true),
            'batchNo' => $batchNo,
            'batchExpireDate' => $batchExpireDate,
        ];

        $pdf = \PDF::loadView('barcodes.item-label', $data)
            ->setPaper('a4')
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'helvetica',
            ]);

        $filename = 'barcode_' . preg_replace('/[^a-zA-Z0-9]/', '_', $product->productName) . '_' . now()->format('His') . '.pdf';

        // Record print history
        ItemPrintHistory::create([
            'product_id' => $product->id,
            'business_id' => $businessId,
            'user_id' => auth()->id(),
            'barcode_number' => $barcodeNumber,
            'quantity' => $request->input('quantity', 1),
            'size' => $request->input('size', 'standard'),
            'options' => [
                'show_price' => $request->boolean('show_price'),
                'show_expiry' => $request->boolean('show_expiry'),
                'show_batch' => $request->boolean('show_batch'),
                'show_code' => $request->boolean('show_code'),
                'show_scientific' => $request->boolean('show_scientific'),
            ],
            'batch_no' => $batchNo,
            'pdf_filename' => $filename,
        ]);

        return $pdf->download($filename);
    }

    /**
     * Build a unified timeline from all event sources.
     */
    private function buildTimeline(int $productId, $auditLogs, $stockMovements, $priceHistory, $printHistory): array
    {
        $events = [];

        foreach ($auditLogs as $log) {
            $meta = $log->meta ?? [];
            $events[] = [
                'type' => 'audit',
                'icon' => $log->action === 'created' ? 'fa-plus-circle' : ($log->action === 'deleted' ? 'fa-trash' : 'fa-edit'),
                'color' => match($log->action) {
                    'created' => 'success',
                    'deleted' => 'danger',
                    'archived' => 'secondary',
                    default => 'info',
                },
                'title' => 'Product ' . $log->action,
                'description' => $log->description,
                'user' => $log->user->name ?? 'System',
                'date' => $log->created_at,
                'sort_date' => $log->created_at->timestamp,
            ];
        }

        foreach ($stockMovements as $mov) {
            $events[] = [
                'type' => 'stock',
                'icon' => match($mov->movement_type) {
                    'in' => 'fa-arrow-down',
                    'out' => 'fa-arrow-up',
                    default => 'fa-exchange-alt',
                },
                'color' => match($mov->movement_type) {
                    'in' => 'success',
                    'out' => 'danger',
                    default => 'warning',
                },
                'title' => 'Stock ' . ucfirst($mov->movement_type),
                'description' => ($mov->movement_type === 'in' ? '+' : '-') . $mov->quantity . ' units' . ($mov->batch_no ? ' (batch: ' . $mov->batch_no . ')' : '') . ($mov->notes ? ' — ' . $mov->notes : ''),
                'user' => $mov->user->name ?? 'System',
                'date' => $mov->created_at,
                'sort_date' => $mov->created_at->timestamp,
            ];
        }

        foreach ($priceHistory as $ph) {
            $events[] = [
                'type' => 'price',
                'icon' => 'fa-dollar-sign',
                'color' => 'primary',
                'title' => 'Price updated',
                'description' => 'Selling: ' . number_format($ph->sales_price, 2) . ($ph->change_reason ? ' (' . $ph->change_reason . ')' : ''),
                'user' => $ph->user->name ?? 'System',
                'date' => $ph->created_at,
                'sort_date' => $ph->created_at->timestamp,
            ];
        }

        foreach ($printHistory as $ph) {
            $events[] = [
                'type' => 'print',
                'icon' => 'fa-print',
                'color' => 'secondary',
                'title' => 'Barcode labels printed',
                'description' => $ph->quantity . ' label(s), size: ' . $ph->size . ($ph->batch_no ? ', batch: ' . $ph->batch_no : ''),
                'user' => $ph->user->name ?? 'System',
                'date' => $ph->created_at,
                'sort_date' => $ph->created_at->timestamp,
            ];
        }

        // Sort by date descending
        usort($events, fn($a, $b) => $b['sort_date'] <=> $a['sort_date']);

        return array_slice($events, 0, 50);
    }
}
