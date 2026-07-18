<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;

class DashboardController extends Controller
{
    public function index()
    {
        return view('pharmacy-dashboard', [
            'medicineCount' => Medicine::count(),
            'supplierCount' => Supplier::count(),
            'customerCount' => Customer::count(),
            'purchaseCount' => PurchaseOrder::count(),
        ]);
    }
}
