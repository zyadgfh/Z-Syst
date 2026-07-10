<?php

namespace App\Exports;

use App\Models\PlanSubscribe;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SubscriptionReportExport implements FromView
{
    public function view(): View
    {
        return view('admin.subscribers.excel-csv', [
            'subscribers' => PlanSubscribe::with(['plan:id,subscriptionName','business:id,companyName,pictureUrl,business_category_id','business.category:id,name'])->latest()->get()
        ]);
    }
}
