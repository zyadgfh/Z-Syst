<?php

namespace Modules\Landing\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\Landing\App\Services\LandingService;

class LandingController extends Controller
{
    protected LandingService $landingService;

    public function __construct(LandingService $landingService)
    {
        $this->landingService = $landingService;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->landingService->getLandingData(),
            'meta' => [
                'version' => '1.0',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get pricing page data
     */
    public function pricing()
    {
        return response()->json([
            'success' => true,
            'data' => $this->landingService->getPricingData(),
        ]);
    }

    /**
     * Get features page data
     */
    public function features()
    {
        return response()->json([
            'success' => true,
            'data' => $this->landingService->getFeaturesData(),
        ]);
    }

    /**
     * Get contact page data
     */
    public function contact()
    {
        return response()->json([
            'success' => true,
            'data' => $this->landingService->getContactData(),
        ]);
    }
}
