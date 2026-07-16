<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AcnooCategoryController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = Category::query()
            ->where('company_id', $companyId)
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = $request->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $categoryPayload = array_merge($data, ['company_id' => $companyId]);
        if (Schema::hasColumn('categories', 'business_id') && Schema::hasTable('businesses')) {
            $hasBusiness = DB::table('businesses')->where('id', $companyId)->exists();
            if ($hasBusiness) {
                $categoryPayload['business_id'] = $companyId;
            }
        }

        $category = Category::create($categoryPayload);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $companyId = $request->user()->company_id ?? app('tenant.company_id');

        $data = $request->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($category->id)->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $categoryPayload = array_merge($data, ['company_id' => $companyId]);
        if (Schema::hasColumn('categories', 'business_id') && Schema::hasTable('businesses')) {
            $hasBusiness = DB::table('businesses')->where('id', $companyId)->exists();
            if ($hasBusiness) {
                $categoryPayload['business_id'] = $companyId;
            }
        }

        $category->update($categoryPayload);

        return response()->json([
            'message' => __('Data updated successfully.'),
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
