<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Helpers\TransactionHelper;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Party;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Stock;
use App\Models\Tax;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
            ->with('party:id,name,phone')
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
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
            'purchaseDate' => 'required|string',
            'party_id' => 'required|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'paidAmount' => 'nullable|numeric',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.purchase_without_tax' => 'required|numeric',
            'products.*.purchase_with_tax' => 'required|numeric',
            'products.*.profit_percent' => 'required|numeric',
            'products.*.sales_price' => 'required|numeric',
            'products.*.wholesale_price' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.expire_date' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
        ]);

        $purchase = TransactionHelper::run(function () use ($request) {
            $business_id = (int) auth()->user()->business_id;

            $party = Party::where('business_id', $business_id)->findOrFail($request->party_id);
            $productIds = collect($request->products)->pluck('product_id')->filter()->unique()->values();
            $ownedProductIds = Product::where('business_id', $business_id)
                ->whereIn('id', $productIds)
                ->pluck('id');

            if ($ownedProductIds->count() !== $productIds->count()) {
                abort(403, 'One or more products do not belong to the current tenant.');
            }

            if ($request->tax_id) {
                Tax::where('business_id', $business_id)->findOrFail($request->tax_id);
            }

            if ($request->dueAmount) {
                $party->update([
                    'due' => $party->due + $request->dueAmount,
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance - $request->paidAmount,
            ]);

            $purchase = Purchase::create([
                'party_id' => $request->party_id,
                'user_id' => auth()->id(),
                'business_id' => $business_id,
                'tax_id' => $request->tax_id,
                'discountAmount' => $request->discountAmount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'dueAmount' => $request->dueAmount ?? 0,
                'paidAmount' => $request->paidAmount ?? 0,
                'totalAmount' => $request->totalAmount ?? 0,
                'isPaid' => $request->isPaid ?? false,
                'paymentType' => $request->paymentType,
                'purchaseDate' => $request->purchaseDate,
                'note' => $request->note,
            ]);

            $purchaseDetails = [];
            foreach ($request->products as $key => $product_data) {
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

            PurchaseDetails::insert($purchaseDetails);

            foreach ($purchaseDetails as $item) {
                $product = Product::where('business_id', $business_id)->findOrFail($item['product_id']);
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                if ($item['batch_no']) {
                    $stock = Stock::where('business_id', $business_id)
                        ->where('product_id', $product->id)
                        ->where('batch_no', $item['batch_no'])
                        ->first();
                } else {
                    $stock = Stock::where('business_id', $business_id)
                        ->where('product_id', $product->id)
                        ->first();
                }

                if ($stock ?? false) {
                    $stock->update([
                        'batch_no' => $item['batch_no'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => $stock->productStock + $item['quantities'],
                    ]);
                } else {
                    Stock::create([
                        'business_id' => $business_id,
                        'batch_no' => $item['batch_no'],
                        'product_id' => $item['product_id'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => $item['quantities'],
                    ]);
                }
            }

            return $purchase->load([
                'tax:id,name,rate',
                'party:id,name,phone',
                'details.product:id,productName',
                'details:id,purchase_id,product_id,purchase_with_tax,quantities',
            ]);
        }, 'purchase:store', [
            'party_id' => $request->party_id,
            'products_count' => count($request->products ?? []),
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $purchase,
        ]);
    }

    public function show($id)
    {
        $data = Purchase::where('business_id', (int) auth()->user()->business_id)
            ->with([
                'tax',
                'user:id,name',
                'party:id,name,phone',
                'purchaseReturns.details',
                'details.product:id,productName,tax_type',
                'details:id,purchase_id,product_id,purchase_with_tax,quantities,batch_no,purchase_without_tax,profit_percent,sales_price,wholesale_price',
            ])
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'products' => 'required|array',
            'purchaseDate' => 'required|string',
            'party_id' => 'required|exists:parties,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'discountAmount' => 'nullable|numeric',
            'tax_amount' => 'nullable|numeric',
            'totalAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'paidAmount' => 'nullable|numeric',
            'isPaid' => 'nullable|boolean',
            'paymentType' => 'nullable|string',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.purchase_without_tax' => 'required|numeric',
            'products.*.purchase_with_tax' => 'required|numeric',
            'products.*.profit_percent' => 'required|numeric',
            'products.*.sales_price' => 'required|numeric',
            'products.*.wholesale_price' => 'required|numeric',
            'products.*.batch_no' => 'nullable|string',
            'products.*.expire_date' => 'nullable|string',
            'products.*.quantities' => 'required|integer',
        ]);

        TransactionHelper::run(function () use ($request, $purchase) {
            $business_id = auth()->user()->business_id;

            $batch_numbers = collect($request->products)->pluck('batch_no');
            $prev_stocks = Stock::where('business_id', $business_id)
                ->whereIn('batch_no', $batch_numbers)
                ->get();

            $productIds = collect($request->products)->pluck('product_id')->filter()->unique();
            if (Product::where('business_id', $business_id)->whereIn('id', $productIds)->count() !== $productIds->count()) {
                abort(403, 'One or more products do not belong to the current tenant.');
            }
            $prev_purchase_details = PurchaseDetails::whereIn('batch_no', $batch_numbers)->get();

            // Validate stock/batch quantity matches
            foreach ($request->products as $req_item) {
                $prev_stock = $prev_stocks->where('batch_no', $req_item['batch_no'])->first();
                $prev_purchase_detail = $prev_purchase_details->where('batch_no', $req_item['batch_no'])->first();

                if (! empty($prev_purchase_detail) && $prev_purchase_detail->quantities > $req_item['quantities']) {
                    if ($prev_stock && $prev_stock->productStock < $req_item['quantities']) {
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

            $prev_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            foreach ($prev_details as $prev_detail) {
                Stock::where('business_id', $business_id)
                    ->where('batch_no', $prev_detail->batch_no)
                    ->decrement('productStock', $prev_detail->quantities);
            }

            $purchaseDetails = [];
            foreach ($request->products as $key => $product_data) {
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

            foreach ($purchaseDetails as $item) {
                $product = Product::where('business_id', $business_id)->findOrFail($item['product_id']);
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                if ($item['batch_no']) {
                    $stock = Stock::where('product_id', $product->id)->where('batch_no', $item['batch_no'])->first();
                } else {
                    $stock = Stock::where('product_id', $product->id)->first();
                }

                if ($stock ?? false) {
                    $stock->update([
                        'batch_no' => $item['batch_no'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => $stock->productStock + $item['quantities'],
                    ]);
                } else {
                    Stock::create($request->all() + [
                        'business_id' => $business_id,
                        'batch_no' => $item['batch_no'],
                        'product_id' => $item['product_id'],
                        'expire_date' => $item['expire_date'],
                        'productStock' => $item['quantities'],
                    ]);
                }
            }

            if ($purchase->dueAmount || $request->dueAmount) {
                $party = Party::where('business_id', $business_id)->findOrFail($request->party_id);
                $party->update([
                    'due' => $request->party_id == $purchase->party_id ? (($party->due - $purchase->dueAmount) + $request->dueAmount) : ($party->due + $request->dueAmount),
                ]);

                if ($request->party_id != $purchase->party_id) {
                    $prev_party = Party::where('business_id', $business_id)->findOrFail($purchase->party_id);
                    $prev_party->update([
                        'due' => $prev_party->due - $purchase->dueAmount,
                    ]);
                }
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'remainingShopBalance' => ($business->remainingShopBalance + $purchase->paidAmount) - $request->paidAmount,
            ]);

            $purchase->update([
                'party_id' => $request->party_id,
                'user_id' => auth()->id(),
                'tax_id' => $request->tax_id,
                'discountAmount' => $request->discountAmount ?? 0,
                'tax_amount' => $request->tax_amount ?? 0,
                'dueAmount' => $request->dueAmount ?? 0,
                'paidAmount' => $request->paidAmount ?? 0,
                'totalAmount' => $request->totalAmount ?? 0,
                'isPaid' => $request->isPaid ?? false,
                'paymentType' => $request->paymentType,
                'purchaseDate' => $request->purchaseDate,
                'note' => $request->note,
            ]);

            $empty_qty_items = array_filter($purchaseDetails, function ($item) {
                return $item['quantities'] > 0;
            });

            PurchaseDetails::where('purchase_id', $purchase->id)->delete();
            PurchaseDetails::insert($empty_qty_items);
        }, 'purchase:update', ['purchase_id' => $purchase->id]);

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        TransactionHelper::run(function () use ($purchase) {
            $business_id = (int) auth()->user()->business_id;
            $purchase_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            $prev_stocks = Stock::where('business_id', $business_id)
                ->whereIn('batch_no', $purchase_details->pluck('batch_no'))
                ->get();

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
            }

            if ($purchase->dueAmount) {
                $party = Party::where('business_id', $business_id)->findOrFail($purchase->party_id);
                $party->update([
                    'due' => $party->due - $purchase->dueAmount,
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $purchase->paidAmount,
            ]);

            $purchase->delete();
        }, 'purchase:destroy', ['purchase_id' => $purchase->id]);

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
