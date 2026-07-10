<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ZSyst\App\Models\Customer;
use Modules\ZSyst\App\Models\LoyaltyPointTransaction;

class LoyaltyController
{
    public function index(): JsonResponse
    {
        return response()->json(LoyaltyPointTransaction::with('customer')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'type' => ['required', 'string', 'in:earn,redeem,adjust'],
            'points' => ['required', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $transaction = LoyaltyPointTransaction::create($data);

        $customer = Customer::findOrFail($data['customer_id']);
        $customer->loyalty_points = max(0, $customer->loyalty_points + $data['points']);
        $customer->save();

        return response()->json([
            'message' => 'Loyalty transaction recorded',
            'data' => $transaction,
        ], 201);
    }
}
