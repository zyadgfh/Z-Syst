<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Minimal admin-side sale creation used by the admin panel's quick-sale form
 * and the integration workflow (route: admin.sales.store). The POS checkout
 * with FEFO batch logic lives in Api\ZSystSaleController; this endpoint keeps
 * products.stock (the denormalized counter shown on dashboards) in sync.
 */
class SaleController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|integer|exists:businesses,id',
            'party_id' => 'nullable|integer|exists:parties,id',
            'saleDate' => 'required|string',
            'totalAmount' => 'required|numeric',
            'paidAmount' => 'nullable|numeric',
            'dueAmount' => 'nullable|numeric',
            'paymentType' => 'nullable|string|max:60',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
        ]);

        $sale = DB::transaction(function () use ($request) {
            $sale = Sale::create([
                'business_id' => (int) $request->business_id,
                'party_id' => $request->party_id,
                'user_id' => Auth::id(),
                'saleDate' => $request->saleDate,
                'totalAmount' => (float) $request->totalAmount,
                'paidAmount' => (float) $request->paidAmount,
                'dueAmount' => (float) $request->dueAmount,
                'isPaid' => ((float) $request->dueAmount) <= 0,
                'paymentType' => $request->paymentType ?? 'cash',
            ]);

            foreach ($request->items as $item) {
                $sale->details()->create([
                    'business_id' => (int) $request->business_id,
                    'product_id' => (int) $item['product_id'],
                    'price' => (float) $item['price'],
                    'quantities' => (int) $item['quantity'],
                    'purchase_price' => (float) (Product::find($item['product_id'])->purchase_with_tax ?? 0),
                ]);

                // Keep the denormalized products.stock counter in sync.
                Product::where('id', $item['product_id'])
                    ->decrement('stock', (int) $item['quantity']);
            }

            return $sale;
        });

        return redirect()
            ->back()
            ->with('success', __('Sale created successfully.'));
    }
}
