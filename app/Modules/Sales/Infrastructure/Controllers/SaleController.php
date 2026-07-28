<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Modules\Sales\Application\Services\SaleService;
use App\Modules\Sales\Domain\DTOs\CreateSaleDTO;
use App\Modules\Sales\Infrastructure\Requests\StoreSaleRequest;
use App\Modules\Sales\Infrastructure\Resources\SaleResource;
use App\Services\ReceiptService;
use App\Services\SaleStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sale Controller
 *
 * Thin controller — only orchestrates request/response.
 * All business logic is delegated to SaleService.
 */
class SaleController extends Controller
{
    public function __construct(
        protected SaleService $saleService,
        protected ReceiptService $receiptService,
        protected SaleStatsService $statsService,
    ) {}

    /**
     * Display a paginated listing of sales.
     *
     * @queryParam search string Search by invoice number, customer name, or phone.
     * @queryParam status string Filter by status (completed, partial, cancelled).
     * @queryParam payment_method string Filter by payment method.
     * @queryParam branch_id string Filter by branch.
     * @queryParam date_from string Filter by date range (start).
     * @queryParam date_to string Filter by date range (end).
     * @queryParam per_page int Items per page (default: 15).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = array_merge(
            $request->only([
                'search', 'status', 'payment_method',
                'branch_id', 'customer_id',
                'date_from', 'date_to', 'per_page',
            ]),
            ['company_id' => $request->user()->company_id]
        );

        $sales = $this->saleService->list($filters);

        return response()->json([
            'success' => true,
            'message' => __('Sales retrieved successfully'),
            'data' => SaleResource::collection($sales->items()),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'from' => $sales->firstItem(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'to' => $sales->lastItem(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Store a newly created sale.
     *
     * Validates stock availability, prevents overselling,
     * deducts inventory via FEFO, and logs all movements.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['company_id'] = $request->user()->company_id;
        $data['branch_id'] = $data['branch_id'] ?? $request->user()->branch_id;
        $data['created_by'] = $request->user()->id;

        try {
            $dto = CreateSaleDTO::fromRequest($data);
            $sale = $this->saleService->create($dto);

            return response()->json([
                'success' => true,
                'message' => __('Sale completed successfully'),
                'data' => new SaleResource($sale),
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): JsonResponse
    {
        $sale->loadMissing(['items.product:id,name,product_name,barcode,sales_price', 'createdBy:id,name']);

        return response()->json([
            'success' => true,
            'data' => new SaleResource($sale),
        ]);
    }

    /**
     * Get sales statistics for dashboard.
     *
     * Returns counts and totals for today, this week, and this month.
     */
    public function stats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branchId = $request->get('branch_id');

        $stats = $this->saleService->getStats($companyId, $branchId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Void/cancel a sale.
     *
     * Restores stock via FEFO, logs movements, and marks sale as voided.
     *
     * POST /api/v1/sales/{sale}/void
     */
    public function void(Request $request, Sale $sale): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $voided = $this->saleService->voidSale(
                $sale->id,
                $request->input('reason')
            );

            return response()->json([
                'success' => true,
                'message' => __('Sale voided successfully. Stock has been restored.'),
                'data' => new SaleResource($voided),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get receipt data for a sale (for printing / download).
     *
     * GET /api/v1/sales/{sale}/receipt
     */
    public function receipt(string $sale): JsonResponse
    {
        try {
            $receipt = $this->receiptService->generateReceiptData($sale);

            return response()->json([
                'success' => true,
                'data' => $receipt,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Sale not found'),
            ], 404);
        }
    }

    /**
     * Get receipt HTML for printing.
     *
     * GET /api/v1/sales/{sale}/receipt/print
     */
    public function printReceipt(string $sale): JsonResponse
    {
        try {
            $html = $this->receiptService->generateReceiptHtml($sale);

            return response()->json([
                'success' => true,
                'data' => [
                    'html' => $html,
                    'title' => __('Receipt'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('Sale not found'),
            ], 404);
        }
    }

    /**
     * Get detailed sales dashboard stats.
     *
     * GET /api/v1/sales/stats/dashboard
     */
    public function dashboardStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branchId = $request->get('branch_id');

        $stats = $this->statsService->getDashboardStats($companyId, $branchId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get top-selling products.
     *
     * GET /api/v1/sales/stats/top-products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branchId = $request->get('branch_id');
        $limit = (int) ($request->get('limit', 10));

        $products = $this->statsService->getTopProducts($companyId, $branchId, $limit);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * Get top customers by sales volume.
     *
     * GET /api/v1/sales/stats/top-customers
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branchId = $request->get('branch_id');
        $limit = (int) ($request->get('limit', 10));

        $customers = $this->statsService->getTopCustomers($companyId, $branchId, $limit);

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }

    /**
     * Record a payment for an existing sale.
     *
     * POST /api/v1/sales/{sale}/payments
     */
    public function addPayment(Request $request, Sale $sale): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,card,wallet,insurance,credit,vodafone_cash,instapay'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $updatedSale = $this->saleService->addPayment(
                $sale->id,
                (float) $request->amount,
                $request->payment_method
            );

            // Record the payment in SalePayment table
            SalePayment::create([
                'sale_id' => $sale->id,
                'payment_method' => $request->payment_method,
                'amount' => (float) $request->amount,
                'reference_number' => $request->reference_number,
                'transaction_id' => $request->transaction_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('Payment recorded successfully'),
                'data' => new SaleResource($updatedSale),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

