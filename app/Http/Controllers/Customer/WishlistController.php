<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /**
     * Toggle wishlist status for a product (AJAX-friendly).
     */
    public function toggle(Product $product): JsonResponse
    {
        $added = Wishlist::toggle($product->id);

        return response()->json([
            'success' => true,
            'added'   => $added,
            'count'   => Wishlist::count(),
            'message' => $added ? 'تمت الإضافة إلى المفضلة' : 'تمت الإزالة من المفضلة',
        ]);
    }

    /**
     * Display user's wishlist page.
     */
    public function index(Request $request)
    {
        $items = Wishlist::forCurrentVisitor()
            ->with(['product.category', 'product.manufacturer'])
            ->latest()
            ->paginate(12);

        return view('customer.wishlist.index', compact('items'));
    }

    /**
     * Remove a single item from wishlist.
     */
    public function destroy(Wishlist $wishlist): JsonResponse
    {
        // Ensure ownership
        if (auth()->check() && $wishlist->user_id !== auth()->id()) {
            abort(403);
        }

        $wishlist->delete();

        return response()->json([
            'success' => true,
            'count'   => Wishlist::count(),
            'message' => 'تمت الإزالة من المفضلة',
        ]);
    }
}
