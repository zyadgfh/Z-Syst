<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Stock;
use App\Services\Stock\StockAllocationService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockAllocationService $stockAllocationService,
        private FinancialTransactionService $financialTransactionService
    ) {}

    public function list(array $filters, int $businessId, int $perPage = 10)
    {
        return Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
            ->with('party:id,name,phone')
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $term = '%' . $filters['search'] . '%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('paymentType', 'like', $term)
                        ->orWhere('invoiceNumber', 'like', $term)
                        ->orWhere('note', 'like', $term)
                        ->orWhereHas('party', function ($query) use ($term) {
                            $query->where('name', 'like', $term)
                                ->orWhere('phone', 'like', $term);
                        });
                });
            })
            ->withCount('purchaseReturns')
            ->where('business_id', $businessId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data, int $businessId, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $businessId, $userId) {
            $dueAmount = $data['dueAmount'] ?? 0;
            if ($dueAmount > 0) {
                $party = Party::where('id', $data['party_id'])
                    ->where('business_id', $businessId)
                    ->firstOrFail();
                $party->update([
                    'due' => $party->due + $dueAmount,
                ]);
            }

            $business = Business::findOrFail($businessId);
            $paidAmount = $data['paidAmount'] ?? 0;
            if ($paidAmount > 0) {
                $business->update([
                    'remainingShopBalance' => $business->remainingShopBalance - $paidAmount,
                ]);
            }

            $purchaseData = $data;
            unset($purchaseData['products']); // Avoid mass assignment issues

            $purchase = Purchase::create(array_merge($purchaseData, [
                'user_id' => $userId,
                'business_id' => $businessId,
            ]));

            $purchaseDetails = [];
            foreach ($data['products'] as $key => $product_data) {
                $purchaseDetails[$key] = [
                    'purchase_id' => $purchase->id,
                    'batch_no' => $product_data['batch_no'] ?? null,
                    'product_id' => $product_data['product_id'],
                    'expire_date' => $product_data['expire_date'] ?? null,
                    'quantities' => $product_data['quantities'] ?? 0,
                    'sales_price' => $product_data['sales_price'] ?? 0,
                    'profit_percent' => $product_data['profit_percent'] ?? 0,
                    'wholesale_price' => $product_data['wholesale_price'] ?? 0,
                    'purchase_with_tax' => $product_data['purchase_with_tax'] ?? 0,
                    'purchase_without_tax' => $product_data['purchase_without_tax'] ?? 0,
                ];
            }

            PurchaseDetails::insert($purchaseDetails);

            // Batch-load products to avoid N+1 queries
            $productIds = collect($purchaseDetails)->pluck('product_id')->unique()->values()->all();
            $productsById = Product::whereIn('id', $productIds)->get()->keyBy('id');

            // Batch-load existing stocks for these products to avoid N+1 queries
            $existingStocks = Stock::whereIn('product_id', $productIds)
                ->whereIn('batch_no', collect($purchaseDetails)->pluck('batch_no')->filter()->values()->all())
                ->orWhere(function ($query) use ($productIds) {
                    $query->whereIn('product_id', $productIds)->whereNull('batch_no');
                })
                ->get()
                ->groupBy('product_id');

            foreach ($purchaseDetails as $item) {
                $product = $productsById->get($item['product_id']);
                if (! $product) {
                    continue;
                }
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                // Resolve stock from pre-loaded collection instead of per-item query
                $productStocks = $existingStocks->get($product->id, collect());
                $stock = null;
                if (!empty($item['batch_no'])) {
                    $stock = $productStocks->first(fn($s) => $s->batch_no === $item['batch_no']);
                }
                if (! $stock) {
                    $stock = $productStocks->first();
                }

                if ($stock) {
                    $stock->update([
                        'batch_no' => $item['batch_no'],
                        'expire_date' => $item['expire_date'],
                    ]);
                    // Add stock and log movement
                    $this->stockAllocationService->addStock(
                        $stock, 
                        $item['quantities'], 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Purchase completed'
                    );
                } else {
                    $newStock = Stock::create(array_merge($data, [
                        'business_id' => $businessId,
                        'batch_no' => $item['batch_no'],
                        'product_id' => $item['product_id'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => 0, // start at 0, then addStock
                    ]));

                    $this->stockAllocationService->addStock(
                        $newStock, 
                        $item['quantities'], 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Initial stock from purchase'
                    );
                }
            }

            // Record financial transaction for this purchase
            $this->financialTransactionService->createFromPurchase($purchase->id, $businessId);

            return $purchase->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,productName',
                'details:id,purchase_id,product_id,purchase_with_tax,quantities',
            ]);
        });
    }

    public function show(int $id)
    {
        return Purchase::with([
            'tax',
            'user:id,name',
            'party:id,name,phone',
            'purchaseReturns.details',
            'details.product:id,productName,tax_type',
            'details:id,purchase_id,product_id,purchase_with_tax,quantities,batch_no,purchase_without_tax,profit_percent,sales_price,wholesale_price',
        ])->findOrFail($id);
    }

    public function update(Purchase $purchase, array $data, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($purchase, $data, $businessId, $userId) {
            $batch_numbers = collect($data['products'])->pluck('batch_no');
            $prev_stocks = Stock::whereIn('batch_no', $batch_numbers)->get();
            $prev_purchase_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();

            // Validate stock/batch quantity matches if we are decreasing the quantity
            foreach ($data['products'] as $req_item) {
                $prev_stock = $prev_stocks->where('batch_no', $req_item['batch_no'])->first();
                $prev_purchase_detail = $prev_purchase_details->where('batch_no', $req_item['batch_no'])->first();

                if ($prev_purchase_detail && $prev_purchase_detail->quantities > $req_item['quantities']) {
                    $diff = $prev_purchase_detail->quantities - $req_item['quantities'];
                    if ($prev_stock && $prev_stock->productStock < $diff) {
                        throw new BusinessRuleException(
                            ErrorCode::BUSINESS_BATCH_QUANTITY_MISMATCH,
                            __('errors.batch_quantity_mismatch', ['batch_no' => $prev_stock->batch_no]),
                            [
                                'batch_no' => $prev_stock->batch_no,
                                'available_stock' => $prev_stock->productStock,
                                'requested_qty' => $req_item['quantities'],
                            ]
                        );
                    }
                }
            }

            // Deduct previous stock
            foreach ($prev_purchase_details as $prev_detail) {
                $stock = Stock::where('batch_no', $prev_detail->batch_no)->first();
                if ($stock) {
                    $this->stockAllocationService->allocate(
                        $stock, 
                        $prev_detail->quantities, 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Reverting previous purchase quantities'
                    );
                }
            }

            $purchaseDetails = [];
            foreach ($data['products'] as $key => $product_data) {
                $purchaseDetails[$key] = [
                    'purchase_id' => $purchase->id,
                    'batch_no' => $product_data['batch_no'],
                    'product_id' => $product_data['product_id'],
                    'expire_date' => $product_data['expire_date'],
                    'quantities' => $product_data['quantities'] ?? 0,
                    'sales_price' => $product_data['sales_price'] ?? 0,
                    'profit_percent' => $product_data['profit_percent'] ?? 0,
                    'wholesale_price' => $product_data['wholesale_price'] ?? 0,
                    'purchase_with_tax' => $product_data['purchase_with_tax'] ?? 0,
                    'purchase_without_tax' => $product_data['purchase_without_tax'] ?? 0,
                ];
            }

            // Batch-load products to avoid N+1 queries
            $updateProductIds = collect($purchaseDetails)->pluck('product_id')->unique()->values()->all();
            $updateProductsById = Product::whereIn('id', $updateProductIds)->get()->keyBy('id');

            $updateStocks = Stock::whereIn('product_id', $updateProductIds)
                ->whereIn('batch_no', collect($purchaseDetails)->pluck('batch_no')->filter()->values()->all())
                ->orWhere(function ($query) use ($updateProductIds) {
                    $query->whereIn('product_id', $updateProductIds)->whereNull('batch_no');
                })
                ->get()
                ->groupBy('product_id');

            foreach ($purchaseDetails as $item) {
                $product = $updateProductsById->get($item['product_id']);
                if (! $product) {
                    continue;
                }
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                // Resolve stock from pre-loaded collection
                $productStocks = $updateStocks->get($product->id, collect());
                $stock = null;
                if ($item['batch_no']) {
                    $stock = $productStocks->first(fn($s) => $s->batch_no === $item['batch_no']);
                }
                if (! $stock) {
                    $stock = $productStocks->first();
                }

                if ($stock) {
                    $stock->update([
                        'batch_no' => $item['batch_no'],
                        'expire_date' => $item['expire_date'],
                    ]);
                    $this->stockAllocationService->addStock(
                        $stock, 
                        $item['quantities'], 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Applying new purchase quantities'
                    );
                } else {
                    $newStock = Stock::create(array_merge($data, [
                        'business_id' => $businessId,
                        'batch_no' => $item['batch_no'],
                        'product_id' => $item['product_id'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => 0,
                    ]));
                    $this->stockAllocationService->addStock(
                        $newStock, 
                        $item['quantities'], 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Applying new purchase quantities'
                    );
                }
            }

            $dueAmount = $data['dueAmount'] ?? 0;
            if ($purchase->dueAmount || $dueAmount) {
                $partyId = $data['party_id'] ?? null;
                if ($partyId) {
                    $party = Party::where('id', $partyId)->where('business_id', $businessId)->firstOrFail();
                    $party->update([
                        'due' => $partyId == $purchase->party_id ? (($party->due - $purchase->dueAmount) + $dueAmount) : ($party->due + $dueAmount),
                    ]);

                    if ($partyId != $purchase->party_id && $purchase->party_id) {
                        $prev_party = Party::where('id', $purchase->party_id)->where('business_id', $businessId)->firstOrFail();
                        $prev_party->update([
                            'due' => $prev_party->due - $purchase->dueAmount,
                        ]);
                    }
                }
            }

            $business = Business::findOrFail($businessId);
            $paidAmount = $data['paidAmount'] ?? 0;
            $business->update([
                'remainingShopBalance' => ($business->remainingShopBalance + $purchase->paidAmount) - $paidAmount,
            ]);

            $purchaseData = $data;
            unset($purchaseData['products']);
            
            $purchase->update(array_merge($purchaseData, [
                'user_id' => $userId,
            ]));

            $empty_qty_items = array_filter($purchaseDetails, function ($item) {
                return $item['quantities'] > 0;
            });

            PurchaseDetails::where('purchase_id', $purchase->id)->delete();
            PurchaseDetails::insert($empty_qty_items);
            
            // Recreate the financial transaction with updated amounts
            $this->financialTransactionService->deleteTransactionFor($purchase);
            $this->financialTransactionService->createFromPurchase($purchase->id, $businessId);

            return $purchase;
        });
    }

    public function delete(Purchase $purchase, int $businessId, int $userId)
    {
        return DB::transaction(function () use ($purchase, $businessId, $userId) {
            $purchase_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            $prev_stocks = Stock::whereIn('batch_no', $purchase_details->pluck('batch_no'))->get();

            foreach ($purchase_details as $purchase_detail) {
                $prev_stock = $prev_stocks->where('batch_no', $purchase_detail->batch_no)->first();

                if ($prev_stock && $prev_stock->productStock < $purchase_detail->quantities) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_BATCH_QUANTITY_MISMATCH,
                        __('errors.batch_quantity_mismatch', ['batch_no' => $prev_stock->batch_no]),
                        [
                            'batch_no' => $prev_stock->batch_no,
                            'available_stock' => $prev_stock->productStock,
                            'requested_qty' => $purchase_detail->quantities,
                        ]
                    );
                }

                if ($prev_stock) {
                    $this->stockAllocationService->allocate(
                        $prev_stock, 
                        $purchase_detail->quantities, 
                        Purchase::class, 
                        $purchase->id, 
                        $userId, 
                        'Reversing purchase due to deletion'
                    );
                }
            }

            if ($purchase->dueAmount && $purchase->party_id) {
                $party = Party::find($purchase->party_id);
                if ($party) {
                    $party->update([
                        'due' => $party->due - $purchase->dueAmount,
                    ]);
                }
            }

            $business = Business::findOrFail($businessId);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $purchase->paidAmount,
            ]);

            // Delete associated financial transactions
            $this->financialTransactionService->deleteTransactionFor($purchase);

            $purchase->delete();
            
            return true;
        });
    }
}