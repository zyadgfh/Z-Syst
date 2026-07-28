<?php

namespace Modules\Landing\App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Modules\Landing\App\Models\Testimonial;

class ExportTestimonial implements FromView
{
    public function view(): View
    {
        return view('landing::admin.testimonials.excel-csv', [
           'testimonials' => Testimonial::latest()->get()
        ]);
    }
}
