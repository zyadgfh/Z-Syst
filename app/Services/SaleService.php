<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Models\Business;
use App\Models\FefoSetting;
use App\Models\Party;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Services\FefoService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class SaleService
{
    use WithTransactionalOperations;

    protected FefoService $fefoService;

    public function __construct(FefoService $fefoService)
    {
        $this->fefoService = $fefoService;
    }

    /**
     * Create a new sale with business logic validation.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @param int $userId
     * @return Sale
     * @throws \Exception
     */
    public function createSale(array $data, int $businessId, int $userId): Sale
    {
        return $this->executeTransaction(function () use ($data, $businessId, $userId) {
            $fefoSettings = FefoSetting::getForBusiness($businessId);

            // Load business stocks for validation
            $productIds = collect($data['products'])->pluck('product_id')->filter()->unique()->values()->all();
            $businessStocks = $this->loadBusinessStocks($businessId, $productIds);

            // Validate stock availability
            $this->validateStockAvailability($data['products'], $businessStocks, $fefoSettings, $businessId);

            // Validate due sale for walking customers
            if (($data['dueAmount'] ?? 0) > 0 && !($data['party_id'] ?? null)) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_DUE_SALE_WALKING_CUSTOMER,
                    'Due sales are not allowed for walking customers',
                    ['due_amount' => $data['dueAmount']]
                );
            }

            // Update party due amount if applicable
            if (($data['dueAmount'] ?? 0) > 0 && ($data['party_id'] ?? null)) {
                $party = Party::findOrFail($data['party_id']);
                $party->update([
                    'due' => $party->due + $data['dueAmount'],
                ]);
            }

            // Update business balance
            $business = Business::findOrFail($businessId);
            $business->update([
                'remainingShopBalance' => $business->remainingShopBalance - ($data['paidAmount'] ?? 0),
            ]);

            // Create sale
            $sale = Sale::create([
                'business_id' => $businessId,
                'party_id' => $data['party_id'] ?? null,
                'user_id' => $userId,
                'tax_id' => $data['tax_id'] ?? null,
                'discountAmount' => $data['discountAmount'] ?? 0,
                'dueAmount' => $data['dueAmount'] ?? 0,
                'isPaid' => $data['isPaid'] ?? false,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'paidAmount' => $data['paidAmount'] ?? 0,
                'totalAmount' => $data['totalAmount'] ?? 0,
                'lossProfit' => $data['lossProfit'] ?? 0,
                'paymentType' => $data['paymentType'] ?? 'Cash',
                'invoiceNumber' => $this->generateInvoiceNumber($businessId),
                'saleDate' => $data['saleDate'] ?? now(),
                'meta' => $data['meta'] ?? null,
            ]);

            // Create sale details and deduct stock
            $this->processSaleItems($sale, $data['products'], $businessStocks, $fefoSettings, $businessId);

            return $sale->fresh(['details.product', 'party', 'tax']);
        });
    }

    /**
     * Update an existing sale.
     *
     * @param Sale $sale
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Sale
     * @throws \Exception
     */
    public function updateSale(Sale $sale, array $data, int $businessId): Sale
    {
        return $this->executeTransaction(function () use ($sale, $data, $businessId) {
            // Restore previous stock
            $this->restoreSaleStock($sale);

            // Update sale
            $sale->update([
                'party_id' => $data['party_id'] ?? $sale->party_id,
                'tax_id' => $data['tax_id'] ?? $sale->tax_id,
                'discountAmount' => $data['discountAmount'] ?? $sale->discountAmount,
                'dueAmount' => $data['dueAmount'] ?? $sale->dueAmount,
                'isPaid' => $data['isPaid'] ?? $sale->isPaid,
                'tax_amount' => $data['tax_amount'] ?? $sale->tax_amount,
                'paidAmount' => $data['paidAmount'] ?? $sale->paidAmount,
                'totalAmount' => $data['totalAmount'] ?? $sale->totalAmount,
                'lossProfit' => $data['lossProfit'] ?? $sale->lossProfit,
                'paymentType' => $data['paymentType'] ?? $sale->paymentType,
                'saleDate' => $data['saleDate'] ?? $sale->saleDate,
                'meta' => $data['meta'] ?? $sale->meta,
            ]);

            // Process new items
            if (isset($data['products']) && is_array($data['products'])) {
                $sale->details()->delete();
                
                $fefoSettings = FefoSetting::getForBusiness($businessId);
                $productIds = collect($data['products'])->pluck('product_id')->filter()->unique()->values()->all();
                $businessStocks = $this->loadBusinessStocks($businessId, $productIds);
                
                $this->processSaleItems($sale, $data['products'], $businessStocks, $fefoSettings, $businessId);
            }

            return $sale->fresh(['details.product', 'party', 'tax']);
        });
    }

    /**
     * Delete a sale and restore stock.
     *
     * @param Sale $sale
     * @return bool
     * @throws \Exception
     */
    public function deleteSale(Sale $sale): bool
    {
        return $this->executeTransaction(function () use ($sale) {
            $this->restoreSaleStock($sale);
            return $sale->delete();
        });
    }

    /**
     * Calculate profit/loss for a sale.
     *
     * @param Sale $sale
     * @return array
     */
    public function calculateProfitLoss(Sale $sale): array
    {
        $totalRevenue = $sale->totalAmount;
        $totalCost = 0;

        foreach ($sale->details as $detail) {
            $totalCost += $detail->purchase_price * $detail->quantities;
        }

        $profit = $totalRevenue - $totalCost;
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'profit' => $profit,
            'profit_margin' => $profitMargin,
        ];
    }

    /**
     * Process sale items with stock deduction.
     *
     * @param Sale $sale
     * @param array $products
     * @param Collection $businessStocks
     * @param FefoSetting $fefoSettings
     * @param int $businessId
     * @return void
     */
    protected function processSaleItems(Sale $sale, array $products, Collection $businessStocks, FefoSetting $fefoSettings, int $businessId): void
    {
        foreach ($products as $productData) {
            $productId = $productData['product_id'];
            $quantity = $productData['quantities'];
            $batchNo = $productData['batch_no'] ?? null;

            $stock = $this->resolveStockForProduct($businessStocks, $productId, $batchNo, $fefoSettings);

            if (!$stock) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                    "Insufficient stock for product ID: {$productId}",
                    [
                        'product_id' => $productId,
                        'batch_no' => $batchNo,
                        'requested_qty' => $quantity,
                    ]
                );
            }

            if ($stock->productStock < $quantity) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                    "Insufficient stock for product ID: {$productId}. Available: {$stock->productStock}, Requested: {$quantity}",
                    [
                        'product_id' => $productId,
                        'batch_no' => $stock->batch_no,
                        'available_qty' => $stock->productStock,
                        'requested_qty' => $quantity,
                    ]
                );
            }

            // Lock the stock row to prevent race conditions
            $stock = Stock::where('id', $stock->id)->lockForUpdate()->first();

            // Deduct stock
            $stock->decrement('productStock', $quantity);

            // Create sale detail
            SaleDetails::create([
                'sale_id' => $sale->id,
                'product_id' => $productId,
                'price' => $productData['price'],
                'purchase_price' => $productData['purchase_price'] ?? $stock->product->purchase_without_tax ?? 0,
                'lossProfit' => $productData['lossProfit'] ?? 0,
                'batch_no' => $stock->batch_no,
                'expire_date' => $stock->expire_date,
                'quantities' => $quantity,
            ]);
        }
    }

    /**
     * Restore stock for a sale (used in updates/deletes).
     *
     * @param Sale $sale
     * @return void
     */
    protected function restoreSaleStock(Sale $sale): void
    {
        foreach ($sale->details as $detail) {
            $stock = Stock::where('product_id', $detail->product_id)
                ->where('business_id', $sale->business_id)
                ->where('batch_no', $detail->batch_no)
                ->first();

            if ($stock) {
                $stock->increment('productStock', $detail->quantities);
            }
        }
    }

    /**
     * Load business stocks for validation.
     *
     * @param int $businessId
     * @param array $productIds
     * @return Collection
     */
    protected function loadBusinessStocks(int $businessId, array $productIds): Collection
    {
        if (empty($productIds)) {
            return collect();
        }

        return Stock::where('business_id', $businessId)
            ->whereIn('product_id', $productIds)
            ->select('id', 'business_id', 'product_id', 'batch_no', 'expire_date', 'productStock')
            ->with('product:id,purchase_without_tax')
            ->get()
            ->groupBy('product_id');
    }

    /**
     * Resolve stock for a product based on batch or FEFO.
     *
     * @param Collection $stocksByProduct
     * @param int $productId
     * @param string|null $batchNo
     * @param FefoSetting $fefoSettings
     * @return Stock|null
     */
    protected function resolveStockForProduct(Collection $stocksByProduct, int $productId, ?string $batchNo, FefoSetting $fefoSettings): ?Stock
    {
        $productStocks = $stocksByProduct->get($productId, collect());

        if ($batchNo) {
            return $productStocks->first(fn ($item) => $item->batch_no === $batchNo);
        }

        if ($fefoSettings->fefo_enabled) {
            return $productStocks
                ->filter(function ($stock) {
                    return $stock->productStock > 0 && 
                           (is_null($stock->expire_date) || $stock->expire_date >= now()->startOfDay());
                })
                ->sortBy(function ($stock) {
                    return $stock->expire_date ?? '9999-12-31';
                })
                ->first();
        }

        return $productStocks->first(fn ($item) => $item->productStock > 0);
    }

    /**
     * Validate stock availability before sale.
     *
     * @param array $products
     * @param Collection $businessStocks
     * @param FefoSetting $fefoSettings
     * @param int $businessId
     * @return void
     */
    protected function validateStockAvailability(array $products, Collection $businessStocks, FefoSetting $fefoSettings, int $businessId): void
    {
        foreach ($products as $productData) {
            $productId = $productData['product_id'];
            $quantity = $productData['quantities'];
            $batchNo = $productData['batch_no'] ?? null;

            $productStocks = $businessStocks->get($productId, collect());

            if ($batchNo) {
                $stock = $productStocks->first(fn ($item) => $item->batch_no === $batchNo);
                
                if (!$stock || $stock->productStock < $quantity) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                        "Insufficient stock for product ID: {$productId}, Batch: {$batchNo}",
                        [
                            'product_id' => $productId,
                            'batch_no' => $batchNo,
                            'available_qty' => $stock->productStock ?? 0,
                            'requested_qty' => $quantity,
                        ]
                    );
                }
            } elseif ($fefoSettings->fefo_enabled) {
                $totalStock = $productStocks->filter(function ($stock) {
                    return $stock->productStock > 0 && 
                           (is_null($stock->expire_date) || $stock->expire_date >= now()->startOfDay());
                })->sum('productStock');

                if ($totalStock < $quantity) {
                    throw new BusinessRuleException(
                        ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                        "Insufficient stock for product ID: {$productId} (FEFO mode)",
                        [
                            'product_id' => $productId,
                            'available_qty' => $totalStock,
                            'requested_qty' => $quantity,
                        ]
                    );
                }
            }
        }
    }

    /**
     * Generate a unique invoice number.
     *
     * @param int $businessId
     * @return string
     */
    protected function generateInvoiceNumber(int $businessId): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $sequence = Sale::where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }
}