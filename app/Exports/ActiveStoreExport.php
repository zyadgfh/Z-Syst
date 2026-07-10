<?php

namespace App\Exports;

use App\Models\Business;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ActiveStoreExport implements FromView
{
    public function view(): View
    {
        return view('admin.reports.active-stores.excel-csv', [
            'active_stores' => Business::with('enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name')->whereDate('will_expire', '>', now())->latest()->get()
        ]);
    }
}
