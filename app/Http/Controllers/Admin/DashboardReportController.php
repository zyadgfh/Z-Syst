<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardReportService;
use Illuminate\Http\Request;

class DashboardReportController extends Controller
{
    protected DashboardReportService $dashboardReportService;

    public function __construct(DashboardReportService $dashboardReportService)
    {
        $this->dashboardReportService = $dashboardReportService;
        $this->middleware('permission:reports-read')->only('index', 'show');
    }

    /**
     * Get overall statistics
     */
    public function overall(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->dashboardReportService->getOverallStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get sales by date chart data
     */
    public function salesByDate(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'group_by' => $request->group_by ?? 'day',
        ];

        $data = $this->dashboardReportService->getSalesByDate($filters);

        return response()->json($data);
    }

    /**
     * Get top selling products
     */
    public function topProducts(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'limit' => $request->limit ?? 10,
        ];

        $products = $this->dashboardReportService->getTopSellingProducts($filters);

        return response()->json($products);
    }

    /**
     * Get top customers
     */
    public function topCustomers(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'limit' => $request->limit ?? 10,
        ];

        $customers = $this->dashboardReportService->getTopCustomers($filters);

        return response()->json($customers);
    }

    /**
     * Get profit and loss report
     */
    public function profitLoss(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $report = $this->dashboardReportService->getProfitAndLoss($filters);

        return response()->json($report);
    }

    /**
     * Get warehouse statistics
     */
    public function warehouse(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->dashboardReportService->getWarehouseStatistics($businessId);

        return response()->json($statistics);
    }

    /**
     * Get transfer statistics
     */
    public function transfers(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->dashboardReportService->getTransferStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get recall statistics
     */
    public function recalls(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->dashboardReportService->getRecallStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get loyalty statistics
     */
    public function loyalty(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->dashboardReportService->getLoyaltyStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get receipt statistics
     */
    public function receipts(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $statistics = $this->dashboardReportService->getReceiptStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get comprehensive dashboard report
     */
    public function comprehensive(Request $request)
    {
        $filters = [
            'business_id' => $request->business_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'group_by' => $request->group_by ?? 'day',
            'limit' => $request->limit ?? 10,
        ];

        $report = $this->dashboardReportService->getComprehensiveReport($filters);

        return response()->json($report);
    }

    /**
     * Show dashboard reports page
     */
    public function index()
    {
        return view('admin.reports.dashboard');
    }
}
