<?php

namespace Modules\Landing\App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Landing\App\Models\Blog;

class ExportBlog implements FromView
{
    public function view(): View
    {
        return view('landing::admin.blogs.excel-csv', [
            'blogs' => Blog::latest()->get(),
        ]);
    }
}
