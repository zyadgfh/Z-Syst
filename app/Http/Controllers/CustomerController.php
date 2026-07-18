<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        return Customer::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $payload = [
            'name' => $request->input('name'),
            'phone' => $request->input('phone'),
            'email' => $request->input('email'),
            'address' => $request->input('address'),
            'customer_type' => $request->input('customer_type', 'individual'),
            'customer_group' => $request->input('customer_group', 'regular'),
            'is_active' => $request->input('is_active', true),
        ];

        $customer = Customer::create($payload);

        return response()->json($customer, 201);
    }
}
