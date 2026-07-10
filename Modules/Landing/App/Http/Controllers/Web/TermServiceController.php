<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Models\Option;
use App\Http\Controllers\Controller;

class TermServiceController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $general = Option::where('key','general')->first();
        $term_condition = Option::where('key', 'term-condition')->first();
        return view('landing::web.term.index',compact('page_data','general','term_condition'));
    }
}
