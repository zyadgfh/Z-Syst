<?php

namespace Modules\ZSyst\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\ZSyst\App\Models\Customer;
use Modules\ZSyst\App\Models\Drug;
use Modules\ZSyst\App\Models\InventoryItem;
use Modules\ZSyst\App\Models\PosSale;
use Modules\ZSyst\App\Models\ProcurementOrder;
use Modules\ZSyst\App\Models\Supplier;

class DashboardController
{
    public function index(): JsonResponse
    {
        return response()->json([
            'module' => 'ZSyst',
            'status' => 'ready',
            'phase' => 'core-foundation',
            'summary' => [
                'drugs' => Drug::count(),
                'customers' => Customer::count(),
                'suppliers' => Supplier::count(),
                'inventory_items' => InventoryItem::count(),
                'pos_sales' => PosSale::count(),
                'procurement_orders' => ProcurementOrder::count(),
            ],
            'latest_sales' => PosSale::latest()->take(5)->get(),
            'low_stock_items' => InventoryItem::where('quantity_on_hand', '<=', 10)->latest()->take(5)->get(),
        ]);
    }
}
