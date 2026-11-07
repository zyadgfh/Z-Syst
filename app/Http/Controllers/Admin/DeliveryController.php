<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:deliveries-read')->only('index');
        $this->middleware('permission:deliveries-create')->only('store');
        $this->middleware('permission:deliveries-update')->only(['assign', 'status']);
    }

    public function index(Request $request)
    {
        $businessId = (int) $request->user()->business_id;

        return view('admin.deliveries.index', [
            'deliveries' => Delivery::forBusiness($businessId)->latest('id')->paginate(15),
            'couriers' => User::where('business_id', $businessId)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'nullable|string|max:32',
            'address' => 'required|string|max:500',
            'fee' => 'nullable|numeric|min:0',
        ]);
        $data['business_id'] = (int) $request->user()->business_id;
        $data['fee'] ??= 0;
        Delivery::create($data);

        return back()->with('success', __('أُنشئ طلب التوصيل'));
    }

    public function assign(Request $request, Delivery $delivery)
    {
        $data = $request->validate(['courier_user_id' => 'required|exists:users,id']);
        $courier = User::find($data['courier_user_id']);
        $delivery->update([
            'courier_user_id' => $courier->id,
            'courier_name' => $courier->name,
            'status' => $delivery->status === 'pending' ? 'assigned' : $delivery->status,
            'assigned_at' => now(),
        ]);

        return response()->json(['success' => true, 'status' => $delivery->status]);
    }

    public function status(Request $request, Delivery $delivery)
    {
        $data = $request->validate(['status' => 'required|in:pending,assigned,picked,out_for_delivery,delivered,failed,cancelled']);
        $delivery->update(array_filter([
            'status' => $data['status'],
            'delivered_at' => $data['status'] === 'delivered' ? now() : null,
        ]));

        return response()->json(['success' => true, 'status' => $delivery->status]);
    }
}
