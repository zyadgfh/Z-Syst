<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PredictionSetting;
use App\Models\Product;
use App\Models\SalesForecast;
use App\Services\PredictionService;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    protected PredictionService $predictionService;

    public function __construct(PredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    /**
     * Get prediction settings for current business.
     */
    public function settings()
    {
        $businessId = auth()->user()->business_id;
        $settings = PredictionSetting::getForBusiness($businessId);

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $settings,
        ]);
    }

    /**
     * Update prediction settings.
     */
    public function updateSettings(Request $request)
    {
        $businessId = auth()->user()->business_id;

        $request->validate([
            'prediction_enabled' => 'boolean',
            'forecast_period' => 'in:daily,weekly,monthly',
            'forecast_days' => 'integer|min:1|max:365',
            'historical_months' => 'integer|min:1|max:24',
            'prediction_method' => 'in:moving_average,weighted_moving_average,exponential_smoothing,combined',
            'seasonal_adjustment' => 'boolean',
            'safety_stock_multiplier' => 'numeric|min:0.5|max:5',
            'lead_time_days' => 'integer|min:1|max:90',
            'confidence_threshold' => 'numeric|min:0|max:1',
            'auto_order_enabled' => 'boolean',
        ]);

        $settings = PredictionSetting::updateOrCreate(
            ['business_id' => $businessId],
            $request->only([
                'prediction_enabled',
                'forecast_period',
                'forecast_days',
                'historical_months',
                'prediction_method',
                'seasonal_adjustment',
                'safety_stock_multiplier',
                'lead_time_days',
                'confidence_threshold',
                'auto_order_enabled',
            ])
        );

        return response()->json([
            'message' => __('Settings updated successfully.'),
            'data' => $settings,
        ]);
    }

    /**
     * Get forecast for a single product.
     */
    public function forecastProduct(Request $request, $productId)
    {
        $businessId = auth()->user()->business_id;
        $forecastDays = $request->days;

        // Ensure the product belongs to this business
        $product = Product::where('id', $productId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $result = $this->predictionService->forecastProduct($productId, $businessId, $forecastDays);

        return response()->json([
            'message' => __('Forecast generated successfully.'),
            'data' => $result,
        ]);
    }

    /**
     * Get forecast for multiple products (batch).
     */
    public function batchForecast(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $businessId = auth()->user()->business_id;
        $productIds = $request->product_ids;

        $result = $this->predictionService->batchForecast($productIds, $businessId);

        return response()->json([
            'message' => __('Batch forecast completed.'),
            'data' => $result,
        ]);
    }

    /**
     * Forecast all products.
     */
    public function forecastAll()
    {
        $businessId = auth()->user()->business_id;

        $result = $this->predictionService->forecastAllProducts($businessId);

        return response()->json([
            'message' => __('Forecast for all products completed.'),
            'data' => [
                'products_forecasted' => count($result),
                'results' => $result,
            ],
        ]);
    }

    /**
     * Get demand report.
     */
    public function demandReport(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $period = $request->period ?? 'daily';

        $report = $this->predictionService->getDemandReport($businessId, $period);

        return response()->json([
            'message' => __('Demand report fetched successfully.'),
            'data' => $report,
        ]);
    }

    /**
     * Get reorder point calculation for a product.
     */
    public function reorderPoint($productId)
    {
        $businessId = auth()->user()->business_id;

        $product = Product::where('id', $productId)
            ->where('business_id', $businessId)
            ->firstOrFail();

        $result = $this->predictionService->calculateReorderPoint($productId, $businessId);

        return response()->json([
            'message' => __('Reorder point calculated.'),
            'data' => $result,
        ]);
    }

    /**
     * Get saved forecasts for a product.
     */
    public function getForecasts(Request $request, $productId)
    {
        $businessId = auth()->user()->business_id;

        $query = SalesForecast::where('business_id', $businessId)
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->orderBy('forecast_date', 'asc');

        // Date range filter
        if ($request->from_date) {
            $query->where('forecast_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('forecast_date', '<=', $request->to_date);
        }

        $forecasts = $query->get();

        return response()->json([
            'message' => __('Forecasts fetched successfully.'),
            'data' => [
                'product_id' => (int) $productId,
                'forecasts' => $forecasts,
                'summary' => [
                    'total_predicted_qty' => round($forecasts->sum('predicted_quantity'), 2),
                    'total_predicted_revenue' => round($forecasts->sum('predicted_revenue'), 2),
                    'avg_confidence' => round($forecasts->avg('confidence_score'), 1),
                ],
            ],
        ]);
    }
}
