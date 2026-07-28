<?php

namespace Modules\Landing\App\Exports;


use Illuminate\Contracts\View\View;
use Modules\Landing\App\Models\Blog;
use Maatwebsite\Excel\Concerns\FromView;

class ExportBlog implements FromView
{
    public function view(): View
    {
        return view('landing::admin.blogs.excel-csv', [
           'blogs' => Blog::latest()->get()
        ]);
    }
}
