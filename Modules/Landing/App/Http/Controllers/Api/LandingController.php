<?php

namespace Modules\Landing\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Option;
use App\Models\Gateway;
use App\Models\BusinessCategory;
use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Feature;
use Modules\Landing\App\Models\PosAppInterface;
use Modules\Landing\App\Models\Testimonial;

class LandingController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');

        $features = Feature::whereStatus(1)->latest()->get();
        $interfaces = PosAppInterface::whereStatus(1)->latest()->get();
        $testimonials = Testimonial::latest()->get();
        $recent_blogs = Blog::with('user:id,name')->whereStatus(1)->latest()->take(3)->get();
        $blogs = Blog::with('user:id,name')->whereStatus(1)->take(2)->latest()->get();

        $general = Option::where('key', 'general')->first();
        $plans = Plan::whereStatus(1)->latest()->get();
        $gateways = Gateway::latest()->get();
        $business_categories = BusinessCategory::latest()->get();

        return response()->json([
            'page_data' => $page_data,
            'sections' => $page_data['sections'] ?? [],
            'features' => $features,
            'interfaces' => $interfaces,
            'pricing' => $page_data['pricing'] ?? [],
            'testimonials' => $testimonials,
            'recent_blogs' => $recent_blogs,
            'blogs' => $blogs,
            'plans' => $plans,
            'contact' => $page_data['contact'] ?? [],
            'gateways' => $gateways,
            'general' => $general,
            'business_categories' => $business_categories,
        ]);
    }
}
