<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureStatusService;
use Illuminate\Http\JsonResponse;

class FeatureStatusController extends Controller
{
    public function __construct(protected FeatureStatusService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Features loaded successfully.',
            'data' => $this->service->getFeatures(),
        ]);
    }
}
