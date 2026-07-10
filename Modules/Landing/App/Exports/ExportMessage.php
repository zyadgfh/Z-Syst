<?php

namespace Modules\Landing\App\Exports;


use Illuminate\Contracts\View\View;
use Modules\Landing\App\Models\Message;
use Maatwebsite\Excel\Concerns\FromView;

class ExportMessage implements FromView
{
    public function view(): View
    {
        return view('landing::admin.messages.excel-csv', [
           'messages' => Message::latest()->get()
        ]);
    }
}
