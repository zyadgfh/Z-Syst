<?php

namespace Modules\Landing\App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Landing\App\Models\Message;

class ExportMessage implements FromView
{
    public function view(): View
    {
        return view('landing::admin.messages.excel-csv', [
            'messages' => Message::latest()->get(),
        ]);
    }
}
