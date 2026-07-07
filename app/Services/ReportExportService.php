<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportExportService
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Export sales report to CSV
     */
    public function exportSalesReport($startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $data = [
            ['Sales Report'],
            ['Period', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')],
            ['Generated', Carbon::now()->format('Y-m-d H:i:s')],
            [],
            ['Date', 'Invoice Number', 'Total Amount', 'Payment Method', 'Payment Status']
        ];

        $sales = \App\Models\Sale::where('company_id', auth()->user()->company_id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at')
            ->get();

        foreach ($sales as $sale) {
            $data[] = [
                $sale->created_at->format('Y-m-d H:i:s'),
                $sale->invoice_number,
                $sale->total_amount,
                $sale->payment_method,
                $sale->payment_status,
            ];
        }

        return $this->generateCSV($data, 'sales_report');
    }

    /**
     * Export inventory report to CSV
     */
    public function exportInventoryReport()
    {
        $summary = $this->analyticsService->getInventorySummary();

        $data = [
            ['Inventory Summary Report'],
            ['Generated', Carbon::now()->format('Y-m-d H:i:s')],
            [],
            ['Metric', 'Value'],
            ['Total Products', $summary['total_products']],
            ['Total Stock Items', $summary['total_stock_items']],
            ['Total Quantity', $summary['total_quantity']],
            ['Total Stock Value', $summary['total_stock_value']],
            ['Total Retail Value', $summary['total_retail_value']],
            ['Low Stock Count', $summary['low_stock_count']],
            ['Out of Stock Count', $summary['out_of_stock_count']],
            ['Overstock Count', $summary['overstock_count']],
            [],
            ['Low Stock Products'],
            ['Product Name', 'SKU', 'Branch', 'Current Quantity', 'Reorder Level', 'Reorder Quantity', 'Urgency']
        ];

        $lowStockProducts = $this->analyticsService->getLowStockProducts(100);

        foreach ($lowStockProducts as $product) {
            $data[] = [
                $product['product_name'],
                $product['sku'] ?? '',
                $product['branch_name'],
                $product['current_quantity'],
                $product['reorder_level'],
                $product['reorder_quantity'],
                $product['urgency'],
            ];
        }

        return $this->generateCSV($data, 'inventory_report');
    }

    /**
     * Export expiring products report to CSV
     */
    public function exportExpiringProductsReport($days = 30)
    {
        $data = [
            ['Expiring Products Report'],
            ['Expiring Within', $days . ' days'],
            ['Generated', Carbon::now()->format('Y-m-d H:i:s')],
            [],
            ['Product Name', 'SKU', 'Branch', 'Batch Number', 'Quantity', 'Expiry Date', 'Days Until Expiry', 'Value']
        ];

        $expiringProducts = $this->analyticsService->getExpiringProducts($days, 100);

        foreach ($expiringProducts as $product) {
            $data[] = [
                $product['product_name'],
                $product['sku'] ?? '',
                $product['branch_name'],
                $product['batch_number'] ?? '',
                $product['quantity'],
                $product['expiry_date'],
                $product['days_until_expiry'],
                $product['value'],
            ];
        }

        return $this->generateCSV($data, 'expiring_products_report');
    }

    /**
     * Export top selling products report to CSV
     */
    public function exportTopSellingProductsReport($limit = 20, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $data = [
            ['Top Selling Products Report'],
            ['Period', $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')],
            ['Generated', Carbon::now()->format('Y-m-d H:i:s')],
            [],
            ['Product Name', 'SKU', 'Total Quantity Sold', 'Total Revenue', 'Total Sales']
        ];

        $products = $this->analyticsService->getTopSellingProducts($limit, $startDate, $endDate);

        foreach ($products as $product) {
            $data[] = [
                $product['product_name'],
                $product['sku'] ?? '',
                $product['total_quantity'],
                $product['total_revenue'],
                $product['total_sales'],
            ];
        }

        return $this->generateCSV($data, 'top_selling_products_report');
    }

    /**
     * Export stock turnover report to CSV
     */
    public function exportStockTurnoverReport($days = 90)
    {
        $data = [
            ['Stock Turnover Report'],
            ['Period', 'Last ' . $days . ' days'],
            ['Generated', Carbon::now()->format('Y-m-d H:i:s')],
            [],
            ['Product Name', 'SKU', 'Current Stock', 'Sold in Period', 'Turnover Rate', 'Days of Supply', 'Category']
        ];

        $turnover = $this->analyticsService->getStockTurnover($days);

        foreach ($turnover as $item) {
            $data[] = [
                $item['product_name'],
                '', // SKU would need to be added to turnover data
                $item['current_stock'],
                $item['sold_period'],
                $item['turnover_rate'],
                $item['days_of_supply'],
                $item['turnover_category'],
            ];
        }

        return $this->generateCSV($data, 'stock_turnover_report');
    }

    /**
     * Generate CSV file from data array
     */
    protected function generateCSV($data, $filename)
    {
        $filename = $filename . '_' . Carbon::now()->format('Y-m-d_His') . '.csv';
        $filepath = 'exports/' . $filename;

        $csv = fopen('php://temp', 'r+');

        foreach ($data as $row) {
            fputcsv($csv, $row);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        // Store the file
        Storage::disk('local')->put($filepath, $csvContent);

        return [
            'filename' => $filename,
            'path' => $filepath,
            'url' => Storage::disk('local')->url($filepath),
            'size' => strlen($csvContent),
        ];
    }

    /**
     * Delete old export files (older than 7 days)
     */
    public function cleanupOldExports()
    {
        $files = Storage::disk('local')->files('exports');

        foreach ($files as $file) {
            if (Storage::disk('local')->lastModified($file) < Carbon::now()->subDays(7)->timestamp) {
                Storage::disk('local')->delete($file);
            }
        }
    }
}