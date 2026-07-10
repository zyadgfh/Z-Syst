<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExpiredBusinessExport implements FromView
{
    public function view(): View
    {
        return view('admin.reports.expired-business.excel-csv', [
            'businesses' => Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '<', now())->latest()->get()
        ]);
    }
}
