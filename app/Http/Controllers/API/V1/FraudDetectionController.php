<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Services\FraudDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FraudDetectionController extends Controller
{
    public function __construct(
        private readonly FraudDetectionService $fraudService
    ) {}

    /**
     * Get fraud alerts
     */
    public function index(Request $request): JsonResponse
    {
        $alerts = $this->fraudService->getFraudAlerts(
            $request->user()->company_id,
            $request->input('days', 30)
        );

        return response()->json([
            'success' => true,
            'data' => $alerts,
        ]);
    }

    /**
     * Get suspicious transactions
     */
    public function suspiciousTransactions(Request $request): JsonResponse
    {
        $transactions = $this->fraudService->getSuspiciousTransactions(
            $request->user()->company_id,
            $request->only(['user_id', 'date_from', 'date_to', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }
}