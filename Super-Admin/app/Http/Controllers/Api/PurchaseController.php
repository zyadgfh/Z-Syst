<?php

namespace App\Http\Controllers\Api;

use App\Events\MultiPaymentProcessed;
use App\Events\PurchaseSms;
use App\Models\Party;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Purchase;
use App\Models\Transaction;
use App\Traits\DateFilterTrait;
use Illuminate\Http\Request;
use App\Models\PurchaseDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\PurchaseReturn;

class PurchaseController extends Controller
{
    Use DateFilterTrait;

    public function index(Request $request)
    {
        $business_id = auth()->user()->business_id;

        // Build the query with all eager loads
        $query = Purchase::with(['user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.product:id,productName,category_id,product_type,vat_id,vat_type,vat_amount', 'details.stock:id,batch_no,variant_name,warehouse_id', 'details.product.vat:id,name,rate', 'details.product.category:id,categoryName', 'purchaseReturns.details', 'vat:id,name,rate', 'branch:id,name,phone,address','transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name'])->where('business_id', $business_id);

        // Filter returned sales (safer boolean check)
        $query->when(request('returned-purchase') == "true", function ($query) {
            $query->whereHas('purchaseReturns');
        });

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Apply date filter
        if(request('duration')){
            $this->applyDateFilter($query, request('duration'), 'purchaseDate', request('from_date'), request('to_date'));
        }

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('paymentType', 'like', "%{$request->search}%")
                    ->orWhere('invoiceNumber', 'like', "%{$request->search}%")
                     ->orWhereHas('party', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('payment_type', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    })
                    ->orWhereHas('branch', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%');
                    });
            });
        }

        // Finally fetch the data (preserves original structure)
        $data = $query->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_amount' => $data->sum('totalAmount'),
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $business_id = auth()->user()->business_id;

        $purchase = Purchase::with(['user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.product:id,productName,category_id,product_type,vat_id,vat_type,vat_amount', 'details.stock:id,batch_no,variant_name,warehouse_id', 'details.product.vat:id,name,rate', 'details.product.category:id,categoryName', 'purchaseReturns.details', 'vat:id,name,rate', 'branch:id,name,phone,address','transactions:id,platform,transaction_type,amount,date,invoice_no,reference_id,payment_type_id,meta', 'transactions.paymentType:id,name'])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->first();

        if (!$purchase) {
            return response()->json([
                'message' => __('Purchase not found.'),
            ], 404);
        }

        return response()->json([
            'message' => __('Purchase fetched successfully.'),
            'data' => $purchase,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'party_id' => 'required|exists:parties,id'
        ]);

        DB::beginTransaction();
        try {

            $business_id = auth()->user()->business_id;

            // Party due update
            if ($request->dueAmount > 0) {
                $party = Party::findOrFail($request->party_id);

                // Check party credit limit
                $newTotalDue = $party->due + $request->dueAmount;
                if ($party->credit_limit > 0 && $newTotalDue > $party->credit_limit) {
                    return response()->json([
                        'message' => __('Cannot create purchase. Party due will exceed credit limit!')
                    ], 400);
                }

                $party->update([
                    'due' => $newTotalDue
                ]);
            }

            updateBalance($request->paidAmount, 'decrement');

            $purchase = Purchase::create($request->all() + [
                    'user_id' => auth()->id(),
                    'business_id' => $business_id,
                ]);

            $purchaseDetails = [];
            foreach ($request->products as $key => $product_data) {

                $batch_no = $product_data['batch_no'] ?? NULL;
                $variantName = $product_data['variant_name'] ?? null;
                $existingStock = Stock::where(['batch_no' => $batch_no, 'product_id' => $product_data['product_id']])
                    ->when($variantName, fn($q) => $q->where('variant_name', $variantName))
                    ->first();

                // update or create stock
                $stock = Stock::updateOrCreate(
                    [
                        'batch_no' => $batch_no,
                        'business_id' => $business_id,
                        'product_id' => $product_data['product_id'],
                        'variant_name' => $variantName
                    ],
                    [
                        'product_id' => $product_data['product_id'],
                        'mfg_date' => $product_data['mfg_date'] ?? NULL,
                        'expire_date' => $product_data['expire_date'] ?? NULL,
                        'profit_percent' => $product_data['profit_percent'] ?? 0,
                        'productSalePrice' => $product_data['productSalePrice'] ?? 0,
                        'productDealerPrice' => $product_data['productDealerPrice'] ?? 0,
                        'productPurchasePrice' => $product_data['productPurchasePrice'] ?? 0,
                        'productWholeSalePrice' => $product_data['productWholeSalePrice'] ?? 0,
                        'productStock' => ($product_data['quantities'] ?? 0) + ($existingStock->productStock ?? 0),
                        'warehouse_id' => $product_data['warehouse_id'] ?? null,
                    ]
                );

                $purchaseDetails[$key] = [
                    'stock_id' => $stock->id,
                    'purchase_id' => $purchase->id,
                    'product_id' => $product_data['product_id'],
                    'quantities' => $product_data['quantities'] ?? 0,
                    'productSalePrice' => $product_data['productSalePrice'] ?? 0,
                    'productDealerPrice' => $product_data['productDealerPrice'] ?? 0,
                    'productPurchasePrice' => $product_data['productPurchasePrice'] ?? 0,
                    'productWholeSalePrice' => $product_data['productWholeSalePrice'] ?? 0,
                    'profit_percent' => $product_data['profit_percent'] ?? 0,
                    'expire_date' => $product_data['expire_date'] ?? NULL,
                    'mfg_date' => $product_data['mfg_date'] ?? NULL,
                ];
            }

            PurchaseDetails::insert($purchaseDetails);

            // MultiPaymentProcessed Event
            event(new MultiPaymentProcessed(
                $request->payments ?? [],
                $purchase->id,
                'purchase',
                $request->paidAmount ?? 0,
                $request->party_id ?? null,
            ));

            // Send SMS
            event(new PurchaseSms($purchase));

            DB::commit();
            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $purchase->load('user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no', 'details.product:id,productName,category_id,product_type', 'details.product.category:id,categoryName', 'purchaseReturns.details', 'vat:id,name,rate', 'payment_type:id,name', 'branch:id,name,phone,address'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'party_id' => 'required|exists:parties,id'
        ]);

        DB::beginTransaction();
        try {

            $has_return = PurchaseReturn::where('purchase_id', $purchase->id)->count();

            if ($has_return > 0) {
                return response()->json([
                    'message' => __("You can not update this purchase because it has already been returned.")
                ], 400);
            }

            // Revert previous stock changes
            foreach ($purchase->details as $detail) {
                Stock::where('id', $detail->stock_id)->decrement('productStock', $detail->quantities);
            }

            // Delete existing purchase details
            $purchase->details()->delete();

            $business_id = auth()->user()->business_id;

            $purchaseDetails = [];
            foreach ($request->products as $key => $product_data) {

                $batch_no = $product_data['batch_no'] ?? NULL;
                $variantName = $cartItem->options['variant_name'] ?? null;
                $existingStock = Stock::where(['batch_no' => $batch_no, 'product_id' => $product_data['product_id']])
                    ->when($variantName, fn($q) => $q->where('variant_name', $variantName))
                    ->first();

                // update or create stock
                $stock = Stock::updateOrCreate(
                    [
                        'batch_no' => $batch_no,
                        'business_id' => $business_id,
                        'product_id' => $product_data['product_id'],
                        'variant_name' => $variantName
                    ],
                    [
                        'product_id' => $product_data['product_id'],
                        'mfg_date' => $product_data['mfg_date'] ?? NULL,
                        'expire_date' => $product_data['expire_date'] ?? NULL,
                        'profit_percent' => $product_data['profit_percent'] ?? 0,
                        'productSalePrice' => $product_data['productSalePrice'] ?? 0,
                        'productDealerPrice' => $product_data['productDealerPrice'] ?? 0,
                        'productPurchasePrice' => $product_data['productPurchasePrice'] ?? 0,
                        'productWholeSalePrice' => $product_data['productWholeSalePrice'] ?? 0,
                        'productStock' => ($product_data['quantities'] ?? 0) + ($existingStock->productStock ?? 0),
                        'warehouse_id' => $product_data['warehouse_id'] ?? null,
                    ]
                );

                $purchaseDetails[$key] = [
                    'stock_id' => $stock->id,
                    'purchase_id' => $purchase->id,
                    'product_id' => $product_data['product_id'],
                    'quantities' => $product_data['quantities'] ?? 0,
                    'productSalePrice' => $product_data['productSalePrice'] ?? 0,
                    'productDealerPrice' => $product_data['productDealerPrice'] ?? 0,
                    'productPurchasePrice' => $product_data['productPurchasePrice'] ?? 0,
                    'productWholeSalePrice' => $product_data['productWholeSalePrice'] ?? 0,
                    'profit_percent' => $product_data['profit_percent'] ?? 0,
                    'expire_date' => $product_data['expire_date'] ?? NULL,
                    'mfg_date' => $product_data['mfg_date'] ?? NULL,
                ];
            }

            PurchaseDetails::insert($purchaseDetails);

            if ($purchase->dueAmount || $request->dueAmount) {
                $party = Party::findOrFail($request->party_id);

                // Calculate new due for this party
                $newDue = $request->party_id == $purchase->party_id ? (($party->due - $purchase->dueAmount) + $request->dueAmount) : ($party->due + $request->dueAmount);

                // Check credit limit
                if ($party->credit_limit > 0 && $newDue > $party->credit_limit) {
                    return response()->json([
                        'message' => __('Cannot update purchase. Party due will exceed credit limit!')
                    ], 400);
                }

                $party->update([
                    'due' => $newDue
                ]);

                // If changed to a new party, reduce previous party’s due
                if ($request->party_id != $purchase->party_id) {
                    $prev_party = Party::findOrFail($purchase->party_id);
                    $prev_party->update([
                        'due' => $prev_party->due - $purchase->dueAmount
                    ]);
                }
            }

            $balanceDiff = ($purchase->paidAmount ?? 0) - $request->paidAmount;
            updateBalance($balanceDiff, 'decrement');

            $purchase->update($request->all() + [
                    'user_id' => auth()->id(),
                ]);

            // Multiple Payment Process
            $oldTransactions = Transaction::where('business_id', $business_id)
                ->where('reference_id', $purchase->id)
                ->where('platform', 'purchase')
                ->get();

            // Revert old transactions
            foreach ($oldTransactions as $old) {
                switch ($old->transaction_type) {
                    case 'bank_payment':
                        $paymentType = PaymentType::find($old->payment_type_id);
                        if ($paymentType) {
                            $paymentType->increment('balance', $old->amount);
                        }
                        break;
                    case 'wallet_payment':
                        if ($request->party_id) {
                            $party = Party::find($request->party_id);
                            if ($party) {
                                $party->decrement('wallet', $old->amount);
                            }
                        }
                        break;
                    case 'cash_payment':
                    case 'cheque_payment':
                        // nothing to revert
                        break;
                }
            }

            // Delete old transactions
            Transaction::where('business_id', $business_id)
                ->where('reference_id', $purchase->id)
                ->where('platform', 'purchase')
                ->delete();

            event(new MultiPaymentProcessed(
                $request->payments ?? [],
                $purchase->id,
                'purchase',
                    $request->paidAmount ?? 0,
                $request->party_id ?? null,
            ));

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $purchase->load('user:id,name,role', 'party:id,name,email,phone,type,address', 'details', 'details.stock:id,batch_no', 'details.product:id,productName,category_id,product_type', 'details.product.category:id,categoryName', 'purchaseReturns.details', 'vat:id,name,rate', 'payment_type:id,name'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $purchase = Purchase::with('details')->findOrFail($id);

            $has_return = PurchaseReturn::where('purchase_id', $purchase->id)->count();

            if ($has_return > 0) {
                return response()->json([
                    'message' => __("You can not update this purchase because it has already been returned.")
                ], 400);
            }

            if ($purchase->dueAmount) {
                $party = Party::findOrFail($purchase->party_id);
                $party->update([
                    'due' => $party->due - $purchase->dueAmount
                ]);
            }

            foreach ($purchase->details as $detail) {
                Stock::where('id', $detail->stock_id)->decrement('productStock', $detail->quantities);
            }

            updateBalance($purchase->paidAmount, 'increment');

            sendNotifyToUser($purchase->id, route('business.purchases.index', ['id' => $purchase->id]), __('Purchase has been deleted.'), $purchase->business_id);

            $purchase->delete();

            DB::commit();
            return response()->json([
                'message' => __('Data deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
