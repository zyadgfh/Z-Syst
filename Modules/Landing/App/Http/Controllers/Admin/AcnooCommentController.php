<?php

namespace Modules\Landing\App\Http\Controllers\Admin;

use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Comment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AcnooCommentController extends Controller
{

    public function acnooFilter(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);
        $comments = Comment::with('blog:id')->whereStatus(1)->where('blog_id', $blog->id)->when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%' . request('search') . '%')
                    ->orWhere('email', 'like', '%' . request('search') . '%')
                    ->orWhere('comment', 'like', '%' . request('search') . '%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('landing::admin.blogs.comment.datas', compact('comments'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();
        return response()->json([
            'message' => __('Comment deleted successfully.'),
            'redirect' => route('admin.blogs.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Comment::whereIn('id', $request->ids)->delete();
        return response()->json([
            'message' => __('Selected Comments deleted successfully'),
            'redirect' => route('admin.blogs.index')
        ]);
    }
}
