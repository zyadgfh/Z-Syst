<?php

namespace Modules\Landing\App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Landing\App\Models\Feature;

class ExportFeature implements FromView
{
    public function view(): View
    {
        return view('landing::admin.features.excel-csv', [
            'features' => Feature::latest()->get(),
        ]);
    }
}
