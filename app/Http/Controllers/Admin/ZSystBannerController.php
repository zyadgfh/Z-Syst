<?php

namespace App\Http\Controllers\Admin;

use App\Exports\BannerExport;
use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ZSystBannerController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:banners-create')->only('create', 'store');
        $this->middleware('permission:banners-read')->only('index');
        $this->middleware('permission:banners-update')->only('edit', 'update', 'status');
        $this->middleware('permission:banners-delete')->only('destroy', 'deleteAll');
    }

    public function index(Request $request)
    {
        $banners = Banner::latest()->paginate(10);

        return view('admin.banners.index', compact('banners'));
    }

    public function zsystFilter(Request $request)
    {
        $banners = Banner::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%'.request('search').'%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.banners.datas', compact('banners'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:250',
            'status' => 'nullable|in:on',
            'imageUrl' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        Banner::create([
            'name' => $request->name,
            'imageUrl' => $request->imageUrl ? $this->upload($request, 'imageUrl') : null,
            'status' => $request->status ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Banner saved successfully'),
            'redirect' => route('admin.banners.index'),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|max:250',
            'status' => 'nullable|in:on',
            'imageUrl' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
        ]);

        $banner = Banner::findOrFail($id);

        $banner->update([
            'name' => $request->name,
            'imageUrl' => $request->imageUrl ? $this->upload($request, 'imageUrl', $banner->imageUrl) : $banner->imageUrl,
            'status' => $request->status ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Banner updated successfully'),
            'redirect' => route('admin.banners.index'),
        ]);
    }

    public function destroy(string $id)
    {
        $banner = Banner::findOrFail($id);

        if (file_exists($banner->imageUrl)) {
            Storage::delete($banner->imageUrl);
        }

        $banner->delete();

        return response()->json([
            'message' => __('Banners deleted successfully'),
            'redirect' => route('admin.banners.index'),
        ]);

    }

    public function status(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        $banner->update(['status' => $request->status]);

        return response()->json(['message' => 'Banner']);
    }

    public function deleteAll(Request $request)
    {
        $idsToDelete = $request->input('ids');
        DB::beginTransaction();
        try {
            $banners = Banner::whereIn('id', $idsToDelete)->get();
            foreach ($banners as $banner) {
                if (file_exists($banner->imageUrl)) {
                    Storage::delete($banner->imageUrl);
                }
            }

            Banner::whereIn('id', $idsToDelete)->delete();

            DB::commit();

            return response()->json([
                'message' => __('Selected Banners deleted successfully'),
                'redirect' => route('admin.banners.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(__('Something was wrong.'), 400);
        }

    }

    public function exportExcel()
    {
        return Excel::download(new BannerExport, 'banners.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new BannerExport, 'banners.csv');
    }
}
