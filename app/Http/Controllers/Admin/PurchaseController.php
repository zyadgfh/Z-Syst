<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Minimal admin-side purchase creation (route: admin.purchases.store).
 * Receives items into stock and keeps products.stock in sync.
 */
class PurchaseController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'party_id' => 'required|integer|exists:parties,id',
            'purchase_date' => 'required|string',
            'total_amount' => 'required|numeric',
            'paid_amount' => 'nullable|numeric',
            'due_amount' => 'nullable|numeric',
            'payment_type' => 'nullable|string|max:60',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
        ]);

        $purchase = DB::transaction(function () use ($request) {
            $purchase = Purchase::create([
                'business_id' => (int) $request->business_id,
                'party_id' => (int) $request->party_id,
                'user_id' => Auth::id(),
                'purchase_date' => $request->purchase_date,
                'total_amount' => (float) $request->total_amount,
                'paid_amount' => (float) $request->paid_amount,
                'due_amount' => (float) $request->due_amount,
                'is_paid' => ((float) $request->due_amount) <= 0,
                'payment_type' => $request->payment_type ?? 'cash',
            ]);

            foreach ($request->items as $item) {
                $purchase->details()->create([
                    'business_id' => (int) $request->business_id,
                    'product_id' => (int) $item['product_id'],
                    'quantities' => (int) $item['quantity'],
                    'purchase_without_tax' => (float) $item['price'],
                    'purchase_with_tax' => (float) $item['price'],
                ]);

                // Receive into stock.
                Product::where('id', $item['product_id'])
                    ->increment('stock', (int) $item['quantity']);
            }

            return $purchase;
        });

        return redirect()
            ->back()
            ->with('success', __('Purchase created successfully.'));
    }
}
