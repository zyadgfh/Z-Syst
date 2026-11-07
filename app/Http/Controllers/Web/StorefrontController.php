<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Product;
use App\Models\StoreOrder;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Public per-tenant storefront: /store/{slug} — catalog + cart + checkout
 * (pickup or delivery). Orders land in store_orders for the tenant to
 * confirm/sell at the POS. Optionally pays online via a gateway.
 */
class StorefrontController extends Controller
{
    public function show(string $slug)
    {
        $business = Business::where('store_slug', $slug)->firstOrFail();

        return view('web.storefront', [
            'business' => $business,
            'products' => Product::where('business_id', $business->id)->orderBy('name')->limit(60)->get(['id', 'name', 'sales_price', 'productCode']),
        ]);
    }

    public function checkout(Request $request, string $slug)
    {
        $business = Business::where('store_slug', $slug)->firstOrFail();
        $data = $request->validate([
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'required|string|max:32',
            'address' => 'nullable|string|max:500',
            'fulfillment' => 'required|in:pickup,delivery',
            'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Server-side pricing (never trust the client with money).
        $items = collect($data['items'])->map(function ($i) use ($business) {
            $product = Product::where('business_id', $business->id)->findOrFail($i['product_id']);

            return [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->sales_price,
                'qty' => (int) $i['qty'],
            ];
        });

        $total = round($items->sum(fn ($i) => $i['price'] * $i['qty']), 2) + ($data['fulfillment'] === 'delivery' ? 15 : 0);

        $order = StoreOrder::create([
            'business_id' => $business->id,
            'order_number' => 'SO-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'address' => $data['address'],
            'fulfillment' => $data['fulfillment'],
            'status' => 'new',
            'total' => $total,
            'items' => $items->values()->all(),
            'notes' => $data['fulfillment'] === 'delivery' ? __('رسوم توصيل 15 جنيه مضافة') : null,
        ]);

        return response()->json(['success' => true, 'order_number' => $order->order_number, 'total' => $total]);
    }
}
