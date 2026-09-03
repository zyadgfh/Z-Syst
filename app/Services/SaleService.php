<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Models\Business;
use App\Models\Party;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Services\Stock\StockAllocationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Facades\TransactionHelper; // Assuming it's in facades or App\Helpers
// Fallback if TransactionHelper is a class
use App\Helpers\TransactionHelper as HelpersTransactionHelper;

class SaleService
{
    public function __construct(
        private StockAllocationService $stockAllocationService,
        private FefoService $fefoService,
        private FinancialTransactionService $financialTransactionService,
        private CacheService $cacheService
    ) {}

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

    public function list(array $filters, int $businessId, int $perPage = 10)
    {
        $cacheKey = 'sales:list:' . $businessId . ':' . md5(serialize($filters) . $perPage);

        return $this->cacheService->remember($cacheKey, CacheService::TTL_SHORT, function () use ($filters, $businessId, $perPage) {
            return Sale::select('id', 'party_id', 'invoiceNumber', 'saleDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType')
                ->with('party:id,name,phone')
                ->when(!empty($filters['search']), function ($query) use ($filters) {
                    $term = '%' . $filters['search'] . '%';
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
                ->where('business_id', $businessId)
                ->latest()
                ->paginate($perPage);
        });
    }

    public function create(array $data, int $businessId, int $userId): Sale
    {
        return DB::transaction(function () use ($data, $businessId, $userId) {
            $fefoSettings = \App\Models\FefoSetting::getForBusiness($businessId);

            $productIds = collect($data['products'])->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($businessId, $productIds);

            foreach ($data['products'] as $productData) {
                $productId = $productData['product_id'];
                $productStocks = $businessStocks->get($productId, collect());
                $batchNo = $productData['batch_no'] ?? null;
                $requestedQty = (int) $productData['quantities'];

                if (! empty($batchNo)) {
                    $stock = $productStocks->first(fn ($item) => $item->batch_no === $batchNo);

                    if (! $stock) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => $batchNo,
                                'available' => 0,
                                'requested' => $requestedQty,
                            ]),
                            ['product_id' => $productId, 'batch_no' => $batchNo, 'available_qty' => 0, 'requested_qty' => $requestedQty]
                        );
                    }

                    if ($stock->productStock < $requestedQty) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => $stock->batch_no,
                                'available' => $stock->productStock,
                                'requested' => $requestedQty,
                            ]),
                            ['product_id' => $productId, 'batch_no' => $stock->batch_no, 'available_qty' => $stock->productStock, 'requested_qty' => $requestedQty]
                        );
                    }
                } elseif ($fefoSettings->fefo_enabled) {
                    $totalStock = $productStocks->filter(function ($stock) {
                        return $stock->productStock > 0 && (is_null($stock->expire_date) || $stock->expire_date >= now()->startOfDay());
                    })->sum('productStock');

                    if ($totalStock < $requestedQty) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                            __('errors.insufficient_stock', [
                                'product' => $productId,
                                'batch' => 'FEFO auto',
                                'available' => $totalStock,
                                'requested' => $requestedQty,
                            ]),
                            ['product_id' => $productId, 'batch_no' => null, 'available_qty' => $totalStock, 'requested_qty' => $requestedQty]
                        );
                    }
                }
            }

            // Validate due sale for walking customers
            if (!empty($data['dueAmount']) && empty($data['party_id'])) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
                    __('errors.due_sale_walking_customer'),
                    ['due_amount' => $data['dueAmount']]
                );
            }

            if (!empty($data['party_id'])) {
                $party = Party::where('id', $data['party_id'])
                    ->where('business_id', $businessId)
                    ->firstOrFail();
            }

            if (!empty($data['dueAmount']) && isset($party)) {
                $party->update([
                    'due' => $party->due + $data['dueAmount'],
                ]);
            }

            $business = Business::findOrFail($businessId);
            $business_name = $business->companyName;
            
            if(!empty($data['paidAmount'])){
                $business->update([
                    'remainingShopBalance' => $business->remainingShopBalance + $data['paidAmount'],
                ]);
            }

            $lossProfit = collect($data['products'])->pluck('lossProfit')->toArray();
            $discountAmount = $data['discountAmount'] ?? 0;

            $saleData = $data;
            unset($saleData['products']); // Prevent mass assignment issue if products is passed

            $sale = Sale::create(array_merge($saleData, [
                'user_id' => $userId,
                'business_id' => $businessId,
                'lossProfit' => array_sum($lossProfit) - $discountAmount,
                'meta' => [
                    'notes' => $data['notes'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                ],
            ]));

            $saleDetails = [];
            
            foreach ($data['products'] as $key => $productData) {
                $productId = $productData['product_id'];
                $quantity = (int) ($productData['quantities'] ?? 0);

                if (empty($productData['batch_no']) && $fefoSettings->fefo_enabled) {
                    $batches = $this->fefoService->getBestBatches($productId, $quantity, $businessId);
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
                            'lossProfit' => ($productData['lossProfit'] ?? 0) / count($data['products']),
                            'quantities' => $deductQty,
                            'expire_date' => $batch->expire_date,
                            'purchase_price' => $productData['purchase_price'] ?? 0,
                        ];

                        $this->stockAllocationService->allocate($batch, $deductQty, Sale::class, $sale->id, $userId, 'FEFO auto-deduction during sale creation');
                        $totalAllocated += $deductQty;
                        
                        $batch->refresh();
                        $this->fefoService->logFefoDeduction(
                            businessId: $businessId,
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
                    $saleDetails[$key] = [
                        'sale_id' => $sale->id,
                        'price' => $productData['price'],
                        'batch_no' => $productData['batch_no'] ?? null,
                        'product_id' => $productId,
                        'lossProfit' => $productData['lossProfit'] ?? 0,
                        'quantities' => $quantity,
                        'expire_date' => $productData['expire_date'] ?? null,
                        'purchase_price' => $productData['purchase_price'] ?? 0,
                    ];

                    $stock = $this->resolveStockForProduct($businessStocks, $productId, $productData['batch_no'] ?? null);

                    if ($stock) {
                        $this->stockAllocationService->allocate($stock, $quantity, Sale::class, $sale->id, $userId, 'Manual batch selection during sale');
                        
                        $this->fefoService->logFefoDeduction(
                            businessId: $businessId,
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
                if (config('zsyst.message_enabled')) {
                    if (function_exists('sendMessage') && function_exists('saleMessage')) {
                        sendMessage($party->phone, saleMessage($sale, $party, $business_name));
                    }
                }
            }

            // Record financial transaction for this sale
            $this->financialTransactionService->createFromSale($sale->id, $businessId);

            // Invalidate cached sale list for this business
            $this->cacheService->forget('sales:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return $sale->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,productName',
                'details:id,sale_id,product_id,price,quantities',
            ]);
        });
    }

    public function show(int $id, int $businessId)
    {
        return Sale::where('business_id', $businessId)
            ->with([
                'tax:id,name,rate',
                'party:id,name,phone,due',
                'user:id,name',
                'saleReturns:id,sale_id,invoice_no,return_date',
                'saleReturns.details:id,sale_return_id,return_qty,return_amount',
                'details:id,sale_id,product_id,price,quantities,purchase_price,batch_no,expire_date',
                'details.product' => function ($query) {
                    $query->select('id', 'productName')
                        ->withSum('stocks', 'productStock');
                },
            ])
            ->findOrFail($id);
    }

    public function update(Sale $sale, array $data, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($sale, $data, $businessId, $userId) {
            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();
            $productIds = collect($data['products'])->pluck('product_id')->filter()->unique()->values()->all();
            $allProductIds = collect([...$productIds, ...$prevDetails->pluck('product_id')->all()])->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($businessId, $allProductIds);
            $products = \App\Models\Product::select('id', 'productName')->whereIn('id', $productIds)->get();

            foreach ($products as $key => $product) {
                $productData = $data['products'][$key];
                $prevProduct = $prevDetails->first(fn($item) => $item->product_id == $product->id);
                $stock = $this->resolveStockForProduct($businessStocks, $product->id, $productData['batch_no'] ?? null);

                $productStock = $stock ? ($stock->productStock + ($prevProduct ? $prevProduct->quantities : 0)) : 0;
                $requestedQty = (int) $productData['quantities'];

                if ($productStock < $requestedQty) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                        __('errors.insufficient_stock', [
                            'product' => $product->productName,
                            'batch' => $productData['batch_no'] ?? 'N/A',
                            'available' => $productStock,
                            'requested' => $requestedQty,
                        ]),
                        [
                            'product_id' => $product->id,
                            'batch_no' => $productData['batch_no'] ?? null,
                            'available_qty' => $productStock,
                            'requested_qty' => $requestedQty,
                        ]
                    );
                }
            }

            foreach ($prevDetails as $prevItem) {
                $stock = $this->resolveStockForProduct($businessStocks, $prevItem->product_id, $prevItem->batch_no);
                if ($stock) {
                    $this->stockAllocationService->release($stock, $prevItem->quantities, Sale::class, $sale->id, $userId, 'Stock restored during sale update');
                }
            }

            $prevDetails->each->delete();

            $saleDetails = [];
            foreach ($data['products'] as $key => $productData) {
                $requestedQty = (int) ($productData['quantities'] ?? 0);
                $saleDetails[$key] = [
                    'sale_id' => $sale->id,
                    'price' => $productData['price'],
                    'batch_no' => $productData['batch_no'] ?? null,
                    'product_id' => $productData['product_id'],
                    'lossProfit' => $productData['lossProfit'] ?? 0,
                    'quantities' => $requestedQty,
                    'expire_date' => $productData['expire_date'] ?? null,
                    'purchase_price' => $productData['purchase_price'] ?? 0,
                ];

                $stock = $this->resolveStockForProduct($businessStocks, $productData['product_id'], $productData['batch_no'] ?? null);
                if ($stock) {
                    $this->stockAllocationService->allocate($stock, $requestedQty, Sale::class, $sale->id, $userId, 'Stock deducted during sale update');
                }
            }

            SaleDetails::insert($saleDetails);

            $dueAmount = $data['dueAmount'] ?? 0;
            if ($sale->dueAmount || $dueAmount) {
                $partyId = $data['party_id'] ?? null;
                if ($partyId) {
                    $party = Party::where('id', $partyId)->where('business_id', $businessId)->firstOrFail();
                    $party->update([
                        'due' => $partyId == $sale->party_id ? (($party->due - $sale->dueAmount) + $dueAmount) : ($party->due + $dueAmount),
                    ]);

                    if ($partyId != $sale->party_id && $sale->party_id) {
                        $prevParty = Party::where('id', $sale->party_id)->where('business_id', $businessId)->firstOrFail();
                        $prevParty->update([
                            'due' => $prevParty->due - $sale->dueAmount,
                        ]);
                    }
                }
            }

            $business = Business::findOrFail($businessId);
            $paidAmount = $data['paidAmount'] ?? 0;
            $business->update([
                'shopOpeningBalance' => ($business->shopOpeningBalance - $sale->paidAmount) + $paidAmount,
            ]);

            $lossProfit = collect($data['products'])->pluck('lossProfit')->toArray();
            $discountAmount = $data['discountAmount'] ?? 0;
            
            $saleData = $data;
            unset($saleData['products']);

            $sale->update(array_merge($saleData, [
                'user_id' => $userId,
                'business_id' => $businessId,
                'lossProfit' => array_sum($lossProfit) - $discountAmount,
                'meta' => [
                    'notes' => $data['notes'] ?? null,
                    'customer_phone' => $data['customer_phone'] ?? null,
                ],
            ]));
            
            // Recreate the financial transaction with updated amounts
            $this->financialTransactionService->deleteTransactionFor($sale);
            $this->financialTransactionService->createFromSale($sale->id, $businessId);

            // Invalidate cached sale list for this business
            $this->cacheService->forget('sales:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return $sale;
        });
    }

    public function delete(Sale $sale, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($sale, $businessId, $userId) {
            // Eager-load details to avoid lazy loading on the route-model-bound $sale
            $sale->load('details');
            $productIds = $sale->details->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($businessId, $productIds);

            foreach ($sale->details as $detail) {
                $stock = $this->resolveStockForProduct($businessStocks, $detail->product_id, $detail->batch_no);

                if ($stock) {
                    $this->stockAllocationService->release($stock, $detail->quantities, Sale::class, $sale->id, $userId, 'Stock restored due to sale deletion');

                    $this->fefoService->logFefoDeduction(
                        businessId: $businessId,
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

            if ($sale->dueAmount && $sale->party_id) {
                $party = Party::find($sale->party_id);
                if ($party) {
                    $party->update([
                        'due' => $party->due - $sale->dueAmount,
                    ]);
                }
            }

            $business = Business::findOrFail($businessId);
            $business->update([
                'shopOpeningBalance' => $business->shopOpeningBalance - $sale->paidAmount,
            ]);

            // Delete associated financial transactions
            $this->financialTransactionService->deleteTransactionFor($sale);

            $sale->delete();

            // Invalidate cached sale list for this business
            $this->cacheService->forget('sales:list:' . $businessId . ':' . md5(serialize([]) . 10));

            return true;
        });
    }
}