<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Option;

class AboutController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $generalOption = Option::where('key', 'general')->first();
        $general = $generalOption ? (object) array_merge([
            'value' => [],
        ], (array) $generalOption->toArray()) : (object) ['value' => []];
        $general->value = is_array($general->value) ? $general->value : [];

        return view('landing::web.about.index', compact('page_data', 'general'));
    }
}
