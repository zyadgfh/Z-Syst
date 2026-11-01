<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ZSystProductController extends Controller
{
    use HasUploader;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $data = Product::select('id', 'productName', 'productCode', 'purchase_with_tax', 'sales_price')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where('productName', 'like', $term)
                    ->orWhere('productCode', 'like', $term);
            })
            ->when($request->filled('expire_date'), function ($query) use ($request) {
                $query->whereHas('stocks', function ($query) use ($request) {
                    $query->whereBetween('expire_date', [today(), $request->input('expire_date')]);
                });
            })
            ->when($request->input('expired') == 'true', function ($query) {
                $query->whereHas('stocks', function ($query) {
                    $query->where('expire_date', '<', today())
                        ->where('productStock', '>', 0);
                });
            })
            ->withSum('stocks', 'productStock')
            ->with(['expiring_item' => function ($query) {
                $query->select('expire_date', 'product_id')
                    ->where('productStock', '>', 0)
                    ->whereNotNull('expire_date');
            }])
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
        $business_id = auth()->user()->business_id;

        $request->validate([
            'productName' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'productCode' => [
                'nullable',
                Rule::unique('products')->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
            'batch_no' => [
                'nullable',
                Rule::unique('stocks')->where(function ($query) use ($business_id) {
                    return $query->where('business_id', $business_id);
                }),
            ],
        ]);

        DB::beginTransaction();
        try {

            $product = Product::create($request->except('images') + [
                'business_id' => $business_id,
                'images' => $request->images ? $this->multipleUpload($request, 'images') : null,
            ]);

            Stock::create($request->all() + [
                'product_id' => $product->id,
                'business_id' => $business_id,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data saved successfully.'),
                'data' => $product,
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
        $data = Product::query()
            ->with('unit:id,unitName', 'medicine_type:id,name', 'manufacterer:id,name', 'box_size:id,name', 'category:id,categoryName', 'stocks:id,expire_date,product_id,batch_no,productStock', 'tax:id,rate')
            ->withSum('stocks', 'productStock')
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $business_id = auth()->user()->business_id;
        $stock = Stock::where('product_id', $product->id)->first();

        $request->validate([
            'productName' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'productCode' => [
                'nullable',
                'unique:products,productCode,'.$product->id.',id,business_id,'.$business_id,
            ],
            'batch_no' => [
                'nullable',
                'unique:stocks,batch_no,'.$stock->id.',id,business_id,'.$business_id,
            ],
        ]);

        DB::beginTransaction();
        try {

            if ($request->removed_images) {

                $prev_images = array_diff($product->images ?? [], $request->removed_images);
                foreach ($request->removed_images as $image) {
                    if (Storage::exists($image)) {
                        Storage::delete($image);
                    }
                }

                $prev_images = array_values($prev_images);
            } else {
                $prev_images = $product->images ?? [];
            }

            $new_images = $request->images ? $this->multipleUpload($request, 'images') : [];
            $merged_images = array_merge($prev_images, $new_images);

            $stock = Stock::where('product_id', $product->id)->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                    'productStock' => $stock->productStock + $request->qty,
                ]);
            } else {
                Stock::create($request->all() + [
                    'product_id' => $product->id,
                    'business_id' => $business_id,
                    'productStock' => $request->qty,
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                ]);
            }

            $product->update($request->except('images') + [
                'images' => $merged_images,
            ]);

            DB::commit();

            return response()->json([
                'message' => __('Data updated successfully.'),
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => __('Something was wrong.'),
            ], 406);
        }
    }

    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'batch_no' => 'nullable|string',
            'tax_type' => 'nullable|string',
            'expire_date' => 'nullable|string',
            'tax_id' => 'nullable|exists:taxes,id',
            'purchase_without_tax' => 'required|numeric',
            'purchase_with_tax' => 'required|numeric',
            'profit_percent' => 'nullable|numeric',
            'sales_price' => 'required|numeric',
            'wholesale_price' => 'required|numeric',
            'qty' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {

            $product = Product::findOrFail($id);
            $product->update($request->all());

            $stock = Stock::where('product_id', $product->id)->where('batch_no', $request->batch_no)->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                    'productStock' => $stock->productStock + $request->qty,
                ]);
            } else {
                Stock::create($request->all() + [
                    'product_id' => $product->id,
                    'productStock' => $request->qty,
                    'expire_date' => $request->expire_date,
                    'business_id' => auth()->user()->business_id,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => __('Stock updated successfully.'),
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => __('Something was wrong.'),
            ], 406);
        }
    }

    public function destroy(Product $product)
    {
        foreach ($product->images ?? [] as $image) {
            if (Storage::exists($image)) {
                Storage::delete($image);
            }
        }

        $product->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }

    public function stocksWithProduct(Request $request)
    {
        $data = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'productStock')
            ->with([
                'product.tax:id,rate,name',
                'product:id,productName,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,productCode',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('batch_no', 'like', $term)
                        ->orWhereHas('product', function ($query) use ($term) {
                            $query->where('productName', 'like', $term)
                                ->orWhere('productCode', 'like', $term);
                        });
                });
            })
            ->when($request->input('check_stock') == 'true', function ($query) {
                $query->where('productStock', '>', 0);
            })
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }
}
