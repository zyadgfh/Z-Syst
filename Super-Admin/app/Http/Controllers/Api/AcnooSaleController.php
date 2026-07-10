<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Party;
use App\Models\Stock;
use App\Events\SaleSms;
use App\Models\SaleDetails;
use App\Helpers\HasUploader;
use App\Models\Transaction;
use App\Traits\DateFilterTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AcnooSaleController extends Controller
{
    use HasUploader;
    Use DateFilterTrait;

    public function index(Request $request)
    {
        $business_id = auth()->user()->business_id;

        // Build the query with all eager loads
        $query = Sale::with([
            'user:id,name,role',
            'party:id,name,email,phone,type,address',
            'details',
            'details.stock:id,batch_no,productStock',
            'details.product:id,productName,category_id,productCode,productPurchasePrice,productStock,product_type',
            'details.product.category:id,categoryName',
            'saleReturns.details',
            'vat:id,name,rate',
            'branch:id,name,phone,address',
            'transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta',
            'transactions.paymentType:id,name,meta'
        ])->where('business_id', $business_id);

        // Filter returned sales
        $query->when(request('returned-sales') == "true", function ($query) {
            $query->whereHas('saleReturns');
        });

        // Branch filter
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($query, request('duration'), 'saleDate', request('from_date'), request('to_date'));
        }

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('invoiceNumber', 'like', "%{$request->search}%")
                    ->orWhere('paymentType', 'like', "%{$request->search}%")
                    ->orWhereHas('party', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('payment_type', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                    ->orWhereHas('branch', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            });
        }

        $data = $query->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_amount' => $data->sum('totalAmount'),
            'data' => $data,
        ]);
    }

    public function show(string $id)
    {
        $business_id = auth()->user()->business_id;

        $sale = Sale::with(['user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no,productStock', 'details.product:id,productName,category_id,productCode,productPurchasePrice,productStock,product_type', 'details.product.category:id,categoryName', 'saleReturns.details', 'vat:id,name,rate', 'branch:id,name,phone,address', 'transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name,meta'])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!$sale) {
            return response()->json([
                'message' => __('Sale not found.'),
            ], 404);
        }

        return response()->json([
            'message' => __('Sale fetched successfully.'),
            'data' => $sale,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'vat_id' => 'nullable|exists:vats,id',
            'products' => 'required',
            'saleDate' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'rounding_option' => 'nullable|in:none,round_up,nearest_whole_number,nearest_0.05,nearest_0.1,nearest_0.5',
        ]);

        DB::beginTransaction();
        try {
            $business_id = auth()->user()->business_id;
            $request_products = json_decode($request->products, true);
            $payments = json_decode($request->payments, true);

            $paidAmount = collect($payments)
                ->reject(fn($p) => strtolower($p['type'] ?? '') === 'cheque')
                ->sum(fn($p) => $p['amount'] ?? 0);

            if ($request->dueAmount > 0) {
                $allowDueForGuest = product_setting()?->modules['allow_due_sale'] ?? false;

                // No party
                if (!$request->party_id) {
                    if (!$allowDueForGuest) {
                        return response()->json([
                            'message' => __('You have not allowed guest sales, so you cannot sell on due for a walking customer.')
                        ], 400);
                    }
                } else {
                    // Party exist
                    $party = Party::findOrFail($request->party_id);
                    $newDue = $party->due + $request->dueAmount;

                    // Check credit limit
                    if ($party->credit_limit > 0 && $newDue > $party->credit_limit) {
                        return response()->json([
                            'message' => __('Sale cannot be created. Party due will exceed credit limit!')
                        ], 400);
                    }

                    $party->update(['due' => $newDue]);
                }
            }

            updateBalance($paidAmount, 'increment');

            $sale = Sale::create($request->except('image', 'isPaid', 'lossProfit', 'payment_type_id') + [
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'isPaid' => filter_var($request->isPaid, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'image' => $request->image ? $this->upload($request, 'image') : null,
                'meta' => [
                    'note' => $request->note,
                ],
            ]);

            $subtotal = 0;
            $totalPurchaseAmount = 0;

            foreach ($request_products as $productData) {
                $productDiscount = $productData['discount'] ?? 0;

                $product = Product::findOrFail($productData['product_id']);
                $qty = $productData['quantities'] ?? 1;
                $price = $productData['price'] ?? 0;

                $lineSubtotal = ($price * $qty) - $productDiscount;
                $subtotal += $lineSubtotal;

                //  Combo Products
                if ($product->product_type == 'combo') {
                    $comboTotalPurchase = 0;

                    foreach ($product->combo_products as $comboItem) {
                        $stock = Stock::findOrFail($comboItem->stock_id);
                        $requiredQty = $comboItem->quantity * $qty;

                        if ($stock->productStock < $requiredQty) {
                            return response()->json([
                                'message' => "Combo item '{$productData['product_name']}' (Batch: {$stock->batch_no}) not available. Available stock: {$stock->productStock}"
                            ], 400);
                        }

                        // Decrement stock
                        $stock->decrement('productStock', $requiredQty);

                        // Combo purchase total
                        $comboTotalPurchase += $stock->productPurchasePrice * $requiredQty;
                    }

                    $totalPurchaseAmount += $comboTotalPurchase;

                    // Store combo in SaleDetails
                    $saleDetails[] = [
                        'sale_id' => $sale->id,
                        'stock_id' => null,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'lossProfit' => $lineSubtotal - $comboTotalPurchase,
                        'quantities' => $qty,
                        'productPurchasePrice' => $comboTotalPurchase ?? 0,
                        'expire_date' => null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info
                            ? json_encode($product->warranty_guarantee_info)
                            : null,
                    ];
                } // Single / Variant Products
                else {
                    $stock = Stock::findOrFail($productData['stock_id']);

                    if ($stock->productStock < $qty) {
                        return response()->json([
                            'message' => "Stock not available for product: {$productData['product_name']}. Available: {$stock->productStock}"
                        ], 400);
                    }

                    // Decrement stock
                    $stock->decrement('productStock', $qty);
                    $purchasePrice = $stock->productPurchasePrice ?? 0;

                    // Calculate profit/loss
                    $totalPurchaseAmount += ($purchasePrice * $qty);

                    $saleDetails[] = [
                        'sale_id' => $sale->id,
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'lossProfit' => $lineSubtotal - ($purchasePrice * $qty),
                        'quantities' => $qty,
                        'productPurchasePrice' => $purchasePrice,
                        'expire_date' => $stock->expire_date ?? null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info
                            ? json_encode($product->warranty_guarantee_info)
                            : null,
                    ];
                }
            }

            SaleDetails::insert($saleDetails);

            $saleLossProfit = ($subtotal - $totalPurchaseAmount) - ($request->discountAmount ?? 0);

            $sale->update([
                'lossProfit' => $saleLossProfit,
            ]);

            // MultiPaymentProcessed Event
            event(new MultiPaymentProcessed(
                $payments,
                $sale->id,
                'sale',
                $paidAmount,
                $request->party_id ?? null
            ));

            // Send SMS
            event(new SaleSms($sale));

            DB::commit();
            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $sale->load('user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no', 'details.product:id,productName,category_id,product_type', 'details.product.category:id,categoryName', 'saleReturns.details', 'vat:id,name,rate', 'payment_type:id,name', 'branch:id,name,phone,address'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'party_id' => 'nullable|exists:parties,id',
            'vat_id' => 'nullable|exists:vats,id',
            'products' => 'required',
            'saleDate' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'rounding_option' => 'nullable|in:none,round_up,nearest_whole_number,nearest_0.05,nearest_0.1,nearest_0.5',
        ]);

        $sale = Sale::findOrFail($id);
        $business_id = auth()->user()->business_id;

        DB::beginTransaction();
        try {
            if ($sale->load('saleReturns')->saleReturns->count() > 0) {
                return response()->json([
                    'message' => __("You cannot update this sale because it has already been returned.")
                ], 400);
            }

            $request_products = json_decode($request->products, true);
            $payments = json_decode($request->payments, true);
            $prevDetails = SaleDetails::where('sale_id', $sale->id)->get();

            // Rollback previous stock
            foreach ($prevDetails as $prevItem) {
                if ($prevItem->stock_id) {
                    $stock = Stock::findOrFail($prevItem->stock_id);
                    $stock->increment('productStock', $prevItem->quantities);
                }
            }

            $prevDetails->each->delete();

            $saleDetails = [];
            $totalLossProfit = 0;
            $totalProductWiseDiscount = 0;

            foreach ($request_products as $productData) {
                $product = Product::findOrFail($productData['product_id']);
                $qty = $productData['quantities'] ?? 1;
                $price = $productData['price'] ?? 0;

                $productDiscount = $productData['discount'] ?? 0;
                $totalProductWiseDiscount += $productDiscount;

                $unitSalePrice = $price;
                $unitDiscount  = $productDiscount / max($qty, 1);
                $finalSalePricePerUnit = $unitSalePrice - $unitDiscount;

                // Combo product
                if ($product->product_type == 'combo') {
                    foreach ($product->combo_products as $comboItem) {
                        $stock = Stock::findOrFail($comboItem->stock_id);
                        $requiredQty = $comboItem->quantity * $qty;

                        if ($stock->productStock < $requiredQty) {
                            return response()->json([
                                'message' => "Combo item '{$productData['product_name']}' (Batch: {$stock->batch_no}) not available. Available stock: {$stock->productStock}"
                            ], 400);
                        }

                        // Decrement stock
                        $stock->decrement('productStock', $requiredQty);

                        // Total combo purchase
                        $comboPurchaseTotal += ($stock->productPurchasePrice * $requiredQty); // 🔥 changed
                    }

                    // purchase price per unit of combo
                    $purchaseUnit = $comboPurchaseTotal / max($qty, 1);

                    // correct lossProfit
                    $lossProfit = ($finalSalePricePerUnit - $purchaseUnit) * $qty;
                    $totalLossProfit += $lossProfit;

                    $saleDetails[] = [
                        'sale_id' => $sale->id,
                        'stock_id' => null,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'lossProfit' => $lossProfit,
                        'quantities' => $qty,
                        'productPurchasePrice' => $purchaseUnit,
                        'expire_date' => null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info
                            ? json_encode($product->warranty_guarantee_info)
                            : null,
                    ];
                } // Single / Variant product
                else {
                    $stock = Stock::findOrFail($productData['stock_id']);
                    if ($stock->productStock < $qty) {
                        return response()->json([
                            'message' => "Stock not available for product: {$productData['product_name']}. Available: {$stock->productStock}"
                        ], 400);
                    }

                    $stock->decrement('productStock', $qty);

                    $purchaseUnit = $stock->productPurchasePrice ?? 0;
                    $lossProfit = ($finalSalePricePerUnit - $purchaseUnit) * $qty;
                    $totalLossProfit += $lossProfit;

                    $saleDetails[] = [
                        'sale_id' => $sale->id,
                        'stock_id' => $stock->id,
                        'product_id' => $product->id,
                        'price' => $price,
                        'discount' => $productDiscount,
                        'lossProfit' => $lossProfit,
                        'quantities' => $qty,
                        'productPurchasePrice' => $purchaseUnit,
                        'expire_date' => $stock->expire_date ?? null,
                        'warranty_guarantee_info' => $product->warranty_guarantee_info ? json_encode($product->warranty_guarantee_info) : null,
                    ];
                }
            }

            SaleDetails::insert($saleDetails);

            // Check if any old transaction type = deposit
            $hasDeposit = Transaction::where('business_id', $business_id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->where('type', 'deposit')
                ->exists();

            $paidAmount = collect($payments)
                ->reject(function ($p) use ($hasDeposit) {
                    // exclude cheque only if not deposit
                    return !$hasDeposit && strtolower($p['type'] ?? '') === 'cheque';
                })
                ->sum(fn($p) => $p['amount'] ?? 0);

            $dueAmount = max($request->totalAmount - $paidAmount, 0);


            // Handle party due
            if ($sale->dueAmount || $dueAmount) {
                $party = Party::findOrFail($request->party_id);

                $newDue = $request->party_id == $sale->party_id
                    ? (($party->due - $sale->dueAmount) + $dueAmount)
                    : ($party->due + $dueAmount);

                if ($party->credit_limit > 0 && $newDue > $party->credit_limit) {
                    return response()->json([
                        'message' => __("Cannot update sale. Party due will exceed credit limit!")
                    ], 400);
                }

                $party->update(['due' => $newDue]);

                if ($request->party_id != $sale->party_id) {
                    $prev_party = Party::findOrFail($sale->party_id);
                    $prev_party->update([
                        'due' => $prev_party->due - $sale->dueAmount
                    ]);
                }
            }

            // Adjust business balance
            $balanceDiff = $paidAmount - $sale->paidAmount;
            updateBalance($balanceDiff, 'increment');

            $sale->update($request->except('image', 'isPaid', 'paidAmount', 'paidAmount', 'dueAmount') + [
                'user_id' => auth()->id(),
                'isPaid' => filter_var($request->isPaid, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'lossProfit' => $totalLossProfit - ($request->discountAmount ?? 0) - $totalProductWiseDiscount,
                'image' => $request->image ? $this->upload($request, 'image', $sale->image) : $sale->image,
                'paidAmount' => $paidAmount > $request->totalAmount ? $request->totalAmount : $paidAmount,
                'dueAmount' => $dueAmount,
                'meta' => [
                    'note' => $request->note,
                ],
            ]);

            // Multiple Payment Process
            $oldTransactions = Transaction::where('business_id', $business_id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->get();

            // Revert old transactions
            foreach ($oldTransactions as $old) {
                switch ($old->transaction_type) {
                    case 'bank_payment':
                        $paymentType = PaymentType::find($old->payment_type_id);
                        if ($paymentType) {
                            $paymentType->decrement('balance', $old->amount);
                        }
                        break;

                    case 'wallet_payment':
                        if ($request->party_id) {
                            $party = Party::find($request->party_id);
                            if ($party) {
                                $party->increment('wallet', $old->amount);
                            }
                        }
                        break;

                    case 'cash_payment':
                    case 'cheque_payment':
                        // nothing to revert for cash/cheque
                        break;
                }
            }

            // Delete old transactions
            Transaction::where('business_id', $business_id)
                ->where('reference_id', $sale->id)
                ->where('platform', 'sale')
                ->delete();

            // Process new payments
            event(new MultiPaymentProcessed(
                $payments ?? [],
                $sale->id,
                'sale',
                $paidAmount,
                $request->party_id ?? null,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Sale updated successfully.'),
                'data' => $sale->load(
                    'details',
                    'details.product:id,productName,product_type',
                    'details.stock:id,batch_no',
                    'party:id,name,email,phone,address',
                    'vat:id,name,rate',
                    'branch:id,name,phone,address'
                ),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale)
    {
        foreach ($sale->details as $item) {
            Stock::findOrFail($item->stock_id)->increment('productStock', $item->quantities);
        }

        if ($sale->dueAmount) {
            $party = Party::findOrFail($sale->party_id);
            $party->update([
                'due' => $party->due - $sale->dueAmount
            ]);
        }

        updateBalance($sale->paidAmount, 'decrement');

        if (file_exists($sale->image)) {
            Storage::delete($sale->image);
        }

        $sale->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
