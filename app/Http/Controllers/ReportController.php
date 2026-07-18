<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\SaleInvoice;

class ReportController extends Controller
{
    public function stock()
    {
        $medicines = Medicine::query()
            ->select('id', 'name', 'stock', 'minimum_stock', 'expiry_date')
            ->orderBy('stock')
            ->get();

        return response()->json([
            'summary' => [
                'total_items' => $medicines->count(),
                'low_stock_items' => $medicines->filter(fn ($item) => $item->stock <= $item->minimum_stock)->count(),
            ],
            'items' => $medicines,
        ]);
    }

    public function sales()
    {
        $invoices = SaleInvoice::query()
            ->select('id', 'invoice_number', 'total_amount', 'created_at')
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'summary' => [
                'total_sales' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
            ],
            'items' => $invoices,
        ]);
    }
}
