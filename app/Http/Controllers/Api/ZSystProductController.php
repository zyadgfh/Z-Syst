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
        $data = Product::select('id', 'product_name', 'product_code', 'purchase_with_tax', 'sales_price')
            ->where('business_id', auth()->user()->business_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($sub) use ($term) {
                    $sub->where('product_name', 'like', $term)
                        ->orWhere('product_code', 'like', $term);
                });
            })
            ->when($request->filled('expire_date'), function ($query) use ($request) {
                $query->whereHas('stocks', function ($query) use ($request) {
                    $query->whereBetween('expire_date', [today(), $request->input('expire_date')]);
                });
            })
            ->when($request->input('expired') == 'true', function ($query) {
                $query->whereHas('stocks', function ($query) {
                    $query->where('expire_date', '<', today())
                        ->where('product_stock', '>', 0);
                });
            })
            ->withSum('stocks', 'product_stock')
            ->with(['expiring_item' => function ($query) {
                $query->select('expire_date', 'product_id')
                    ->where('product_stock', '>', 0)
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
            'product_name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'product_code' => [
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

            $product = Product::create($request->only([
                'product_name', 'category_id', 'unit_id', 'type_id', 'manufacturer_id',
                'box_size_id', 'purchase_without_tax', 'purchase_with_tax',
                'profit_percent', 'sales_price', 'alert_qty', 'wholesale_price',
                'product_code', 'meta', 'tax_id', 'tax_type',
            ]) + [
                'business_id' => $business_id,
                'images' => $request->images ? $this->multipleUpload($request, 'images') : null,
            ]);

            Stock::create($request->only(['batch_no', 'expire_date', 'product_stock']) + [
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
            ->with('unit:id,unit_name', 'medicine_type:id,name', 'manufacterer:id,name', 'box_size:id,name', 'category:id,category_name', 'stocks:id,expire_date,product_id,batch_no,product_stock', 'tax:id,rate')
            ->withSum('stocks', 'product_stock')
            ->when(auth()->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', auth()->user()->business_id))
            ->findOrFail($id);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $business_id = auth()->user()->business_id;
        abort_unless(auth()->user()->role === 'superadmin'
            || (int) $product->business_id === (int) $business_id, 404);
        $stock = Stock::where('product_id', $product->id)
            ->when(auth()->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $business_id))
            ->first();

        $request->validate([
            'product_name' => 'required|string',
            'category_id' => 'required|integer|exists:categories,id',
            'type_id' => 'nullable|integer|exists:medicine_types,id',
            'unit_id' => 'nullable|integer|exists:units,id',
            'manufacturer_id' => 'nullable|integer|exists:manufacturers,id',
            'box_size_id' => 'nullable|integer|exists:box_sizes,id',
            'product_code' => [
                'nullable',
                'unique:products,product_code,'.$product->id.',id,business_id,'.$business_id,
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

            $stock = Stock::where('product_id', $product->id)
                ->when(auth()->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $business_id))
                ->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                    'product_stock' => $stock->product_stock + $request->qty,
                ]);
            } else {
                Stock::create($request->only(['batch_no', 'expire_date']) + [
                    'product_id' => $product->id,
                    'business_id' => auth()->user()->role === 'superadmin'
                        ? $product->business_id
                        : $business_id,
                    'product_stock' => $request->qty,
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                ]);
            }

            $product->update($request->only([
                'product_name', 'category_id', 'type_id', 'unit_id', 'manufacturer_id',
                'box_size_id', 'purchase_without_tax', 'purchase_with_tax',
                'profit_percent', 'sales_price', 'alert_qty', 'wholesale_price',
                'product_code', 'meta', 'tax_id', 'tax_type',
            ]) + [
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

            $business_id = auth()->user()->business_id;
            $product = Product::query()
                ->when(auth()->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $business_id))
                ->findOrFail($id);
            $product->update($request->only([
                'purchase_without_tax', 'purchase_with_tax', 'profit_percent',
                'sales_price', 'wholesale_price', 'tax_type', 'tax_id',
            ]));

            $stock = Stock::where('product_id', $product->id)
                ->where('batch_no', $request->batch_no)
                ->when(auth()->user()->role !== 'superadmin', fn ($query) => $query->where('business_id', $business_id))
                ->first();

            if ($stock) {
                $stock->update([
                    'batch_no' => $request->batch_no,
                    'expire_date' => $request->expire_date,
                    'product_stock' => $stock->product_stock + $request->qty,
                ]);
            } else {
                Stock::create($request->only(['batch_no', 'expire_date']) + [
                    'product_id' => $product->id,
                    'product_stock' => $request->qty,
                    'expire_date' => $request->expire_date,
                    'business_id' => auth()->user()->role === 'superadmin'
                        ? $product->business_id
                        : $business_id,
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
        $data = Stock::select('id', 'expire_date', 'product_id', 'batch_no', 'product_stock')
            ->with([
                'product.tax:id,rate,product_name',
                'product:id,product_name,purchase_without_tax,purchase_with_tax,profit_percent,sales_price,wholesale_price,tax_id,tax_type,product_code',
            ])
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%'.$request->input('search').'%';
                $query->where(function ($subQuery) use ($term) {
                    $subQuery->where('batch_no', 'like', $term)
                        ->orWhereHas('product', function ($query) use ($term) {
                            $query->where('product_name', 'like', $term)
                                ->orWhere('product_code', 'like', $term);
                        });
                });
            })
            ->when($request->input('check_stock') == 'true', function ($query) {
                $query->where('product_stock', '>', 0);
            })
            ->where('business_id', auth()->user()->business_id)
            ->latest()
            ->paginate($request->input('per_page', 10));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * In-stock substitutes for a product — the "smart alternative" feature.
     * Ranking (no generics table in the legacy schema, so heuristics):
     *   +3  same active-ingredient word (first token of productName,
     *       e.g. "Paracetamol" matches "Paracetamol Extra")
     *   +2  same category
     *   +1  same manufacturer
     * Only in-stock items from the caller's business are returned, with
     * known drug-interaction warnings for the original product appended.
     */
    public function alternatives(Product $product): \Illuminate\Http\JsonResponse
    {
        $businessId = auth()->user()->business_id;
        abort_unless($product->business_id === $businessId, 404);

        $root = trim(mb_strtolower(explode(' ', trim($product->product_name))[0] ?? ''));

        $candidates = Product::query()
            ->where('products.business_id', $businessId)
            ->where('products.id', '!=', $product->id)
            ->whereHas('stocks', function ($q) use ($businessId) {
                $q->where('business_id', $businessId)->where('product_stock', '>', 0);
            })
            ->get()
            ->map(function (Product $candidate) use ($businessId) {
                $candidate->available_stock_total = (int) Stock::where('business_id', $businessId)
                    ->where('product_id', $candidate->id)
                    ->where('product_stock', '>', 0)
                    ->sum('product_stock');
                return $candidate;
            });

        $scored = $candidates->map(function (Product $candidate) use ($product, $root) {
            $first = trim(mb_strtolower(explode(' ', trim($candidate->product_name))[0] ?? ''));
            $score = 0;
            if ($root !== '' && $first === $root) {
                $score += 3;
            }
            if ($candidate->category_id && $candidate->category_id === $product->category_id) {
                $score += 2;
            }
            if ($candidate->manufacturer_id && $candidate->manufacturer_id === $product->manufacturer_id) {
                $score += 1;
            }

            return [
                'id' => $candidate->id,
                'product_name' => $candidate->product_name,
                'product_code' => $candidate->product_code,
                'available_stock' => (int) ($candidate->available_stock_total ?? 0),
                'match_score' => $score,
            ];
        })
            ->filter(fn ($row) => $row['match_score'] > 0)
            ->sortByDesc('match_score')
            ->take(6)
            ->values();

        $warnings = \App\Models\DrugInteraction::where('business_id', $businessId)
            ->where(function ($q) use ($product) {
                $q->where('drug_a_name', 'like', '%' . $product->product_name . '%')
                    ->orWhere('drug_b_name', 'like', '%' . $product->product_name . '%');
            })
            ->limit(5)
            ->get(['drug_a_name', 'drug_b_name', 'description', 'recommendation']);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => [
                'product' => ['id' => $product->id, 'product_name' => $product->product_name],
                'alternatives' => $scored,
                'interaction_warnings' => $warnings,
            ],
        ]);
    }
}
