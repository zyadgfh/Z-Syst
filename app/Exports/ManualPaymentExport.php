<?php

namespace App\Exports;

use App\Models\PlanSubscribe;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ManualPaymentExport implements FromView
{
    public function view(): View
    {
        return view('admin.manual-payments.excel-csv', [
            'manual_payments' => PlanSubscribe::with([
                'plan:id,subscriptionName',
                'business:id,company_name,picture_url,business_category_id',
                'business.category:id,name',
                'gateway:id,name',
            ])->whereHas('gateway', function ($query) {
                $query->where('name', 'Manual');
            })->latest()->get(),
        ]);
    }
}
