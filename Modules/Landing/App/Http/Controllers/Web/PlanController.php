<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Models\Plan;
use App\Models\Option;
use App\Models\BusinessCategory;
use App\Http\Controllers\Controller;

class PlanController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $general = Option::where('key','general')->first();
        $plans = Plan::where('status',1)->latest()->get();
        $business_categories = BusinessCategory::latest()->get();

        return view('landing::web.plan.index', compact('page_data', 'general','plans', 'business_categories'));
    }
}
