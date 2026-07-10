<?php

namespace App\Exports;

use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PlanExport implements FromView
{
    public function view(): View
    {
        return view('admin.plans.excel-csv', [
            'plans' => Plan::latest()->get()
        ]);
    }
}
