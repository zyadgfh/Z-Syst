<?php

namespace Modules\Landing\App\Http\Controllers\Admin;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Landing\App\Models\Blog;
use Illuminate\Support\Facades\Storage;
use Modules\Landing\App\Exports\ExportBlog;
use Modules\Landing\App\Models\Comment;

class AcnooBlogController extends Controller
{
    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:blogs-read')->only('index');
        $this->middleware('permission:blogs-create')->only('create', 'store');
        $this->middleware('permission:blogs-update')->only('edit', 'update','status');
        $this->middleware('permission:blogs-delete')->only('destroy','deleteAll');
    }

    public function index(Request $request)
    {
        $blogs = Blog::latest()->paginate(10);
        return view('landing::admin.blogs.index', compact('blogs'));
    }

    public function acnooFilter(Request $request)
    {
        $blogs = Blog::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('title', 'like', '%' . request('search') . '%')
                    ->orWhere('slug', 'like', '%' . request('search') . '%')
                    ->orWhere('descriptions', 'like', '%' . request('search') . '%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('landing::admin.blogs.datas', compact('blogs'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function filterComment($id)
    {
        $blog = Blog::findOrFail($id);
        $comments = Comment::with('blog:id')->whereStatus(1)->where('blog_id', $blog->id)->latest()->paginate(10);
        return view('landing::admin.blogs.comment.index',compact('comments', 'blog'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('landing::admin.blogs.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'                  => 'required|unique:blogs,title',
            'image'                  => 'required|image|mimes:jpeg,png,jpg,gif,svg',
            'status'                 => 'boolean',
            'tags'                   => 'nullable|array',
            'meta.title'             => 'nullable|string',
            'meta.description'       => 'nullable|string',
            'descriptions'           => 'nullable|string',
        ]);

       $blog = Blog::create($request->except('image') + [
            'user_id' => Auth::id(),
            'image' => $request->image ? $this->upload($request, 'image') : null,
        ]);

        sendNotification($blog->id, route('admin.blogs.index', ['id' => $blog->id]), 'A new blog post has been published.');

        return response()->json([
            'message'   => __('BLog created successfully'),
            'redirect'  => route('admin.blogs.index')
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Blog $blog)
    {
        return view('landing::admin.blogs.edit', compact('blog'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Blog $blog)
    {
        $request->validate([
            'title'             => 'required|unique:blogs,title,' . $blog->id,
            'image'             => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'status'            => 'boolean',
            'descriptions'      => 'nullable|string',
            'tags'              => 'nullable|array',
            'meta.title'        => 'nullable|string',
            'meta.description'  => 'nullable|string',
        ]);

        $blog->update($request->except('image') + [
            'user_id' => Auth::id(),
            'image' => $request->image ? $this->upload($request, 'image', $blog->image) : $blog->image,
        ]);

        return response()->json([
            'message'   => __('BLog updated successfully'),
            'redirect'  => route('admin.blogs.index')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Blog $blog)
    {
        if (file_exists($blog->image)) {
            Storage::delete($blog->image);
        }

        $blog->delete();

        return response()->json([
            'message' => __('Blog deleted successfully'),
            'redirect' => route('admin.blogs.index')
        ]);
    }

    public function status(Request $request, $id)
    {
        $blog_status = Blog::findOrFail($id);
        $blog_status->update(['status' => $request->status]);
        return response()->json(['message' => 'Blog']);
    }

    public function deleteAll(Request $request)
    {
      $blogs = Blog::whereIn('id', $request->ids)->get();

        foreach($blogs as $blog) {
        if (file_exists($blog->image)) {
            Storage::delete($blog->image);
            }
        }

        Blog::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => __('Selected Blog deleted successfully'),
            'redirect' => route('admin.blogs.index')
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new ExportBlog, 'blogs.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new ExportBlog, 'blogs.csv');
    }
}
