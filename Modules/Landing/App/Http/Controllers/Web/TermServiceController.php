<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Option;

class TermServiceController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $generalOption = Option::where('key', 'general')->first();
        $general = $generalOption ? (object) array_merge([
            'value' => [],
        ], (array) $generalOption->toArray()) : (object) ['value' => []];
        $general->value = is_array($general->value) ? $general->value : [];
        $term_condition = Option::where('key', 'term-condition')->first();

        return view('landing::web.term.index', compact('page_data', 'general', 'term_condition'));
    }
}
