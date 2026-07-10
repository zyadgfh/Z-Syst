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

class AcnooSaleController extends Controller
{
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

        DB::beginTransaction();
        try {

            $business_id = auth()->user()->business_id;

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
