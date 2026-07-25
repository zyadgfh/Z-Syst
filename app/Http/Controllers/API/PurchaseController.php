<?php

namespace App\Http\Controllers\Api;

use App\Models\Party;
use App\Models\Stock;
use App\Models\Product;
use App\Models\Business;
use App\Models\Purchase;
use App\Services\Stock\StockAllocationService;
use Illuminate\Http\Request;
use App\Models\PurchaseDetails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Purchase::select('id', 'party_id', 'invoiceNumber', 'purchaseDate', 'totalAmount', 'dueAmount', 'paidAmount', 'paymentType', 'note')
                ->with('party:id,name,phone')
                ->when(request('search'), function ($query) {
                    $query->where(function ($subQuery) {
                        $subQuery->where('paymentType', 'like', '%' . request('search') . '%')
                            ->orWhere('invoiceNumber', 'like', '%' . request('search') . '%')
                            ->orWhere('note', 'like', '%' . request('search') . '%')
                            ->orWhereHas('party', function ($query) {
                                $query->where('name', 'like', '%' . request('search') . '%')
                                    ->orWhere('phone', 'like', '%' . request('search') . '%');
                            });
                    });
                })
                ->withCount('purchaseReturns')
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

        DB::beginTransaction();
        try {

            $business_id = auth()->user()->business_id;

            if ($request->dueAmount) {
                $party = Party::findOrFail($request->party_id);
                $party->update([
                    'due' => $party->due + $request->dueAmount
                ]);
            }

            $business = Business::findOrFail($business_id);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance - $request->paidAmount
            ]);

            $purchase = Purchase::create($request->all() + [
                            'user_id' => auth()->id(),
                            'business_id' => $business_id,
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
                $product = Product::findOrFail($item['product_id']);
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                // Use StockAllocationService with pessimistic locking
                StockAllocationService::addToLegacyStock(
                    $product->id,
                    (int) $item['quantities'],
                    $item['batch_no'],
                    $item['expire_date'],
                    $business_id
                );
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $purchase->load([
                                'tax:id,name,rate',
                                'party:id,name,phone',
                                'details.product:id,productName',
                                'details:id,purchase_id,product_id,purchase_with_tax,quantities',
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
        $data = Purchase::with([
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

        DB::beginTransaction();
        try {

            $business_id = auth()->user()->business_id;

            // Reverse old stock first using StockAllocationService
            $prev_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();
            foreach ($prev_details as $prev_detail) {
                StockAllocationService::allocate(
                    $prev_detail->product_id,
                    (int) $prev_detail->quantities
                );
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
                $product = Product::findOrFail($item['product_id']);
                $product->update([
                    'sales_price' => $item['sales_price'],
                    'profit_percent' => $item['profit_percent'],
                    'wholesale_price' => $item['wholesale_price'],
                    'purchase_with_tax' => $item['purchase_with_tax'],
                    'purchase_without_tax' => $item['purchase_without_tax'],
                ]);

                // Add new stock using StockAllocationService
                StockAllocationService::addToLegacyStock(
                    $product->id,
                    (int) $item['quantities'],
                    $item['batch_no'],
                    $item['expire_date'],
                    $business_id
                );
            }

            if ($purchase->dueAmount || $request->dueAmount) {
                $party = Party::findOrFail($request->party_id);
                $party->update([
                    'due' => $request->party_id == $purchase->party_id ? (($party->due - $purchase->dueAmount) + $request->dueAmount) : ($party->due + $request->dueAmount)
                ]);

                if ($request->party_id != $purchase->party_id) {
                    $prev_party = Party::findOrFail($purchase->party_id);
                    $prev_party->update([
                        'due' => $prev_party->due - $purchase->dueAmount
                    ]);
                }
            }

            $business = Business::findOrFail(auth()->user()->business_id);
            $business->update([
                'remainingShopBalance' => ($business->remainingShopBalance + $purchase->paidAmount) - $request->paidAmount
            ]);

            $purchase->update($request->all() + [
                'user_id' => auth()->id(),
            ]);

            $empty_qty_items = array_filter($purchaseDetails, function ($item) {
                return $item['quantities'] > 0;
            });

            PurchaseDetails::where('purchase_id', $purchase->id)->delete();
            PurchaseDetails::insert($empty_qty_items);

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

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();
        try {

            $purchase_details = PurchaseDetails::where('purchase_id', $purchase->id)->get();

            // Reverse stock using StockAllocationService with pessimistic locking
            foreach ($purchase_details as $purchase_detail) {
                StockAllocationService::allocate(
                    $purchase_detail->product_id,
                    (int) $purchase_detail->quantities
                );
            }

            if ($purchase->dueAmount) {
                $party = Party::findOrFail($purchase->party_id);
                $party->update([
                    'due' => $party->due - $purchase->dueAmount
                ]);
            }

            $business = Business::findOrFail(auth()->user()->business_id);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance + $purchase->paidAmount
            ]);

            $purchase->delete();
            DB::commit();

            return response()->json([
                'message' => __('Data deleted successfully.'),
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => __('Something was wrong.'),
            ], 406);
        }
    }
}
