<?php

namespace Modules\Landing\App\Exports;


use Illuminate\Contracts\View\View;
use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Feature;
use Maatwebsite\Excel\Concerns\FromView;

class ExportFeature implements FromView
{
    public function view(): View
    {
        return view('landing::admin.features.excel-csv', [
           'features' => Feature::latest()->get()
        ]);
    }
}
