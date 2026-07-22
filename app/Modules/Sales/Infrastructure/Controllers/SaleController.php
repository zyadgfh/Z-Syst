<?php

declare(strict_types=1);

namespace App\Modules\Sales\Infrastructure\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Modules\Sales\Application\Services\SaleService;
use App\Modules\Sales\Domain\DTOs\CreateSaleDTO;
use App\Modules\Sales\Infrastructure\Requests\StoreSaleRequest;
use App\Modules\Sales\Infrastructure\Resources\SaleResource;
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
        protected SaleService $saleService
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
     * Get receipt data for a sale (for printing / download).
     */
    public function receipt(string $sale): JsonResponse
    {
        try {
            $receipt = $this->saleService->getReceipt($sale);

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
     * Record a payment for an existing sale.
     */
    public function addPayment(Request $request, Sale $sale): JsonResponse
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'in:cash,card,wallet,insurance,credit'],
        ]);

        try {
            $sale = $this->saleService->addPayment(
                $sale->id,
                (float) $request->amount,
                $request->payment_method
            );

            return response()->json([
                'success' => true,
                'message' => __('Payment recorded successfully'),
                'data' => new SaleResource($sale),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

