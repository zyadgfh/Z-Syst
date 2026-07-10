<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Models\Option;
use App\Http\Controllers\Controller;


class AboutController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $general = Option::where('key','general')->first();

        return view('landing::web.about.index',compact('page_data','general'));
    }
}
