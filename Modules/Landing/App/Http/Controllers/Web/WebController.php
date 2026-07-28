<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Models\Plan;
use App\Models\Option;
use App\Models\Gateway;
use App\Models\BusinessCategory;
use App\Http\Controllers\Controller;
use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Feature;
use Modules\Landing\App\Models\Testimonial;
use Modules\Landing\App\Models\PosAppInterface;
use Illuminate\Support\Arr;

class WebController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');
        $features = Feature::whereStatus(1)->latest()->get();
        $interfaces = PosAppInterface::whereStatus(1)->latest()->get();
        $testimonials = Testimonial::latest()->get();
        $recent_blogs = Blog::with('user:id,name')->whereStatus(1)->latest()->take(3)->get();
        $blogs = Blog::with('user:id,name')->whereStatus(1)->take(2)->latest()->get();
        $generalOption = Option::where('key', 'general')->first();
        $general = $generalOption ? (object) array_merge([
            'value' => [],
        ], (array) $generalOption->toArray()) : (object) ['value' => []];
        $general->value = is_array($general->value) ? $general->value : [];
        $plans = Plan::whereStatus(1)->latest()->get();
        $gateways = Gateway::latest()->get();
        $business_categories = BusinessCategory::latest()->get();

        return view('landing::web.home.index', compact('page_data', 'features', 'interfaces', 'testimonials', 'blogs', 'recent_blogs', 'plans', 'gateways', 'general', 'business_categories'));
    }
}
