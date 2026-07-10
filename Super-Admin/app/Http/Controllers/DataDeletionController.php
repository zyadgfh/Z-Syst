<?php

namespace App\Http\Controllers;

use App\Models\Option;
use Illuminate\Http\Request;

class DataDeletionController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $general = Option::where('key','general')->first();

        return view('web.data-deletion.index', compact('page_data', 'general'));
    }
}
