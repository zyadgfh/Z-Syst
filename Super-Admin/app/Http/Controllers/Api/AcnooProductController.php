<?php

namespace App\Http\Controllers\Api;

use App\Models\ComboProduct;
use App\Models\Stock;
use App\Models\Product;
use App\Helpers\HasUploader;
use App\Models\Vat;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class AcnooProductController extends Controller
{
    use HasUploader;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $products = Product::with([
                        'unit:id,unitName',
                        'vat:id,rate',
                        'brand:id,brandName',
                        'category:id,categoryName',
                        'product_model:id,name',
                        'stocks',
                        'combo_products.stock.product' => function ($query) {
                            $query->select('id', 'productName', 'productCode');
                        },
                        'rack:id,name',
                        'shelf:id,name'
                    ])
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('product_type', '!=', 'combo')
                                ->whereHas('stocks');
                        })
                        ->orWhere(function ($q) {
                            $q->where('product_type', 'combo')
                                ->whereHas('combo_products');
                        });
                    })
                    ->withSum('saleDetails', 'quantities')
                    ->withSum('purchaseDetails', 'quantities')
                    ->withSum('stocks', 'productStock')
                    ->where('business_id', $user->business_id)
                    ->latest()
                    ->get();

        $total_stock_value = $products->sum(function ($product) {
            return $product->stocks->sum(function ($stock) {
                return $stock->productPurchasePrice * $stock->productStock;
            });
        });

        $products = $products->map(function ($product) {
        $product->total_sale_amount = $product->saleDetails->sum(function ($sale) {
            return ($sale->price - $sale->discount) * $sale->quantities;
        });

        $product->total_profit_loss = $product->saleDetails->sum(function ($sale) {
            // If lossProfit column exists, use it
                if (!is_null($sale->lossProfit)) {
                    return $sale->lossProfit;
                }
                // Otherwise calculate: (price - discount - purchase price) * quantity
                return (($sale->price - $sale->discount) - $sale->productPurchasePrice) * $sale->quantities;
            });

            return $product;
        });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'total_stock_value' => $total_stock_value,
            'data' => $products,
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->stocks)) {
            $request->merge(['stocks' => json_decode($request->stocks, true)]);
        }

        if (is_string($request->warranty_guarantee_info)) {
            $request->merge(['warranty_guarantee_info' => json_decode($request->warranty_guarantee_info, true)]);
        }

        if (is_string($request->combo_products)) {
            $request->merge(['combo_products' => json_decode($request->combo_products, true)]);
        }

        if (is_string($request->variation_ids)) {
            $decoded = json_decode($request->variation_ids, true);
            $request->merge(['variation_ids' => $decoded]);
        }

        $business_id = auth()->user()->business_id;

        $request->validate([
            'vat_id' => 'nullable|exists:vats,id',
            'unit_id' => 'nullable|exists:units,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'model_id' => 'nullable|exists:product_models,id',
            'vat_type' => 'nullable|in:inclusive,exclusive',
            'productName' => 'required|string|max:255',
            'productPicture' => 'nullable|image|mimes:jpg,png,jpeg,svg',
            'productCode' => [
                'nullable',
                Rule::unique('products')->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
            'alert_qty' => 'nullable|numeric|min:0',
            'size' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'productManufacturer' => 'nullable|string|max:255',
            'product_type' => 'required|in:single,variant,combo',
            'variation_ids' => 'nullable|array',
            'variation_ids.*' => 'exists:variations,id',

            'stocks.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'stocks.*.productStock' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.exclusive_price' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.inclusive_price' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.profit_percent' => 'nullable|numeric|max:99999999.99',
            'stocks.*.productSalePrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.productWholeSalePrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.productDealerPrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.mfg_date' => 'nullable|date',
            'stocks.*.expire_date' => 'nullable|date|after_or_equal:stocks.*.mfg_date',
            'stocks' => 'nullable|array',
            'stocks.*.batch_no' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    $batchNos = collect($request->stocks)->pluck('batch_no')->filter()->toArray();

                    if (count($batchNos) !== count(array_unique($batchNos))) {
                        $fail('Duplicate batch number found in the request.');
                    }
                },
            ],

            // Combo validation
            'combo_products' => 'nullable|array',
            'combo_products.*.stock_id' => [
                'required_if:product_type,combo',
                Rule::exists('stocks', 'id')->where('business_id', $business_id),
            ],
            'combo_products.*.quantity' => 'required_if:product_type,combo|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // vat calculation
            $vat = Vat::find($request->vat_id);
            $vat_rate = $vat->rate ?? 0;

            // Create the product
            $product = Product::create($request->only('productName', 'unit_id', 'brand_id', 'vat_id', 'vat_type', 'category_id', 'productCode', 'product_type', 'rack_id', 'shelf_id', 'model_id', 'variation_ids', 'warranty_guarantee_info') + [
                'business_id' => $business_id,
                'alert_qty' => $request->alert_qty ?? 0,
                'profit_percent' => $request->product_type == 'combo' ? $request->profit_percent ?? 0 : 0,
                'productSalePrice' => $request->product_type == 'combo' ? $request->productSalePrice ?? 0 : 0,
                'productPicture' => $request->productPicture ? $this->upload($request, 'productPicture') : NULL,
            ]);

            // Single or Variant Product
            if (in_array($request->product_type, ['single', 'variant']) && !empty($request->stocks)) {
                $stockData = [];
                foreach ($request->stocks as $stock) {

                    $base_price = $stock['exclusive_price'] ?? 0;
                    $purchasePrice = $request->vat_type === 'inclusive'
                        ? $base_price + ($base_price * $vat_rate / 100)
                        : $base_price;

                    $stockData[] = [
                        'business_id' => $business_id,
                        'product_id' => $product->id,
                        'batch_no' => $stock['batch_no'] ?? null,
                        'warehouse_id' => $stock['warehouse_id'] ?? null,
                        'productStock' => $stock['productStock'] ?? 0,
                        'productPurchasePrice' => $purchasePrice,
                        'profit_percent' => $stock['profit_percent'] ?? 0,
                        'productSalePrice' => $stock['productSalePrice'] ?? 0,
                        'productWholeSalePrice' => $stock['productWholeSalePrice'] ?? 0,
                        'productDealerPrice' => $stock['productDealerPrice'] ?? 0,
                        'mfg_date' => $stock['mfg_date'] ?? null,
                        'expire_date' => $stock['expire_date'] ?? null,
                        'variation_data' => isset($stock['variation_data']) ? json_encode($stock['variation_data']) : null,
                        'variant_name' => $stock['variant_name'] ?? null,
                        'serial_numbers' => $stock['serial_numbers'] ?? null,
                        'branch_id' => auth()->user()->branch_id ?? auth()->user()->active_branch_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                Stock::insert($stockData);
            }

            // Combo Product
            if ($request->product_type === 'combo' && !empty($request->combo_products)) {
                foreach ($request->combo_products as $item) {
                    ComboProduct::create([
                        'product_id' => $product->id,
                        'stock_id' => $item['stock_id'],
                        'quantity' => $item['quantity'],
                        'purchase_price' => $item['purchase_price'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $product
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage(),
            ], 406);
        }
    }

    public function show(string $id)
    {
        $data = Product::with([
            'unit:id,unitName',
            'vat:id,rate',
            'brand:id,brandName',
            'category:id,categoryName',
            'product_model:id,name',
            'stocks',
            'stocks.warehouse:id,name',
            'combo_products.stock.product' => function ($query) {
                $query->select('id', 'productName', 'productCode');
            },
            'rack:id,name',
            'shelf:id,name'
        ])
            ->withSum('saleDetails', 'quantities')
            ->withSum('purchaseDetails', 'quantities')
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);

            $data->total_sale_amount = $data->saleDetails->sum(function ($sale) {
                return ($sale->price - $sale->discount) * $sale->quantities;
            });

            $data->total_profit_loss = $data->saleDetails->sum(function ($sale) {
                if (!is_null($sale->lossProfit)) {
                    return $sale->lossProfit;
                }
                return (($sale->price - $sale->discount) - $sale->productPurchasePrice) * $sale->quantities;
            });

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $business_id = auth()->user()->business_id;

        if ($product->product_type != $request->product_type) {
            return response()->json([
                'message' => __('Product type can not be changed.'),
            ], 406);
        }

        if (is_string($request->stocks)) {
            $request->merge(['stocks' => json_decode($request->stocks, true)]);
        }

        if (is_string($request->warranty_guarantee_info)) {
            $request->merge(['warranty_guarantee_info' => json_decode($request->warranty_guarantee_info, true)]);
        }

        if (is_string($request->combo_products)) {
            $request->merge(['combo_products' => json_decode($request->combo_products, true)]);
        }

        if (is_string($request->variation_ids)) {
            $request->merge(['variation_ids' => json_decode($request->variation_ids, true)]);
        }

        $request->validate([
            'vat_id' => 'nullable|exists:vats,id',
            'unit_id' => 'nullable|exists:units,id',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'model_id' => 'nullable|exists:product_models,id',
            'vat_type' => 'nullable|in:inclusive,exclusive',
            'productName' => 'required|string|max:255',
            'productPicture' => 'nullable|image|mimes:jpg,png,jpeg,svg',
            'productCode' => [
                'nullable',
                Rule::unique('products', 'productCode')->ignore($product->id)->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
            'alert_qty' => 'nullable|numeric|min:0',
            'size' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'weight' => 'nullable|string|max:255',
            'capacity' => 'nullable|string|max:255',
            'productManufacturer' => 'nullable|string|max:255',
            'product_type' => 'required|in:single,variant,combo',
            'variation_ids' => 'nullable|array',
            'variation_ids.*' => 'exists:variations,id',

            'stocks.*.warehouse_id' => 'nullable|exists:warehouses,id',
            'stocks.*.productStock' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.exclusive_price' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.inclusive_price' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.profit_percent' => 'nullable|numeric|max:99999999.99',
            'stocks.*.productSalePrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.productWholeSalePrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.productDealerPrice' => 'nullable|numeric|min:0|max:99999999.99',
            'stocks.*.mfg_date' => 'nullable|date',
            'stocks.*.expire_date' => 'nullable|date|after_or_equal:stocks.*.mfg_date',
            'stocks' => 'nullable|array',
            'stocks.*.batch_no' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    $batchNos = collect($request->stocks)->pluck('batch_no')->filter()->toArray();

                    if (count($batchNos) !== count(array_unique($batchNos))) {
                        $fail('Duplicate batch number found in the request.');
                    }
                },
            ],

            // Combo validation
            'combo_products' => 'nullable|array',
            'combo_products.*.stock_id' => [
                'required_if:product_type,combo',
                Rule::exists('stocks', 'id')->where('business_id', $business_id),
            ],
            'combo_products.*.quantity' => 'required_if:product_type,combo|numeric|min:1',
        ]);

        DB::beginTransaction();
        try {
            // VAT calculation
            $vat = Vat::find($request->vat_id);
            $vat_rate = $vat->rate ?? 0;

            // Update product
            $product->update($request->except(['productPicture', 'productPurchasePrice', 'productDealerPrice', 'productWholeSalePrice', 'alert_qty', 'stocks', 'vat_amount', 'profit_percent']) + [
                'business_id' => $business_id,
                'alert_qty' => $request->alert_qty ?? 0,
                'profit_percent' => $request->profit_percent ?? 0,
                'productPicture' => $request->productPicture ? $this->upload($request, 'productPicture', $product->productPicture) : $product->productPicture,
            ]);

            // Delete previous stocks and combos
            if ($product->product_type === 'combo') {
                ComboProduct::where('product_id', $product->id)->delete();
            }

            // Handle Single/Variant Product Stocks
            if (in_array($request->product_type, ['single', 'variant']) && !empty($request->stocks)) {
                $existingStockIds = $product->stocks()->pluck('id')->toArray();
                $incomingStockIds = collect($request->stocks)->pluck('stock_id')->filter()->toArray();

                // Delete removed stocks
                $stocksToDelete = array_diff($existingStockIds, $incomingStockIds);
                Stock::whereIn('id', $stocksToDelete)->delete();

                // Insert or Update
                foreach ($request->stocks as $stock) {
                    // Decode variation_data if sent as string
                    if (isset($stock['variation_data']) && is_string($stock['variation_data'])) {
                        $stock['variation_data'] = json_decode($stock['variation_data'], true);
                    }

                    $stockId = $stock['stock_id'] ?? null;

                    // Recalculate price
                    $base_price = $stock['exclusive_price'] ?? 0;
                    $purchasePrice = $request->vat_type === 'inclusive'
                        ? $base_price + ($base_price * $vat_rate / 100)
                        : $base_price;

                    $payload = [
                        'business_id' => $business_id,
                        'product_id' => $product->id,
                        'batch_no' => $stock['batch_no'] ?? null,
                        'warehouse_id' => $stock['warehouse_id'] ?? null,
                        'productStock' => $stock['productStock'] ?? 0,
                        'productPurchasePrice' => $purchasePrice,
                        'profit_percent' => $stock['profit_percent'] ?? 0,
                        'productSalePrice' => $stock['productSalePrice'] ?? 0,
                        'productWholeSalePrice' => $stock['productWholeSalePrice'] ?? 0,
                        'productDealerPrice' => $stock['productDealerPrice'] ?? 0,
                        'mfg_date' => $stock['mfg_date'] ?? null,
                        'expire_date' => $stock['expire_date'] ?? null,
                        'variation_data' => $stock['variation_data'] ?? null,
                        'variant_name' => $stock['variant_name'] ?? null,
                        'branch_id' => auth()->user()->branch_id ?? auth()->user()->active_branch_id,
                        'serial_numbers' => $request->has_serial ? $stock['serial_numbers'] : null,
                    ];

                    if ($stockId) {
                        Stock::where('id', $stockId)->update($payload);
                    } else {
                        Stock::create($payload);
                    }
                }
            }

            // Handle Combo Product
            if ($request->product_type === 'combo' && !empty($request->combo_products)) {
                foreach ($request->combo_products as $item) {
                    ComboProduct::create([
                        'product_id' => $product->id,
                        'stock_id' => $item['stock_id'],
                        'quantity' => $item['quantity'],
                        'purchase_price' => $item['purchase_price'] ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $product->load('stocks'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'message' => $e->getMessage()
            ], 406);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        if (file_exists($product->productPicture)) {
            Storage::delete($product->productPicture);
        }
        $product->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }


    public function generateCode()
    {
        $product_id = (Product::where('business_id', auth()->user()->business_id)->count() ?? 0) + 1;
        $code = str_pad($product_id, 4, '0', STR_PAD_LEFT);

        return response()->json([
            'data' => $code,
        ]);
    }
}
