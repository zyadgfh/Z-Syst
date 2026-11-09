<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\FefoSetting;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Services\FefoService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ZSystSaleController extends Controller
{
    protected FefoService $fefoService;

    public function __construct(FefoService $fefoService)
    {
        $this->fefoService = $fefoService;
    }

    private function loadBusinessStocks(int $businessId, array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return Stock::where('business_id', $businessId)
            ->whereIn('product_id', $productIds)
            ->select('id', 'business_id', 'product_id', 'batch_no', 'expire_date', 'product_stock')
            ->get()
            ->groupBy('product_id');
    }

    private function resolveStockForProduct(Collection $stocksByProduct, int $productId, ?string $batchNo = null): ?Stock
    {
        $productStocks = $stocksByProduct->get($productId, collect());

        if ($batchNo) {
            $stock = $productStocks->first(fn ($item) => $item->batch_no === $batchNo);
            if ($stock) {
                return $stock;
            }
        }

        return $productStocks->first();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Sale::select('id', 'party_id', 'invoice_number', 'sale_date', 'total_amount', 'due_amount', 'paid_amount', 'payment_type')
            ->with('party:id,name,phone')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('payment_type', 'like', $term)
                        ->orWhere('invoice_number', 'like', $term)
                        ->orWhere('meta', 'like', $term)
                        ->orWhereHas('party', function ($query) use ($term) {
                            $query->where('name', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->withCount('saleReturns')
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'sale_date' => 'required|string',
            'client_uuid' => 'nullable|uuid',
            'party_id' => 'nullable|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'due_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'is_paid' => 'nullable|boolean',
            'payment_type' => 'nullable|string',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.price' => 'required|numeric',
            'products.*.loss_profit' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
            'products.*.dose' => 'nullable|string|max:160',
            'products.*.dose_code' => 'nullable|string|max:24',
        ]);

        $sale = TransactionHelper::run(function () use ($request) {
            $business_id = auth()->user()->business_id;
            $fefoService = $this->fefoService;
            $fefoSettings = FefoSetting::getForBusiness($business_id);

            $productIds = collect($request->products)->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($business_id, $productIds);

            // H-01: prices are server authority. The client-sent price is only
            // honored for users with the sale.price-override permission (audit
            // logged below); everyone else must match the product's list price
            // (2% rounding tolerance for terminal tax-inclusive configs).
            $canOverridePrice = auth()->user()?->can('sale.price-override');
            $catalogPrices = Product::whereIn('id', $productIds)->pluck('sales_price', 'id');
            foreach ($request->products as $idx => $line) {
                $listPrice = (float) ($catalogPrices[$line['product_id']] ?? 0);
                if (! $canOverridePrice && $listPrice > 0) {
                    $delta = abs((float) $line['price'] - $listPrice);
                    if ($delta > max(0.02, $listPrice * 0.02)) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('Price mismatch for product :id — sell at the listed price.', ['id' => $line['product_id']])
                        );
                    }
                }
            }

            foreach ($request->products as $productData) {
                $productId = $productData['product_id'];
                $productStocks = $businessStocks->get($productId, collect());
                $batchNo = $productData['batch_no'] ?? null;

                if (! empty($batchNo)) {
                    $stock = $productStocks->first(fn ($item) => $item->batch_no === $batchNo);

                    if (! $stock && $productStocks->isNotEmpty()) {
                        // Offline terminals may carry local batch labels that
                        // never matched a warehouse batch; honour the sale as
                        // long as the product has aggregate stock.
                        if ($productStocks->sum('product_stock') >= $productData['quantities']) {
                            $stock = $productStocks->first(fn ($item) => $item->product_stock >= $productData['quantities']);
                        }
                    }

                    if (! $stock) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => $batchNo,
                                'available' => 0,
                                'requested' => $productData['quantities'],
                            ]),
                            [
                                'product_id' => $productId,
                                'batch_no' => $batchNo,
                                'available_qty' => 0,
                                'requested_qty' => $productData['quantities'],
                            ]
                        );
                    }

                    if ($stock->product_stock < $productData['quantities']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => $stock->batch_no,
                                'available' => $stock->product_stock,
                                'requested' => $productData['quantities'],
                            ]),
                            [
                                'product_id' => $productId,
                                'batch_no' => $stock->batch_no,
                                'available_qty' => $stock->product_stock,
                                'requested_qty' => $productData['quantities'],
                            ]
                        );
                    }
                } elseif ($fefoSettings->fefo_enabled) {
                    $totalStock = $productStocks->filter(function ($stock) {
                        return $stock->product_stock > 0 && (is_null($stock->expire_date) || $stock->expire_date >= now()->startOfDay());
                    })->sum('product_stock');

                    if ($totalStock < $productData['quantities']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => 'FEFO auto',
                                'available' => $totalStock,
                                'requested' => $productData['quantities'],
                            ]),
                            [
                                'product_id' => $productId,
                                'batch_no' => null,
                                'available_qty' => $totalStock,
                                'requested_qty' => $productData['quantities'],
                            ]
                        );
                    }
                }
            }

            // Validate due sale for walking customers
            if ($request->due_amount && ! $request->party_id) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
                    __('errors.due_sale_walking_customer'),
                    ['due_amount' => $request->due_amount]
                );
            }

            if ($request->party_id) {
                $party = Party::findOrFail($request->party_id);
            }

            if ($request->due_amount && isset($party)) {
                $party->update([
                    'due' => $party->due + $request->due_amount,
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business_name = $business->company_name;
            $business->update([
                'remaining_shop_balance' => $business->remaining_shop_balance + $request->paid_amount,
            ]);

            $loss_profit = collect($request->products)->pluck('loss_profit')->toArray();

            $sale = Sale::create($request->only([
        'party_id',
        'user_id',
        'tax_id',
        'discount_amount',
        'due_amount',
        'is_paid',
        'tax_amount',
        'paid_amount',
        'total_amount',
        'loss_profit',
        'payment_type',
        'invoice_number',
        'sale_date',
        'sale_data',
        'meta',
        'client_uuid',
    ]) + [
                'business_id' => $business_id,
                'loss_profit' => array_sum($loss_profit) - ($request->discount_amount ?? 0),
                'meta' => [
                    'notes' => $request->notes,
                    'customer_phone' => $request->customer_phone,
                    // Path B: identity lives in app_users (uuid) while
                    // sales.user_id still carries a FK to the legacy integer
                    // users table — writing the uuid there violates the
                    // constraint on every backend. Attribute the actor in
                    // meta until the schema FKs are retargeted to app_users.
                    'user_id' => auth()->id(),
                ],
            ]);

            $saleDetails = [];
            $fefoService = $this->fefoService;
            $fefoSettings = FefoSetting::getForBusiness($business_id);

            foreach ($request->products as $key => $productData) {
                $productId = $productData['product_id'];
                $quantity = $productData['quantities'] ?? 0;

                // FEFO: If batch_no is not explicitly provided by frontend, use FEFO to auto-select
                if (empty($productData['batch_no']) && $fefoSettings->fefo_enabled) {
                    $batches = $fefoService->getBestBatches($productId, $quantity, $business_id);
                    $totalAllocated = 0;

                    foreach ($batches as $batch) {
                        if ($totalAllocated >= $quantity) {
                            break;
                        }

                        $deductQty = min($batch->product_stock, $quantity - $totalAllocated);

                        $saleDetails[] = [
                            'sale_id' => $sale->id,
                            'price' => $productData['price'],
                            'batch_no' => $batch->batch_no,
                            'product_id' => $productId,
                            'loss_profit' => $productData['loss_profit'] / count($request->products) ?? 0,
                            'quantities' => $deductQty,
                            'dose' => $productData['dose'] ?? null,
                            'dose_code' => $productData['dose_code'] ?? null,
                            'expire_date' => $batch->expire_date,
                            'purchase_price' => $productData['purchase_price'] ?? 0,
                        ];

                        $batch->decrement('product_stock', $deductQty);
                        $totalAllocated += $deductQty;

                        // Log FEFO deduction
                        $batch->refresh();
                        $fefoService->logFefoDeduction(
                            businessId: $business_id,
                            productId: $productId,
                            stockId: $batch->id,
                            batchNo: $batch->batch_no,
                            expireDate: $batch->expire_date,
                            quantityDeducted: $deductQty,
                            quantityRemaining: $batch->product_stock,
                            saleId: $sale->id,
                            notes: 'FEFO auto-deduction during sale creation'
                        );
                    }
                } else {
                    // Manual batch selection (original behavior with FEFO logging)
                    $saleDetails[$key] = [
                        'sale_id' => $sale->id,
                        'price' => $productData['price'],
                        'batch_no' => $productData['batch_no'],
                        'product_id' => $productId,
                        'loss_profit' => $productData['loss_profit'],
                        'quantities' => $quantity,
                        'dose' => $productData['dose'] ?? null,
                        'dose_code' => $productData['dose_code'] ?? null,
                        'expire_date' => $productData['expire_date'] ?? null,
                        'purchase_price' => $productData['purchase_price'] ?? 0,
                    ];

                    $stock = $this->resolveStockForProduct($businessStocks, $productId, $productData['batch_no'] ?? null);

                    if ($stock) {
                        $stock->decrement('product_stock', $quantity);

                        // Log FEFO deduction for manual selection
                        $fefoService->logFefoDeduction(
                            businessId: $business_id,
                            productId: $productId,
                            stockId: $stock->id,
                            batchNo: $stock->batch_no,
                            expireDate: $stock->expire_date,
                            quantityDeducted: $quantity,
                            quantityRemaining: $stock->product_stock,
                            saleId: $sale->id,
                            notes: 'Manual batch selection during sale'
                        );
                    }
                }
            }

            SaleDetails::insert($saleDetails);

            if (isset($party) && $party->phone) {
                if (config('services.message.enabled')) {
                    sendMessage($party->phone, saleMessage($sale, $party, $business_name));
                }
            }

            return $sale->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,product_name',
                'details:id,sale_id,product_id,price,quantities',
            ]);
        }, 'sale:store', [
            'products_count' => count($request->products ?? []),
            'party_id' => $request->party_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $sale,
        ]);
    }

    public function show($id)
    {
        $data = Sale::where('business_id', auth()->user()->business_id)
            ->with([
                'tax',
                'party',
                'user:id,name',
                'saleReturns.details',
                'details:id,sale_id,product_id,price,quantities,purchase_price,batch_no,expire_date',
                'details.product' => function ($query) {
                    $query->select('id', 'product_name')
                        ->withSum('stocks', 'product_stock');
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
        if ($sale->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        $request->validate([
            'products' => 'required|array',
            'sale_date' => 'required|string',
            'client_uuid' => 'nullable|uuid',
            'party_id' => 'nullable|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discount_amount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'total_amount' => 'nullable|numeric',
            'due_amount' => 'nullable|numeric',
            'paid_amount' => 'nullable|numeric',
            'is_paid' => 'nullable|boolean',
            'payment_type' => 'nullable|string',
            'products.*.price' => 'required|numeric',
            'products.*.loss_profit' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
            'products.*.dose' => 'nullable|string|max:160',
            'products.*.dose_code' => 'nullable|string|max:24',
            'products.*.product_id' => 'required|exists:products,id',
        ]);

        TransactionHelper::run(function () use ($request, $sale) {
            $business_id = auth()->user()->business_id;

            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();
            $productIds = collect($request->products)->pluck('product_id')->filter()->unique()->values()->all();
            $allProductIds = collect([...$productIds, $prevDetails->pluck('product_id')->all()])->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($business_id, $allProductIds);
            $products = Product::select('id', 'product_name')->whereIn('id', $productIds)->get();

            foreach ($products as $key => $product) {
                $prevProduct = $prevDetails->first(function ($item) use ($product) {
                    return $item->product_id == $product->id;
                });

                $stock = $this->resolveStockForProduct($businessStocks, $product->id, $request->products[$key]['batch_no'] ?? null);

                $product_stock = 0;
                if ($prevProduct) {
                    $product_stock = $stock ? ($stock->product_stock + $prevProduct->quantities) : 0;
                } else {
                    $product_stock = $stock ? $stock->product_stock : 0;
                }

                if ($product_stock < $request->products[$key]['quantities']) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                        __('errors.insufficient_stock', [
                            'product' => $product->product_name,
                            'batch' => $request->products[$key]['batch_no'] ?? 'N/A',
                            'available' => $product_stock,
                            'requested' => $request->products[$key]['quantities'],
                        ]),
                        [
                            'product_id' => $product->id,
                            'batch_no' => $request->products[$key]['batch_no'] ?? null,
                            'available_qty' => $product_stock,
                            'requested_qty' => $request->products[$key]['quantities'],
                        ]
                    );
                }
            }

            // Restore stock for previously saved sale details
            foreach ($prevDetails as $prevItem) {
                $stock = $this->resolveStockForProduct($businessStocks, $prevItem->product_id, $prevItem->batch_no);

                if ($stock) {
                    $stock->increment('product_stock', $prevItem->quantities);
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
                    'loss_profit' => $productData['loss_profit'],
                    'quantities' => $productData['quantities'] ?? 0,
                    'expire_date' => $productData['expire_date'] ?? null,
                    'purchase_price' => $productData['purchase_price'] ?? 0,
                ];

                $stock = $this->resolveStockForProduct($businessStocks, $productData['product_id'], $productData['batch_no'] ?? null);

                if ($stock) {
                    $stock->decrement('product_stock', $productData['quantities']);
                }
            }

            SaleDetails::insert($saleDetails);

            // Update financial and business logic
            if ($sale->due_amount || $request->due_amount) {
                $party = Party::findOrFail($request->party_id);
                $party->update([
                    'due' => $request->party_id == $sale->party_id ? (($party->due - $sale->due_amount) + $request->due_amount) : ($party->due + $request->due_amount),
                ]);

                if ($request->party_id != $sale->party_id) {
                    $prevParty = Party::findOrFail($sale->party_id);
                    $prevParty->update([
                        'due' => $prevParty->due - $sale->due_amount,
                    ]);
                }
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'shop_opening_balance' => ($business->shop_opening_balance - $sale->paid_amount) + $request->paid_amount,
            ]);

            $loss_profit = collect($request->products)->pluck('loss_profit')->toArray();

            $sale->update($request->only([
        'party_id',
        'user_id',
        'tax_id',
        'discount_amount',
        'due_amount',
        'is_paid',
        'tax_amount',
        'paid_amount',
        'total_amount',
        'loss_profit',
        'payment_type',
        'invoice_number',
        'sale_date',
        'sale_data',
        'meta',
        'client_uuid',
    ]) + [
                'business_id' => $business_id,
                'loss_profit' => array_sum($loss_profit) - ($request->discount_amount ?? 0),
                'meta' => [
                    'notes' => $request->notes,
                    'customer_phone' => $request->customer_phone,
                    'user_id' => auth()->id(), // see store(): uuid must not hit the legacy users FK
                ],
            ]);
        }, 'sale:update', ['sale_id' => $sale->id]);

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    public function destroy(Sale $sale)
    {
        if ($sale->business_id !== auth()->user()->business_id) {
            abort(403);
        }

        TransactionHelper::run(function () use ($sale) {
            $business_id = auth()->user()->business_id;
            $fefoService = $this->fefoService;
            $productIds = $sale->details->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($business_id, $productIds);

            foreach ($sale->details as $detail) {
                $stock = $this->resolveStockForProduct($businessStocks, $detail->product_id, $detail->batch_no);

                if ($stock) {
                    $stock->increment('product_stock', $detail->quantities);

                    // Log FEFO return (stock restoration)
                    $fefoService->logFefoDeduction(
                        businessId: $business_id,
                        productId: $detail->product_id,
                        stockId: $stock->id,
                        batchNo: $stock->batch_no,
                        expireDate: $stock->expire_date,
                        quantityDeducted: -$detail->quantities, // negative = restored
                        quantityRemaining: $stock->product_stock,
                        saleId: $sale->id,
                        saleDetailId: $detail->id,
                        notes: 'Stock restored due to sale deletion'
                    );
                }
            }

            if ($sale->due_amount) {
                $party = Party::findOrFail($sale->party_id);
                $party->update([
                    'due' => $party->due - $sale->due_amount,
                ]);
            }

            $business = Business::findOrFail(auth()->user()->business_id);
            $business->update([
                'shop_opening_balance' => $business->shop_opening_balance - $sale->paid_amount,
            ]);

            $sale->delete();
        }, 'sale:destroy', ['sale_id' => $sale->id]);

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
