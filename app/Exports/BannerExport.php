<?php

namespace App\Exports;

use App\Models\Banner;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BannerExport implements FromView
{
    public function view(): View
    {
        return view('admin.banners.excel-csv', [
            'banners' => Banner::latest()->get()
        ]);
    }
}
