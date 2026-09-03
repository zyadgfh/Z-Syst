<?php

namespace App\Modules\Reports\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Reports\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function salesReport(Request $request): JsonResponse|Response
    {
        $data = $request->all();
        $format = $data['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->reportService->generateSalesReportPdf($data, auth()->user()->business_id);
        }

        if ($format === 'excel') {
            return $this->reportService->generateSalesReportExcel($data, auth()->user()->business_id);
        }

        $report = $this->reportService->getSalesReport($data, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function purchaseReport(Request $request): JsonResponse|Response
    {
        $data = $request->all();
        $format = $data['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->reportService->generatePurchaseReportPdf($data, auth()->user()->business_id);
        }

        if ($format === 'excel') {
            return $this->reportService->generatePurchaseReportExcel($data, auth()->user()->business_id);
        }

        $report = $this->reportService->getPurchaseReport($data, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function lossProfitReport(Request $request): JsonResponse|Response
    {
        $data = $request->all();
        $format = $data['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->reportService->generateLossProfitReportPdf($data, auth()->user()->business_id);
        }

        if ($format === 'excel') {
            return $this->reportService->generateLossProfitReportExcel($data, auth()->user()->business_id);
        }

        $report = $this->reportService->getLossProfitReport($data, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function stockAuditReport(Request $request): JsonResponse|Response
    {
        $data = $request->all();
        $format = $data['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->reportService->generateStockAuditReportPdf($data, auth()->user()->business_id);
        }

        if ($format === 'excel') {
            return $this->reportService->generateStockAuditReportExcel($data, auth()->user()->business_id);
        }

        $report = $this->reportService->getStockAuditReport($data, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function financialAuditReport(Request $request): JsonResponse|Response
    {
        $data = $request->all();
        $format = $data['format'] ?? 'json';

        if ($format === 'pdf') {
            return $this->reportService->generateFinancialAuditReportPdf($data, auth()->user()->business_id);
        }

        if ($format === 'excel') {
            return $this->reportService->generateFinancialAuditReportExcel($data, auth()->user()->business_id);
        }

        $report = $this->reportService->getFinancialAuditReport($data, auth()->user()->business_id);
        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }
}
