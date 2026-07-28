<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\Party;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Business;
use App\Models\FefoLog;
use App\Models\FefoSetting;
use App\Models\SaleDetails;
use App\Services\FefoService;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Exceptions\NotFoundException;
use App\Exceptions\TransactionException;
use App\Helpers\TransactionHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AcnooSaleController extends Controller
{
    protected FefoService $fefoService;

    public function __construct(FefoService $fefoService)
    {
        $this->fefoService = $fefoService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->when(request('search'), function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                            ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                            ->orWhere('meta', 'like', '%' . request('search') . '%')
                            ->orWhereHas('party', function ($query) {
                                $query->where('name', 'like', '%' . request('search') . '%')
                                    ->orWhere('phone', 'like', '%' . request('search') . '%');
                            });
                    });
                })
                ->withCount('saleReturns')
                ->where('business_id', auth()->user()->business_id)
                ->latest()
                ->paginate(10);

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

            // Validate stock availability for all products
            $batch_numbers = collect($request->products)->pluck('batch_no')->filter()->toArray();

            // Only validate batch-specific stock if batch_no provided OR FEFO is disabled
            if (!empty($batch_numbers)) {
                $stocks = Stock::whereIn('batch_no', $batch_numbers)->where('business_id', $business_id)->get();

                foreach ($stocks as $key => $stock) {
                    if ($stock->productStock < $request->products[$key]['quantities']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $request->products[$key]['product_id'],
                                'batch' => $stock->batch_no,
                                'available' => $stock->productStock,
                                'requested' => $request->products[$key]['quantities'],
                            ]),
                            [
                                'product_id' => $request->products[$key]['product_id'],
                                'batch_no' => $stock->batch_no,
                                'available_qty' => $stock->productStock,
                                'requested_qty' => $request->products[$key]['quantities'],
                            ]
                        );
                    }
                }
            } elseif ($fefoSettings->fefo_enabled) {
                // FEFO auto-selection: validate total stock across all batches
                foreach ($request->products as $productData) {
                    $totalStock = Stock::where('product_id', $productData['product_id'])
                        ->where('business_id', $business_id)
                        ->where('productStock', '>', 0)
                        ->where(function ($q) {
                            $q->whereNull('expire_date')
                              ->orWhere('expire_date', '>=', now()->startOfDay());
                        })
                        ->sum('productStock');

                    if ($totalStock < $productData['quantities']) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productData['product_id'],
                                'batch' => 'FEFO auto',
                                'available' => $totalStock,
                                'requested' => $productData['quantities'],
                            ]),
                            [
                                'product_id' => $productData['product_id'],
                                'batch_no' => null,
                                'available_qty' => $totalStock,
                                'requested_qty' => $productData['quantities'],
                            ]
                        );
                    }
                }
            }

            // Validate due sale for walking customers
            if ($request->dueAmount && !$request->party_id) {
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

                    $stock = Stock::where('batch_no', $productData['batch_no'])
                        ->where('product_id', $productId)
                        ->first();

                    if ($stock) {
                        $stock->decrement('productStock', $quantity);
                        $stock->refresh();

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

        TransactionHelper::run(function () use ($request, $sale) {
            $business_id = auth()->user()->business_id;

            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();
            $productIds = collect($request->products)->pluck('product_id')->toArray();
            $products = Product::whereIn('id', $productIds)->get();

            foreach ($products as $key => $product) {
                $prevProduct = $prevDetails->first(function ($item) use ($product) {
                    return $item->product_id == $product->id;
                });

                $stock = Stock::where('product_id', $product->id)
                            ->where('batch_no', $request->products[$key]['batch_no'] ?? null)
                            ->first();

                if (!$stock) {
                    $stock = Stock::where('product_id', $product->id)->orderBy('id', 'asc')->first();
                }

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
                    'expire_date' => $productData['expire_date'] ?? null,
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
        TransactionHelper::run(function () use ($sale) {
            $business_id = auth()->user()->business_id;
            $fefoService = $this->fefoService;

            foreach ($sale->details as $detail) {
                $stock = Stock::where('product_id', $detail->product_id)
                              ->where('batch_no', $detail->batch_no)
                              ->first();

                if (!$stock) {
                    $stock = Stock::where('product_id', $detail->product_id)->orderBy('id', 'asc')->first();
                }

                if ($stock) {
                    $stock->increment('productStock', $detail->quantities);

                    // Log FEFO return (stock restoration)
                    $stock->refresh();
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
                    'due' => $party->due - $sale->dueAmount
                ]);
            }

            $business = Business::findOrFail(auth()->user()->business_id);
            $business->update([
                'shopOpeningBalance' => $business->shopOpeningBalance - $sale->paidAmount
            ]);

            $sale->delete();
        }, 'sale:destroy', ['sale_id' => $sale->id]);

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}

