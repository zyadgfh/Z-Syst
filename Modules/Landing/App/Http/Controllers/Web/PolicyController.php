<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Models\Option;
use App\Http\Controllers\Controller;

class PolicyController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $generalOption = Option::where('key', 'general')->first();
        $general = $generalOption ? (object) array_merge([
            'value' => [],
        ], (array) $generalOption->toArray()) : (object) ['value' => []];
        $general->value = is_array($general->value) ? $general->value : [];
        $privacy_policy = Option::where('key', 'privacy-policy')->first();
        return view('landing::web.policy.index', compact('page_data','general','privacy_policy'));
    }
}
