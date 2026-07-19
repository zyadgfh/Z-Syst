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
        $companyId = app()->bound('tenant.company_id') ? app('tenant.company_id') : null;

        return view('pharmacy-dashboard', [
            'medicineCount' => Medicine::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->count(),
            'supplierCount' => Supplier::query()->when($companyId, fn ($query) => $query->where('company_id', $companyId))->count(),
            'customerCount' => $this->safeCount(Customer::class),
            'purchaseCount' => $this->safeCount(PurchaseOrder::class),
        ]);
    }

    protected function safeCount(string $model): int
    {
        try {
            $query = app($model)::query();

            if (app()->bound('tenant.company_id')) {
                $query->where('company_id', app('tenant.company_id'));
            }

            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
