<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BusinessCategoryExport;
use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ZSystBusinessCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:business-categories-read')->only('index', 'exportExcel', 'exportCsv');
        $this->middleware('permission:business-categories-update')->only('create', 'store', 'edit', 'update', 'status', 'zsystFilter');
        $this->middleware('permission:business-categories-delete')->only('destroy', 'deleteAll');
    }

    public function index()
    {
        $this->authorize('viewAny', BusinessCategory::class);

        $categories = BusinessCategory::latest()->paginate(10);

        return view('admin.business-categories.index', compact('categories'));
    }

    public function zsystFilter(Request $request)
    {
        $categories = BusinessCategory::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%'.request('search').'%')
                    ->orWhere('description', 'like', '%'.request('search').'%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.business-categories.datas', compact('categories'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        $this->authorize('create', BusinessCategory::class);

        return view('admin.business-categories.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', BusinessCategory::class);

        $request->validate([
            'status' => 'in:on',
            'description' => 'nullable|string|max:255',
            'name' => 'required|string|unique:business_categories|max:255',
        ]);

        BusinessCategory::create($request->except('status') + [
            'status' => $request->status ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Category saved successfully'),
            'redirect' => route('admin.business-categories.index'),
        ]);
    }

    public function edit($id)
    {
        $category = BusinessCategory::findOrFail($id);
        $this->authorize('update', $category);

        return view('admin.business-categories.edit', compact('category'));
    }

    public function update(Request $request, $id)
    {
        $category = BusinessCategory::findOrFail($id);
        $this->authorize('update', $category);

        $request->validate([
            'status' => 'in:on',
            'description' => 'nullable|string|max:255',
            'name' => 'required|string|max:255|unique:business_categories,name,'.$id,
        ]);

        $category = BusinessCategory::find($id);

        $category->update($request->except('status') + [
            'status' => $request->status ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Category updated successfully'),
            'redirect' => route('admin.business-categories.index'),
        ]);
    }

    public function destroy($id)
    {
        $category = BusinessCategory::findOrFail($id);
        $this->authorize('delete', $category);
        $category->delete();

        return response()->json([
            'message' => __('Category deleted successfully'),
            'redirect' => route('admin.business-categories.index'),
        ]);
    }

    public function deleteAll(Request $request)
    {
        $this->authorize('delete', BusinessCategory::class);
        BusinessCategory::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => __('Selected Category deleted successfully'),
            'redirect' => route('admin.business-categories.index'),
        ]);
    }

    public function status(Request $request, $id)
    {
        $category = BusinessCategory::findOrFail($id);
        $category->update(['status' => $request->status]);

        return response()->json(['message' => 'Business category']);
    }

    public function exportExcel()
    {
        return Excel::download(new BusinessCategoryExport, 'business-categories.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new BusinessCategoryExport, 'business-categories.csv');
    }
}
