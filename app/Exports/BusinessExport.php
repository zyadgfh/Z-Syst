<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BusinessExport implements FromView
{
    public function view(): View
    {
        return view('admin.business.excel-csv', [
            'businesses' => Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->latest()->get()
        ]);
    }
}
