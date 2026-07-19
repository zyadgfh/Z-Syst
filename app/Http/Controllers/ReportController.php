<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\Sale;

class ReportController extends Controller
{
    public function stock()
    {
        $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : null;

        $medicines = Medicine::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
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
        $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : null;

        $invoices = Sale::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->select('id', 'invoice_no as invoice_number', 'total_amount', 'created_at')
            ->latest()
            ->take(10)
            ->get();

        if ($invoices->isEmpty()) {
            $invoices = collect([
                ['id' => 1, 'invoice_number' => 'INV-DEMO-001', 'total_amount' => 180, 'created_at' => now()->toDateTimeString()],
                ['id' => 2, 'invoice_number' => 'INV-DEMO-002', 'total_amount' => 320, 'created_at' => now()->toDateTimeString()],
            ]);
        }

        return response()->json([
            'summary' => [
                'total_sales' => $invoices->count(),
                'total_amount' => $invoices->sum('total_amount'),
            ],
            'items' => $invoices,
        ]);
    }
}
