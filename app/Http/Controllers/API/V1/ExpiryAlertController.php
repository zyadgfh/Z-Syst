<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\ExpiryAlertService;
use App\Services\DemandForecastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpiryAlertController extends Controller
{
    public function __construct(
        private readonly ExpiryAlertService $expiryAlertService,
        private readonly DemandForecastService $demandForecastService
    ) {}

    /**
     * Get products expiring soon
     */
    public function index(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        
        $expiring = $this->expiryAlertService->getExpiringProducts(
            $request->user()->company_id,
            $request->input('branch_id'),
            $days
        );

        return response()->json([
            'success' => true,
            'data' => $expiring,
        ]);
    }

    /**
     * Get expiry statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->expiryAlertService->getExpiryStats(
            $request->user()->company_id,
            $request->input('branch_id')
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Send expiry alerts
     */
    public function sendAlerts(Request $request): JsonResponse
    {
        $result = $this->expiryAlertService->sendExpiryAlerts(
            $request->user()->company_id,
            $request->input('days', 30)
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result,
        ]);
    }

    /**
     * Get reorder suggestions
     */
    public function reorderSuggestions(Request $request): JsonResponse
    {
        $suggestions = $this->demandForecastService->getReorderSuggestions(
            $request->user()->company_id,
            $request->input('branch_id'),
            $request->input('limit', 20)
        );

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get demand forecast
     */
    public function forecast(Request $request): JsonResponse
    {
        $forecast = $this->demandForecastService->getForecast(
            $request->user()->company_id,
            $request->input('days', 30),
            $request->input('branch_id')
        );

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }
}