<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceController extends Controller
{
    public function __construct(private PurchaseService $purchaseService)
    {
        $this->middleware('permission:purchases-view')->only('index', 'show', 'statistics');
        $this->middleware('permission:purchases-create')->only('create', 'storeAjax', 'searchBarcode', 'searchProducts');
        $this->middleware('permission:purchases-edit')->only('edit', 'updateAjax');
        $this->middleware('permission:purchases-delete')->only('cancel');
    }

    /**
     * Purchase invoices list page.
     */
    public function index(Request $request)
    {
        $businessId = Auth::user()->business_id;

        $filters = $request->only(['search', 'status', 'party_id', 'branch_id', 'from_date', 'to_date']);
        $purchases = $this->purchaseService->list($filters, $businessId, 15);

        $suppliers = Party::where('business_id', $businessId)
            ->where('type', 'supplier')
            ->select('id', 'name', 'phone')
            ->get();

        $branches = Branch::where('company_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'branch_name')
            ->get();

        return view('admin.purchases.index', compact('purchases', 'suppliers', 'branches', 'filters'));
    }

    /**
     * Purchase invoice create page.
     */
    public function create()
    {
        $businessId = Auth::user()->business_id;

        $suppliers = Party::where('business_id', $businessId)
            ->where('type', 'supplier')
            ->select('id', 'name', 'phone')
            ->get();

        $branches = Branch::where('company_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'branch_name')
            ->get();

        $products = Product::where('business_id', $businessId)
            ->select('id', 'productName', 'productCode', 'purchase_without_tax', 'purchase_with_tax', 'sales_price', 'profit_percent', 'wholesale_price', 'alert_qty')
            ->get();

        return view('admin.purchases.create', compact('suppliers', 'branches', 'products'));
    }

    /**
     * Store a purchase invoice via AJAX.
     */
    public function storeAjax(Request $request): JsonResponse
    {
        $businessId = Auth::user()->business_id;

        $request->validate([
            'party_id' => 'required|exists:parties,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'nullable|integer|exists:products,id',
            'products.*.new_product_name' => 'nullable|string|max:255',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.purchase_with_tax' => 'required|numeric|min:0',
            'products.*.barcode' => 'nullable|string|max:50',
            'products.*.category_id' => 'nullable|integer|exists:categories,id',
            'purchaseDate' => 'required|date',
            'discountAmount' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'paidAmount' => 'nullable|numeric|min:0',
            'paymentType' => 'nullable|string',
            'note' => 'nullable|string|max:500',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        // Ensure supplier belongs to current business
        $party = Party::where('id', $request->party_id)
            ->where('business_id', $businessId)
            ->first();
        if (!$party) {
            return response()->json([
                'success' => false,
                'message' => __('Supplier not found.'),
            ], 422);
        }

        // Auto-create products for line items without product_id
        $products = $request->input('products', []);
        foreach ($products as &$productData) {
            if (empty($productData['product_id']) && !empty($productData['new_product_name'])) {
                // Check for duplicate by barcode first
                if (!empty($productData['barcode'])) {
                    $existing = \App\Models\Product::where('business_id', $businessId)
                        ->where('barcode', $productData['barcode'])
                        ->first();
                    if ($existing) {
                        $productData['product_id'] = $existing->id;
                        continue;
                    }
                }

                // Auto-create the product
                $defaultUnit = \App\Models\Unit::where('business_id', $businessId)->first();
                $newProduct = \App\Models\Product::create([
                    'business_id' => $businessId,
                    'productName' => $productData['new_product_name'],
                    'category_id' => $productData['category_id'] ?? null,
                    'unit_id' => $defaultUnit?->id,
                    'barcode' => $productData['barcode'] ?? null,
                    'productCode' => $productData['barcode'] ?? 'AUTO-' . strtoupper(uniqid()),
                    'purchase_without_tax' => $productData['purchase_with_tax'] ?? 0,
                    'purchase_with_tax' => $productData['purchase_with_tax'] ?? 0,
                    'sales_price' => $productData['sales_price'] ?? ($productData['purchase_with_tax'] ?? 0) * 1.3,
                    'wholesale_price' => $productData['wholesale_price'] ?? $productData['sales_price'] ?? 0,
                    'profit_percent' => $productData['profit_percent'] ?? 30,
                    'active' => true,
                    'track_inventory' => true,
                ]);

                $productData['product_id'] = $newProduct->id;
            }
        }
        unset($productData);

        // Ensure every line item has a product_id
        foreach ($products as $index => $productData) {
            if (empty($productData['product_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => __('Line item :num requires either a product or a product name.', ['num' => $index + 1]),
                ], 422);
            }
        }

        $request->merge(['products' => $products]);

        try {
            $purchase = $this->purchaseService->create(
                $request->all(),
                $businessId,
                Auth::id()
            );

            // Prepare items for barcode print dialog
            $itemsForBarcodes = $purchase->details->map(function ($detail) {
                return [
                    'product_name' => $detail->product->productName ?? 'Product #' . $detail->product_id,
                    'product_id' => $detail->product_id,
                    'barcode' => $detail->product->productCode ?? null,
                    'batch_no' => $detail->batch_no,
                    'qty' => $detail->quantities,
                ];
            })->toArray();

            return response()->json([
                'success' => true,
                'message' => __('Purchase invoice created successfully.'),
                'data' => [
                    'id' => $purchase->id,
                    'invoiceNumber' => $purchase->invoiceNumber,
                    'totalAmount' => $purchase->totalAmount,
                    'dueAmount' => $purchase->dueAmount,
                    'invoice_number' => $purchase->invoiceNumber,
                    'items' => $itemsForBarcodes,
                ],
                'redirect' => route('admin.purchases.show', $purchase->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show purchase invoice details.
     */
    public function show(Purchase $purchase)
    {
        // Ensure purchase belongs to current business
        if ($purchase->business_id !== Auth::user()->business_id) {
            abort(404);
        }

        $data = $this->purchaseService->show($purchase->id);

        return view('admin.purchases.show', compact('data'));
    }

    /**
     * Purchase invoice edit page.
     */
    public function edit(Purchase $purchase)
    {
        // Ensure purchase belongs to current business
        if ($purchase->business_id !== Auth::user()->business_id) {
            abort(404);
        }

        $businessId = Auth::user()->business_id;

        $suppliers = Party::where('business_id', $businessId)
            ->where('type', 'supplier')
            ->select('id', 'name', 'phone')
            ->get();

        $branches = Branch::where('company_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'branch_name')
            ->get();

        $products = Product::where('business_id', $businessId)
            ->select('id', 'productName', 'productCode', 'purchase_without_tax', 'purchase_with_tax', 'sales_price', 'profit_percent', 'wholesale_price')
            ->get();

        $purchase->load('details.product:id,productName,productCode');

        return view('admin.purchases.edit', compact('purchase', 'suppliers', 'branches', 'products'));
    }

    /**
     * Update purchase invoice via AJAX.
     */
    public function updateAjax(Request $request, Purchase $purchase): JsonResponse
    {
        // Ensure purchase belongs to current business
        if ($purchase->business_id !== Auth::user()->business_id) {
            return response()->json(['success' => false, 'message' => __('Not found.')], 404);
        }

        $request->validate([
            'party_id' => 'required|exists:parties,id',
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantities' => 'required|integer|min:1',
            'products.*.purchase_with_tax' => 'required|numeric|min:0',
            'purchaseDate' => 'required|date',
        ]);

        try {
            $this->purchaseService->update(
                $purchase,
                $request->all(),
                Auth::user()->business_id,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => __('Purchase invoice updated successfully.'),
                'redirect' => route('admin.purchases.show', $purchase->id),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a purchase invoice.
     */
    public function cancel(Request $request, Purchase $purchase): JsonResponse
    {
        // Ensure purchase belongs to current business
        if ($purchase->business_id !== Auth::user()->business_id) {
            return response()->json(['success' => false, 'message' => __('Not found.')], 404);
        }

        try {
            $this->purchaseService->delete($purchase, Auth::user()->business_id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => __('Purchase invoice cancelled successfully.'),
                'redirect' => route('admin.purchases.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Search for products by barcode (AJAX).
     */
    public function searchBarcode(Request $request): JsonResponse
    {
        $request->validate(['barcode' => 'required|string']);

        $result = $this->purchaseService->searchByBarcode(
            $request->barcode,
            Auth::user()->business_id
        );

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => __('No product found with this barcode.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Search products by name/code (AJAX).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate(['query' => 'required|string|min:1']);

        $products = $this->purchaseService->searchProducts(
            $request->query,
            Auth::user()->business_id,
            10
        );

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get purchase statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->purchaseService->getStatistics(Auth::user()->business_id);

        return response()->json(['data' => $stats]);
    }
}
