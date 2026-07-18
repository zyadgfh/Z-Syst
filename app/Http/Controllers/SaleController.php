<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\SaleInvoice;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index()
    {
        return SaleInvoice::query()->latest()->get();
    }

    public function store(Request $request)
    {
        $payload = [
            'customer_id' => $request->input('customer_id'),
            'invoice_number' => $request->input('invoice_number', 'INV-' . now()->timestamp),
            'subtotal' => $request->input('subtotal', 0),
            'discount_amount' => $request->input('discount_amount', 0),
            'total_amount' => $request->input('total_amount', 0),
            'status' => $request->input('status', 'completed'),
        ];

        $invoice = SaleInvoice::create($payload);

        $items = $request->input('items', []);
        foreach ($items as $item) {
            $medicine = Medicine::find($item['medicine_id'] ?? null);
            if ($medicine) {
                $medicine->decrement('stock', (int) ($item['quantity'] ?? 0));
                $invoice->items()->create([
                    'medicine_id' => $medicine->id,
                    'quantity' => $item['quantity'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)),
                ]);
            }
        }

        return response()->json($invoice->load('items'), 201);
    }
}
