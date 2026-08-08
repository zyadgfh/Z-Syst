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
            ->select('id', 'business_id', 'product_id', 'batch_no', 'expire_date', 'productStock')
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
        $data = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
            ->with('party:id,name,phone')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('paymentType', 'like', $term)
                        ->orWhere('invoiceNumber', 'like', $term)
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

        $sale = TransactionHelper::run(function () use ($request) {
            $business_id = auth()->user()->business_id;
            $fefoService = $this->fefoService;
            $fefoSettings = FefoSetting::getForBusiness($business_id);

            $productIds = collect($request->products)->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($business_id, $productIds);

            foreach ($request->products as $productData) {
                $productId = $productData['product_id'];
                $productStocks = $businessStocks->get($productId, collect());
                $batchNo = $productData['batch_no'] ?? null;

                if (! empty($batchNo)) {
                    $stock = $productStocks->first(fn ($item) => $item->batch_no === $batchNo);

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

                    if ($stock->productStock < $productData['quantities']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => $stock->batch_no,
                                'available' => $stock->productStock,
                                'requested' => $productData['quantities'],
                            ]),
                            [
                                'product_id' => $productId,
                                'batch_no' => $stock->batch_no,
                                'available_qty' => $stock->productStock,
                                'requested_qty' => $productData['quantities'],
                            ]
                        );
                    }
                } elseif ($fefoSettings->fefo_enabled) {
                    $totalStock = $productStocks->filter(function ($stock) {
                        return $stock->productStock > 0 && (is_null($stock->expire_date) || $stock->expire_date >= now()->startOfDay());
                    })->sum('productStock');

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
            if ($request->dueAmount && ! $request->party_id) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
                    __('errors.due_sale_walking_customer'),
                    ['due_amount' => $request->dueAmount]
                );
            }

            if ($request->party_id) {
                $party = Party::findOrFail($request->party_id);
            }

            if ($request->dueAmount && isset($party)) {
                $party->update([
                    'due' => $party->due + $request->dueAmount,
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business_name = $business->companyName;
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $request->paidAmount,
            ]);

            $lossProfit = collect($request->products)->pluck('lossProfit')->toArray();

            $sale = Sale::create($request->all() + [
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'lossProfit' => array_sum($lossProfit) - ($request->discountAmount ?? 0),
                'meta' => [
                    'notes' => $request->notes,
                    'customer_phone' => $request->customer_phone,
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

                        $deductQty = min($batch->productStock, $quantity - $totalAllocated);

                        $saleDetails[] = [
                            'sale_id' => $sale->id,
                            'price' => $productData['price'],
                            'batch_no' => $batch->batch_no,
                            'product_id' => $productId,
                            'lossProfit' => $productData['lossProfit'] / count($request->products) ?? 0,
                            'quantities' => $deductQty,
                            'expire_date' => $batch->expire_date,
                            'purchase_price' => $productData['purchase_price'] ?? 0,
                        ];

                        $batch->decrement('productStock', $deductQty);
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
                            quantityRemaining: $batch->productStock,
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
                        'lossProfit' => $productData['lossProfit'],
                        'quantities' => $quantity,
                        'expire_date' => $productData['expire_date'] ?? null,
                        'purchase_price' => $productData['purchase_price'] ?? 0,
                    ];

                    $stock = $this->resolveStockForProduct($businessStocks, $productId, $productData['batch_no'] ?? null);

                    if ($stock) {
                        $stock->decrement('productStock', $quantity);

                        // Log FEFO deduction for manual selection
                        $fefoService->logFefoDeduction(
                            businessId: $business_id,
                            productId: $productId,
                            stockId: $stock->id,
                            batchNo: $stock->batch_no,
                            expireDate: $stock->expire_date,
                            quantityDeducted: $quantity,
                            quantityRemaining: $stock->productStock,
                            saleId: $sale->id,
                            notes: 'Manual batch selection during sale'
                        );
                    }
                }
            }

            SaleDetails::insert($saleDetails);

            if (isset($party) && $party->phone) {
                if (env('MESSAGE_ENABLED')) {
                    sendMessage($party->phone, saleMessage($sale, $party, $business_name));
                }
            }

            return $sale->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,productName',
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
        if ($sale->business_id !== auth()->user()->business_id) {
            abort(403);
        }

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

        TransactionHelper::run(function () use ($request, $sale) {
            $business_id = auth()->user()->business_id;

            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();
            $productIds = collect($request->products)->pluck('product_id')->filter()->unique()->values()->all();
            $allProductIds = collect([...$productIds, $prevDetails->pluck('product_id')->all()])->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($business_id, $allProductIds);
            $products = Product::select('id', 'productName')->whereIn('id', $productIds)->get();

            foreach ($products as $key => $product) {
                $prevProduct = $prevDetails->first(function ($item) use ($product) {
                    return $item->product_id == $product->id;
                });

                $stock = $this->resolveStockForProduct($businessStocks, $product->id, $request->products[$key]['batch_no'] ?? null);

                $productStock = 0;
                if ($prevProduct) {
                    $productStock = $stock ? ($stock->productStock + $prevProduct->quantities) : 0;
                } else {
                    $productStock = $stock ? $stock->productStock : 0;
                }

                if ($productStock < $request->products[$key]['quantities']) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                        __('errors.insufficient_stock', [
                            'product' => $product->productName,
                            'batch' => $request->products[$key]['batch_no'] ?? 'N/A',
                            'available' => $productStock,
                            'requested' => $request->products[$key]['quantities'],
                        ]),
                        [
                            'product_id' => $product->id,
                            'batch_no' => $request->products[$key]['batch_no'] ?? null,
                            'available_qty' => $productStock,
                            'requested_qty' => $request->products[$key]['quantities'],
                        ]
                    );
                }
            }

            // Restore stock for previously saved sale details
            foreach ($prevDetails as $prevItem) {
                $stock = $this->resolveStockForProduct($businessStocks, $prevItem->product_id, $prevItem->batch_no);

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
                    'expire_date' => $productData['expire_date'] ?? null,
                    'purchase_price' => $productData['purchase_price'] ?? 0,
                ];

                $stock = $this->resolveStockForProduct($businessStocks, $productData['product_id'], $productData['batch_no'] ?? null);

                if ($stock) {
                    $stock->decrement('productStock', $productData['quantities']);
                }
            }

            SaleDetails::insert($saleDetails);

            // Update financial and business logic
            if ($sale->dueAmount || $request->dueAmount) {
                $party = Party::findOrFail($request->party_id);
                $party->update([
                    'due' => $request->party_id == $sale->party_id ? (($party->due - $sale->dueAmount) + $request->dueAmount) : ($party->due + $request->dueAmount),
                ]);

                if ($request->party_id != $sale->party_id) {
                    $prevParty = Party::findOrFail($sale->party_id);
                    $prevParty->update([
                        'due' => $prevParty->due - $sale->dueAmount,
                    ]);
                }
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'shopOpeningBalance' => ($business->shopOpeningBalance - $sale->paidAmount) + $request->paidAmount,
            ]);

            $lossProfit = collect($request->products)->pluck('lossProfit')->toArray();

            $sale->update($request->all() + [
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'lossProfit' => array_sum($lossProfit) - ($request->discountAmount ?? 0),
                'meta' => [
                    'notes' => $request->notes,
                    'customer_phone' => $request->customer_phone,
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
                    $stock->increment('productStock', $detail->quantities);

                    // Log FEFO return (stock restoration)
                    $fefoService->logFefoDeduction(
                        businessId: $business_id,
                        productId: $detail->product_id,
                        stockId: $stock->id,
                        batchNo: $stock->batch_no,
                        expireDate: $stock->expire_date,
                        quantityDeducted: -$detail->quantities, // negative = restored
                        quantityRemaining: $stock->productStock,
                        saleId: $sale->id,
                        saleDetailId: $detail->id,
                        notes: 'Stock restored due to sale deletion'
                    );
                }
            }

            if ($sale->dueAmount) {
                $party = Party::findOrFail($sale->party_id);
                $party->update([
                    'due' => $party->due - $sale->dueAmount,
                ]);
            }

            $business = Business::findOrFail(auth()->user()->business_id);
            $business->update([
                'shopOpeningBalance' => $business->shopOpeningBalance - $sale->paidAmount,
            ]);

            $sale->delete();
        }, 'sale:destroy', ['sale_id' => $sale->id]);

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
