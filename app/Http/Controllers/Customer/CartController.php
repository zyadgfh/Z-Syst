<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Get cart items (JSON for AJAX)
     */
    public function index(Request $request)
    {
        $cartItems = $this->getCartQuery()->with('product')->get();

        $subtotal = $cartItems->sum(fn ($item) => $item->total_price);

        return response()->json([
            'items' => $cartItems,
            'count' => $cartItems->sum('quantity'),
            'subtotal' => number_format($subtotal, 2),
        ]);
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->active || $product->archived) {
            return response()->json(['error' => __('Product is not available.')], 400);
        }

        $query = $this->getCartQuery();
        $existingItem = $query->where('product_id', $product->id)->first();

        if ($existingItem) {
            $existingItem->increment('quantity', $request->quantity);
        } else {
            CartItem::create([
                'user_id' => Auth::id(),
                'session_id' => Auth::guest() ? session()->getId() : null,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        $cartCount = $this->getCartQuery()->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => __('Item added to cart.'),
            'cart_count' => $cartCount,
        ]);
    }

    /**
     * Update item quantity
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeCart($cartItem);

        $request->validate([
            'quantity' => 'required|integer|min:1|max:100',
        ]);

        $cartItem->update(['quantity' => $request->quantity]);

        $cartItems = $this->getCartQuery()->with('product')->get();
        $subtotal = $cartItems->sum(fn ($item) => $item->total_price);

        return response()->json([
            'success' => true,
            'message' => __('Cart updated.'),
            'cart_count' => $cartItems->sum('quantity'),
            'subtotal' => number_format($subtotal, 2),
        ]);
    }

    /**
     * Remove item from cart
     */
    public function remove(CartItem $cartItem)
    {
        $this->authorizeCart($cartItem);
        $cartItem->delete();

        $cartCount = $this->getCartQuery()->sum('quantity');

        return response()->json([
            'success' => true,
            'message' => __('Item removed from cart.'),
            'cart_count' => $cartCount,
        ]);
    }

    /**
     * Clear entire cart
     */
    public function clear()
    {
        $this->getCartQuery()->delete();

        return response()->json([
            'success' => true,
            'message' => __('Cart cleared.'),
            'cart_count' => 0,
        ]);
    }

    /**
     * Get cart count (for header badge)
     */
    public function count()
    {
        $count = $this->getCartQuery()->sum('quantity');
        return response()->json(['count' => $count]);
    }

    // ── Helpers ──

    protected function getCartQuery()
    {
        if (Auth::check()) {
            return CartItem::where('user_id', Auth::id());
        }

        return CartItem::where('session_id', session()->getId())
            ->whereNull('user_id');
    }

    protected function authorizeCart(CartItem $cartItem): void
    {
        if (Auth::check()) {
            abort_if($cartItem->user_id !== Auth::id(), 403);
        } else {
            abort_if($cartItem->session_id !== session()->getId(), 403);
        }
    }
}
