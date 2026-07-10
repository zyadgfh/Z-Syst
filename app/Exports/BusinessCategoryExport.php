<?php

namespace App\Exports;

use App\Models\BusinessCategory;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;


class BusinessCategoryExport implements FromView
{
    public function view(): View
    {
        return view('admin.business-categories.excel-csv', [
            'categories' => BusinessCategory::latest()->get()
        ]);
    }
}
