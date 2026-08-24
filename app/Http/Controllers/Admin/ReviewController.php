<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings-read')->only('index');
        $this->middleware('permission:settings-update')->only('approve', 'reject', 'destroy');
    }

    /**
     * List all reviews
     */
    public function index(Request $request)
    {
        $query = ProductReview::with('product:id,productName', 'user:id,name,email');

        if ($status = $request->input('status')) {
            $query->where('is_approved', $status === 'approved');
        }

        if ($rating = $request->input('rating')) {
            $query->where('rating', $rating);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('review', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('productName', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $reviews = $query->latest()->paginate(15);

        $stats = [
            'total' => ProductReview::count(),
            'pending' => ProductReview::where('is_approved', false)->count(),
            'approved' => ProductReview::where('is_approved', true)->count(),
            'avg_rating' => ProductReview::where('is_approved', true)->avg('rating') ?? 0,
        ];

        return view('admin.reviews.index', compact('reviews', 'stats'));
    }

    /**
     * Approve a review
     */
    public function approve(ProductReview $review)
    {
        $review->update(['is_approved' => true]);

        return response()->json([
            'success' => true,
            'message' => __('Review approved.'),
        ]);
    }

    /**
     * Reject/hide a review
     */
    public function reject(ProductReview $review)
    {
        $review->update(['is_approved' => false]);

        return response()->json([
            'success' => true,
            'message' => __('Review hidden.'),
        ]);
    }

    /**
     * Delete a review
     */
    public function destroy(ProductReview $review)
    {
        $review->delete();

        return response()->json([
            'success' => true,
            'message' => __('Review deleted.'),
        ]);
    }
}
