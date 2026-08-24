<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerOrder;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show review form for a product
     */
    public function create(Request $request, int $productId)
    {
        $user = Auth::user();

        // Check eligibility
        if (!ProductReview::canUserReview($user->id, $productId)) {
            return back()->with('error', __('You have already reviewed this product or haven\'t purchased it yet.'));
        }

        // Find the delivered order containing this product
        $orderItem = \App\Models\CustomerOrderItem::where('product_id', $productId)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('status', 'delivered');
            })
            ->with('order')
            ->first();

        $product = \App\Models\Product::findOrFail($productId);

        return view('customer.reviews.create', compact('product', 'orderItem'));
    }

    /**
     * Store a new review
     */
    public function store(Request $request, int $productId)
    {
        $user = Auth::user();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'review' => 'required|string|min:10|max:2000',
            'customer_order_id' => 'nullable|exists:customer_orders,id',
        ]);

        if (!ProductReview::canUserReview($user->id, $productId)) {
            return back()->with('error', __('You have already reviewed this product.'));
        }

        // Check if this is a verified purchase
        $isVerified = false;
        $orderId = null;

        if ($request->customer_order_id) {
            $orderExists = \App\Models\CustomerOrderItem::where('product_id', $productId)
                ->where('customer_order_id', $request->customer_order_id)
                ->whereHas('order', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 'delivered');
                })
                ->exists();

            if ($orderExists) {
                $isVerified = true;
                $orderId = $request->customer_order_id;
            }
        }

        ProductReview::create([
            'product_id' => $productId,
            'user_id' => $user->id,
            'customer_order_id' => $orderId,
            'rating' => $request->rating,
            'title' => $request->title,
            'review' => $request->review,
            'is_verified_purchase' => $isVerified,
        ]);

        return back()->with('success', __('Thank you! Your review has been submitted.'));
    }

    /**
     * Mark a review as helpful
     */
    public function helpful(ProductReview $review)
    {
        $review->increment('helpful_count');

        return response()->json([
            'success' => true,
            'helpful_count' => $review->fresh()->helpful_count,
        ]);
    }
}
