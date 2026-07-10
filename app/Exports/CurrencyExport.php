<?php

namespace App\Exports;

use App\Models\Currency;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CurrencyExport implements FromView
{
    public function view(): View
    {
        return view('admin.currencies.excel-csv', [
            'currencies' => Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')->latest()->get()
        ]);
    }
}
