<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\Customer;

class CustomerController
{
    public function index(): JsonResponse
    {
        return response()->json(Customer::latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'loyalty_points' => ['nullable', 'integer'],
        ]);

        $customer = Customer::create($data);

        return response()->json($customer, 201);
    }
}
