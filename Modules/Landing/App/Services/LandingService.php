<?php

namespace Modules\Landing\App\Services;

use App\Models\Option;
use App\Models\Plan;
use App\Models\BusinessCategory;
use App\Models\Gateway;
use Modules\Landing\App\Models\Blog;
use Modules\Landing\App\Models\Feature;
use Modules\Landing\App\Models\PosAppInterface;
use Modules\Landing\App\Models\Testimonial;

class LandingService
{
    /**
     * Get complete landing page data
     */
    public function getLandingData(): array
    {
        $pageData = $this->getPageData();
        
        return [
            'page_data' => $pageData,
            'sections' => $pageData['sections'] ?? [],
            'features' => $this->getActiveFeatures(),
            'interfaces' => $this->getActiveInterfaces(),
            'pricing' => $pageData['pricing'] ?? [],
            'testimonials' => $this->getTestimonials(),
            'recent_blogs' => $this->getRecentBlogs(3),
            'blogs' => $this->getRecentBlogs(2),
            'plans' => $this->getActivePlans(),
            'contact' => $pageData['contact'] ?? [],
            'gateways' => $this->getGateways(),
            'general' => $this->getGeneralSettings(),
            'business_categories' => $this->getBusinessCategories(),
        ];
    }

    /**
     * Get pricing page data
     */
    public function getPricingData(): array
    {
        return [
            'plans' => $this->getActivePlansWithDiscounts(),
            'page_data' => $this->getPageData(),
            'pricing_section' => $this->getPageData()['pricing'] ?? [],
        ];
    }

    /**
     * Get features page data
     */
    public function getFeaturesData(): array
    {
        return [
            'features' => $this->getActiveFeatures(),
            'interfaces' => $this->getActiveInterfaces(),
        ];
    }

    /**
     * Get contact page data
     */
    public function getContactData(): array
    {
        $pageData = $this->getPageData();
        
        return [
            'contact' => $pageData['contact'] ?? [],
            'general' => $this->getGeneralSettings(),
        ];
    }

    /**
     * Get page data from options
     */
    protected function getPageData(): array
    {
        return get_option('manage-pages') ?? [];
    }

    /**
     * Get active features
     */
    protected function getActiveFeatures()
    {
        return Feature::whereStatus(1)->latest()->get();
    }

    /**
     * Get active interfaces
     */
    protected function getActiveInterfaces()
    {
        return PosAppInterface::whereStatus(1)->latest()->get();
    }

    /**
     * Get testimonials
     */
    protected function getTestimonials()
    {
        return Testimonial::latest()->get();
    }

    /**
     * Get recent blogs
     */
    protected function getRecentBlogs(int $limit)
    {
        return Blog::with('user:id,name')
            ->whereStatus(1)
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Get active plans
     */
    protected function getActivePlans()
    {
        return Plan::whereStatus(1)->latest()->get()->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->subscriptionName,
                'duration' => $plan->duration,
                'price' => $plan->subscriptionPrice,
                'offer_price' => $plan->offerPrice,
                'features' => $plan->features ?? [],
                'status' => $plan->status,
            ];
        });
    }

    /**
     * Get active plans with discount calculations
     */
    protected function getActivePlansWithDiscounts()
    {
        return Plan::whereStatus(1)->latest()->get()->map(function ($plan) {
            $discountPercentage = 0;
            if ($plan->offerPrice && $plan->subscriptionPrice > 0) {
                $discountPercentage = round((($plan->subscriptionPrice - $plan->offerPrice) / $plan->subscriptionPrice) * 100);
            }

            return [
                'id' => $plan->id,
                'name' => $plan->subscriptionName,
                'duration' => $plan->duration,
                'duration_text' => $plan->duration . ' days',
                'price' => $plan->subscriptionPrice,
                'offer_price' => $plan->offerPrice,
                'discount_percentage' => $discountPercentage,
                'features' => $plan->features ?? [],
                'status' => $plan->status,
                'is_popular' => false, // Can be configured from admin
            ];
        });
    }

    /**
     * Get gateways
     */
    protected function getGateways()
    {
        return Gateway::latest()->get()->map(function ($gateway) {
            return [
                'id' => $gateway->id,
                'name' => $gateway->name,
                'image' => $gateway->image,
                'status' => $gateway->status ?? 1,
            ];
        });
    }

    /**
     * Get general settings
     */
    protected function getGeneralSettings()
    {
        return Option::where('key', 'general')->first();
    }

    /**
     * Get business categories
     */
    protected function getBusinessCategories()
    {
        return BusinessCategory::latest()->get()->map(function ($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'status' => $category->status,
            ];
        });
    }
}
