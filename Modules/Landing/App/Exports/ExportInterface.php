<?php

namespace Modules\Landing\App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Landing\App\Models\PosAppInterface;

class ExportInterface implements FromView
{
    public function view(): View
    {
        return view('landing::admin.interfaces.excel-csv', [
           'interfaces' => PosAppInterface::latest()->get()
        ]);
    }
}
