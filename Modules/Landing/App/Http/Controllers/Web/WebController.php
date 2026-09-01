<?php

namespace Modules\Landing\App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BusinessCategory;
use App\Models\Gateway;
use App\Models\Option;
use App\Models\Plan;
use App\Models\Product;
use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Feature;
use Modules\Landing\App\Models\PosAppInterface;
use Modules\Landing\App\Models\Testimonial;

class WebController extends Controller
{
    public function index()
    {
        $page_data = get_option('manage-pages');

        try {
            $features = Feature::whereStatus(1)->latest()->get();
            $interfaces = PosAppInterface::whereStatus(1)->latest()->get();
            $testimonials = Testimonial::latest()->get();
            $recent_blogs = Blog::with('user:id,name')->whereStatus(1)->latest()->take(3)->get();
            $blogs = Blog::with('user:id,name')->whereStatus(1)->take(2)->latest()->get();
            $featured_products = Product::active()
                ->with(['category', 'manufacturer'])
                ->select([
                    'id', 'productName', 'scientific_name', 'commercial_name',
                    'description', 'category_id', 'manufacturer_id',
                    'sales_price', 'images', 'stock_status',
                    'prescription_required', 'dosage_form', 'strength',
                ])
                ->orderByDesc('created_at')
                ->take(6)
                ->get();
        } catch (\Throwable $e) {
            $features = collect();
            $interfaces = collect();
            $testimonials = collect();
            $recent_blogs = collect();
            $blogs = collect();
            $featured_products = collect();
        }

        try {
            $general = Option::where('key', 'general')->first();
            $plans = Plan::whereStatus(1)->latest()->get();
            $gateways = Gateway::latest()->get();
            $business_categories = BusinessCategory::latest()->get();
        } catch (\Throwable $e) {
            $general = null;
            $plans = collect();
            $gateways = collect();
            $business_categories = collect();
        }

        return view('landing::web.home.index', compact('page_data', 'features', 'interfaces', 'testimonials', 'blogs', 'recent_blogs', 'plans', 'gateways', 'general', 'business_categories', 'featured_products'));
    }
}
