<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Modules\Landing\App\Exports\ExportTestimonial;
use Modules\Landing\App\Models\Testimonial;

class AcnooTestimonialController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:testimonials-read')->only('index');
        $this->middleware('permission:testimonials-create')->only('create', 'store');
        $this->middleware('permission:testimonials-update')->only('edit', 'update','status');
        $this->middleware('permission:testimonials-delete')->only('destroy','deleteAll');
    }

    public function index(Request $request)
    {
        $testimonials = Testimonial::latest()->paginate(10);
        return view('landing::admin.testimonials.index', compact('testimonials'));
    }

    public function acnooFilter(Request $request)
    {
        $testimonials = Testimonial::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('text', 'like', '%' . request('search') . '%')
                    ->orWhere('client_name', 'like', '%' . request('search') . '%')
                    ->orWhere('work_at', 'like', '%' . request('search') . '%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('landing::admin.testimonials.datas', compact('testimonials'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        return view('landing::admin.testimonials.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'work_at' => 'nullable|string',
            'text' => 'nullable',
            'star' => 'nullable',
            'client_name' => 'required|string',
            'client_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ]);

        Testimonial::create($request->except('client_image') + [
            'client_image' => $request->client_image ? $this->upload($request, 'client_image') : NULL
        ]);

        return response()->json([
            'message' => __('Testimonial created successfully'),
            'redirect' => route('admin.testimonials.index')
        ]);
    }

    public function edit(Testimonial $testimonial)
    {
        return view('landing::admin.testimonials.edit', compact('testimonial'));
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $request->validate([
            'work_at' => 'nullable|string',
            'text' => 'nullable|string',
            'star' => 'nullable|integer',
            'client_name' => 'required|string',
            'client_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
        ]);

        $testimonial->update($request->except('client_image') + [
            'client_image' => $request->client_image ? $this->upload($request, 'client_image', $testimonial->client_image) : $testimonial->client_image,
        ]);

        return response()->json([
            'message' => __('Testimonial updated successfully'),
            'redirect' => route('admin.testimonials.index')
        ]);
    }

    public function destroy(Testimonial $testimonial)
    {
        if (file_exists($testimonial->client_image)) {
            Storage::delete($testimonial->client_image);
        }
        $testimonial->delete();

        return response()->json([
            'message'   => __('Testimonial Deleted successfully'),
            'redirect'  => route('admin.testimonials.index')
        ]);
    }
    public function deleteAll(Request $request)
    {
        $testimonials = Testimonial::whereIn('id', $request->ids)->get();
        foreach ($testimonials as $testimonial) {
            if (file_exists($testimonial->image)) {
                Storage::delete($testimonial->image);
            }
        }

        $testimonials->each->delete();

        return response()->json([
            'message' => __('Selected Testimonial deleted successfully'),
            'redirect' => route('admin.testimonials.index')
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new ExportTestimonial, 'testimonials.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportTestimonial, 'testimonials.csv');
    }
}
