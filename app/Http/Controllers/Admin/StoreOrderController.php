<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\ZSystSaleController;
use App\Models\StoreOrder;
use App\Services\DeviceSettings;
use Illuminate\Http\Request;

/** Admin side of the per-tenant storefront: confirm → sell → (deliver). */
class StoreOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:store-orders-read')->only('index');
        $this->middleware('permission:store-orders-update')->only(['status']);
    }

    public function index(Request $request)
    {
        return view('admin.store-orders.index', [
            'orders' => StoreOrder::forBusiness((int) $request->user()->business_id)->latest('id')->paginate(15),
        ]);
    }

    public function status(Request $request, StoreOrder $order)
    {
        $data = $request->validate(['status' => 'required|in:new,confirmed,ready,delivered,cancelled']);
        $order->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    /** Convert a confirmed storefront order into a real sale via the hardened store. */
    public function sell(Request $request, StoreOrder $order)
    {
        if ($order->sale_id) {
            return response()->json(['success' => false, 'message' => __('محوّلة لمبيعة مسبقًا')], 422);
        }

        $lines = collect($order->items)->map(fn ($i) => [
            'product_id' => (int) $i['product_id'],
            'price' => (float) $i['price'],
            'lossProfit' => 0,
            'quantities' => (int) $i['qty'],
        ])->values()->all();

        $apiRequest = Request::create('/api/v1/sales', 'POST', [
            'products' => $lines,
            'saleDate' => now()->toDateString(),
            'paidAmount' => (float) $order->total,
            'isPaid' => true,
            'paymentType' => 'online',
        ]);
        $apiRequest->setUserResolver(fn () => $request->user());
        $apiRequest->setLaravelSession($request->session());
        $apiRequest->headers->set('Accept', 'application/json');

        $response = app(ZSystSaleController::class)->store($apiRequest);
        if ($response->getStatusCode() >= 300) {
            return response()->json(json_decode($response->getContent(), true), $response->getStatusCode());
        }

        $body = json_decode($response->getContent(), true);
        $order->update(['sale_id' => $body['data']['id'] ?? null, 'status' => 'confirmed']);

        return response()->json(['success' => true, 'sale_id' => $order->sale_id]);
    }
}
