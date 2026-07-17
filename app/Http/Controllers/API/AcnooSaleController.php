<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Party;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Business;
use App\Models\SaleDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\StockMovementService;
use Illuminate\Support\Facades\Validator;

class AcnooSaleController extends Controller
{
    protected $stockMovementService;

    public function __construct(StockMovementService $stockMovementService)
    {
        $this->stockMovementService = $stockMovementService;
    }

    /**
     * Validate inventory for POS checkout.
     * Accepts items with barcode and quantity.
     */
    public function validateInventory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $results = [];
        $allAvailable = true;

        foreach ($request->items as $index => $item) {
            $product = Product::query()
                ->where('company_id', $companyId)
                ->where('barcode', trim((string) $item['barcode']))
                ->first();

            if (!$product) {
                $results[] = [
                    'barcode' => $item['barcode'],
                    'available' => false,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => 0,
                    'error' => 'Product not found',
                ];
                $allAvailable = false;
                continue;
            }

            $stock = Stock::query()
                ->where('product_id', $product->id)
                ->whereHas('product', function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                })
                ->sum('productStock');

            if ($stock < $item['quantity']) {
                $results[] = [
                    'barcode' => $item['barcode'],
                    'product_id' => $product->id,
                    'name' => $product->productName,
                    'available' => false,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $stock,
                ];
                $allAvailable = false;
            } else {
                $results[] = [
                    'barcode' => $item['barcode'],
                    'product_id' => $product->id,
                    'name' => $product->productName,
                    'available' => true,
                    'requested_quantity' => $item['quantity'],
                    'available_quantity' => $stock,
                    'price' => $product->sales_price ?? 0,
                ];
            }
        }

        return response()->json([
            'all_available' => $allAvailable,
            'items' => $results,
        ]);
    }

    /**
     * Display a listing of sales for POS (returns array directly for frontend compatibility).
     */
    public function index()
    {
        $companyId = auth()->user()->company_id ?? app('tenant.company_id') ?? null;

        $sales = Sale::select('id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'status', 'meta')
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->latest()
            ->limit(20)
            ->get();

        // Format for POS frontend
        $formattedSales = $sales->map(function ($sale) {
            $items = [];
            // Try to get items from meta first (POS sales store items_json there)
            if (isset($sale->meta['items_json'])) {
                $items = $sale->meta['items_json'];
            } elseif (isset($sale->meta['items'])) {
                $items = is_array($sale->meta['items']) ? $sale->meta['items'] : json_decode($sale->meta['items'], true);
            }

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoiceNumber,
                'customer_name' => $sale->meta['customer_name'] ?? 'Guest',
                'payment_method' => $sale->paymentType,
                'total_amount' => $sale->totalAmount,
                'created_at' => $sale->created_at->toDateTimeString(),
                'items' => $items,
            ];
        });

        return response()->json($formattedSales->values()->toArray());
    }

    /**
     * Store a POS sale using barcode-based items.
     */
    protected function storePosSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.barcode' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric',
            'customer_name' => 'nullable|string',
            'payment_method' => 'nullable|string|in:cash,card,insurance',
            'status' => 'nullable|string|in:completed,held,pending',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $companyId = $request->user()->company_id ?? app('tenant.company_id');
        $businessId = $request->user()->business_id ?? app('tenant.business_id') ?? auth()->user()->business_id;

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $items = [];
            $stockMovements = [];

            foreach ($request->items as $itemData) {
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where('barcode', trim((string) $itemData['barcode']))
                    ->first();

                if (!$product) {
                    return response()->json([
                        'message' => "Product not found for barcode: {$itemData['barcode']}",
                    ], 404);
                }

                $quantity = (int) $itemData['quantity'];
                $price = (float) ($itemData['price'] ?? ($product->sales_price ?? 0));

                $stock = Stock::query()
                    ->where('product_id', $product->id)
                    ->whereHas('product', function ($query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    })
                    ->first();

                if (!$stock) {
                    return response()->json([
                        'message' => "No stock record found for barcode: {$itemData['barcode']}",
                    ], 404);
                }

                if ($stock->productStock < $quantity) {
                    return response()->json([
                        'message' => "Insufficient stock for {$product->productName}. Available: {$stock->productStock}",
                    ], 422);
                }

                // Deduct stock
                $stock->update(['productStock' => max(0, (int) $stock->productStock - $quantity)]);

                // Log stock movement
                $this->stockMovementService->logMovement(
                    $product->id,
                    $request->input('branch_id', 1),
                    'out',
                    $quantity,
                    'pos_sale',
                    null,
                    null,
                    [
                        'barcode' => $product->barcode,
                        'product_name' => $product->productName,
                    ]
                );

                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;

                $items[] = [
                    'product_id' => $product->id,
                    'barcode' => $product->barcode,
                    'name' => $product->productName,
                    'price' => $price,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                ];
            }

            $taxAmount = round($subtotal * 0.08, 2);
            $total = $subtotal + $taxAmount;

            // Create sale record
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'business_id' => $businessId,
                'company_id' => $companyId,
                'invoiceNumber' => 'POS-' . strtoupper(uniqid()),
                'saleDate' => now()->toDateString(),
                'totalAmount' => $total,
                'paidAmount' => $total,
                'paymentType' => $request->input('payment_method', 'cash'),
                'status' => $request->input('status', 'completed'),
                'meta' => [
                    'customer_name' => $request->input('customer_name', 'Guest'),
                    'payment_method' => $request->input('payment_method', 'cash'),
                    'source' => 'pos',
                    'items_json' => $items,
                ],
            ]);

            // Create sale details
            $saleDetails = [];
            foreach ($items as $key => $item) {
                $saleDetails[] = [
                    'sale_id' => $sale->id,
                    'price' => $item['unit_price'],
                    'quantities' => $item['quantity'],
                    'product_id' => $item['product_id'],
                    'expire_date' => null,
                    'purchase_price' => $item['unit_price'],
                ];
            }

            SaleDetails::insert($saleDetails);

            DB::commit();

            // Return with sale_items relationship
            return response()->json([
                'id' => $sale->id,
                'invoice_number' => $sale->invoiceNumber,
                'customer_name' => $sale->meta['customer_name'] ?? 'Guest',
                'payment_method' => $sale->paymentType,
                'status' => $sale->status,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $total,
                'created_at' => $sale->created_at->toDateTimeString(),
                'sale_items' => $items,
                'message' => 'Sale completed successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Sale failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     * Supports both legacy format (products with product_id) and POS format (items with barcode).
     */
    public function store(Request $request)
    {
        // Detect if this is a POS request (has items array with barcode)
        $isPosRequest = $request->has('items') && !$request->has('products');

        if ($isPosRequest) {
            return $this->storePosSale($request);
        }

        // Legacy format validation
        $request->validate([
            'products' => 'required|array',
            'saleDate' => 'required|string',
            'party_id' => 'nullable|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'paidAmount' => 'nullable|numeric',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric',
            'products.*.lossProfit' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {

            $business_id = $request->user()->business_id ?? app('tenant.business_id') ?? auth()->user()->business_id;

            $batch_numbers = collect($request->products)->pluck('batch_no')->toArray();
            $stocks = Stock::whereIn('batch_no', $batch_numbers)->where('business_id', $business_id)->get();
            foreach ($stocks as $key => $stock) {
                if ($stock->productStock < $request->products[$key]['quantities']) {
                    return response()->json([
                        'message' => __($stock->batch_no . ' - stock not available for this batch number. Available quantity is : '. $stock->productStock)
                    ], 400);
                }
            }

            if ($request->party_id) {
                $party = Party::findOrFail($request->party_id);
            }

            if ($request->dueAmount) {
                if (!$request->party_id) {
                    return response()->json([
                        'message' => __('You can not sale in due for a walking customer.')
                    ], 400);
                }

                $party->update([
                    'due' => $party->due + $request->dueAmount
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business_name = $business->companyName;
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $request->paidAmount
            ]);

            $lossProfit = collect($request->products)->pluck('lossProfit')->toArray();

            $sale = Sale::create($request->all() + [
                        'user_id' => auth()->id(),
                        'business_id' => $business_id,
                        'lossProfit' => array_sum($lossProfit) - $request->discountAmount,
                        'meta' => [
                            'notes' => $request->notes,
                            'customer_phone' => $request->customer_phone,
                        ],
                    ]);

            $saleDetails = [];
            foreach ($request->products as $key => $productData) {
                $saleDetails[$key] = [
                    'sale_id' => $sale->id,
                    'price' => $productData['price'],
                    'batch_no' => $productData['batch_no'],
                    'product_id' => $productData['product_id'],
                    'lossProfit' => $productData['lossProfit'],
                    'quantities' => $productData['quantities'] ?? 0,
                    'expire_date' => $productData['expire_date'] ?? NULL,
                    'purchase_price' => $productData['purchase_price'] ?? 0,
                ];

                Stock::where('batch_no', $productData['batch_no'])->decrement('productStock', $productData['quantities']);
            }

            SaleDetails::insert($saleDetails);

            if ($party ?? false && $party->phone) {
                if (env('MESSAGE_ENABLED')) {
                    sendMessage($party->phone, saleMessage($sale, $party, $business_name));
                }
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $sale->load([
                                'tax:id,name,rate',
                                'party:id,name,phone',
                                'details.product:id,productName',
                                'details:id,sale_id,product_id,price,quantities',
                            ]),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => __('Something was wrong.'),
            ], 406);
        }
    }

    public function show($id)
    {
        $data = Sale::with([
                    'tax',
                    'party',
                    'user:id,name',
                    'saleReturns.details',
                    'details:id,sale_id,product_id,price,quantities,purchase_price,batch_no,expire_date',
                    'details.product' => function ($query) {
                        $query->select('id', 'productName')
                            ->withSum('stocks', 'productStock');
                    },
                ])
                ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'products' => 'required|array',
            'saleDate' => 'required|string',
            'party_id' => 'nullable|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'paidAmount' => 'nullable|numeric',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string',
            'products.*.price' => 'required|numeric',
            'products.*.lossProfit' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
            'products.*.product_id' => 'required|exists:products,id',
        ]);

        $business_id = auth()->user()->business_id;

        DB::beginTransaction();
        try {

            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();
            $productIds = collect($request->products)->pluck('product_id')->toArray();
            $products = Product::whereIn('id', $productIds)->get();

            foreach ($products as $key => $product) {
                $prevProduct = $prevDetails->first(function ($item) use ($product) {
                    return $item->product_id == $product->id;
                });

                $productStock = 0;

                // Calculate stock by batch_no or fallback to first stock entry
                $stock = Stock::where('product_id', $product->id)
                            ->where('batch_no', $request->products[$key]['batch_no'] ?? null)
                            ->first();

                if (!$stock) {
                    $stock = Stock::where('product_id', $product->id)->orderBy('id', 'asc')->first();
                }

                if ($prevProduct) {
                    $productStock = $stock ? ($stock->productStock + $prevProduct->quantities) : 0;
                } else {
                    $productStock = $stock ? $stock->productStock : 0;
                }

                if ($productStock < $request->products[$key]['quantities']) {
                    return response()->json([
                        'message' => __($product->productName . ' - stock not available for this product. Available quantity is: ' . $productStock)
                    ], 400);
                }
            }

            // Restore stock for previously saved sale details
            foreach ($prevDetails as $prevItem) {
                $stock = Stock::where('product_id', $prevItem->product_id)
                            ->where('batch_no', $prevItem->batch_no)
                            ->first();

                if ($stock) {
                    $stock->increment('productStock', $prevItem->quantities);
                }
            }

            $prevDetails->each->delete();

            // Save new sale details and adjust stock
            $saleDetails = [];
            foreach ($request->products as $key => $productData) {
                $saleDetails[$key] = [
                    'sale_id' => $sale->id,
                    'price' => $productData['price'],
                    'batch_no' => $productData['batch_no'],
                    'product_id' => $productData['product_id'],
                    'lossProfit' => $productData['lossProfit'],
                    'quantities' => $productData['quantities'] ?? 0,
                    'expire_date' => $productData['expire_date'] ?? NULL,
                    'purchase_price' => $productData['purchase_price'] ?? 0,
                ];

                $stock = Stock::where('product_id', $productData['product_id'])
                            ->where('batch_no', $productData['batch_no'])
                            ->first();

                if (!$stock) {
                    $stock = Stock::where('product_id', $productData['product_id'])->orderBy('id', 'asc')->first();
                }

                if ($stock) {
                    $stock->decrement('productStock', $productData['quantities']);
                }
            }

            SaleDetails::insert($saleDetails);

            // Update financial and business logic
            if ($sale->dueAmount || $request->dueAmount) {
                $party = Party::findOrFail($request->party_id);
                $party->update([
                    'due' => $request->party_id == $sale->party_id ? (($party->due - $sale->dueAmount) + $request->dueAmount) : ($party->due + $request->dueAmount)
                ]);

                if ($request->party_id != $sale->party_id) {
                    $prevParty = Party::findOrFail($sale->party_id);
                    $prevParty->update([
                        'due' => $prevParty->due - $sale->dueAmount
                    ]);
                }
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'shopOpeningBalance' => ($business->shopOpeningBalance - $sale->paidAmount) + $request->paidAmount
            ]);

            $lossProfit = collect($request->products)->pluck('lossProfit')->toArray();

            $sale->update($request->all() + [
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'lossProfit' => array_sum($lossProfit) - $request->discountAmount,
                'meta' => [
                    'notes' => $request->notes,
                    'customer_phone' => $request->customer_phone,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => __('Something was wrong.'),
            ], 406);
        }
    }

    public function destroy(Sale $sale)
    {
        foreach ($sale->details as $detail) {
            $stock = Stock::where('product_id', $detail->product_id)
                          ->where('batch_no', $detail->batch_no)
                          ->first();

            if (!$stock) {
                $stock = Stock::where('product_id', $detail->product_id)->orderBy('id', 'asc')->first();
            }

            if ($stock) {
                $stock->increment('productStock', $detail->quantities);
            }
        }

        if ($sale->dueAmount) {
            $party = Party::findOrFail($sale->party_id);
            $party->update([
                'due' => $party->due - $sale->dueAmount
            ]);
        }

        $business = Business::findOrFail(auth()->user()->business_id);
        $business->update([
            'shopOpeningBalance' => $business->shopOpeningBalance - $sale->paidAmount
        ]);

        $sale->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
