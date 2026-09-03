<?php

namespace App\Modules\Sales\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\Errors\ErrorCode;
use App\Models\FefoSetting;
use App\Models\Party;
use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Stock;
use App\Services\FEFODispensingService;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class SaleService
{
    use WithTransactionalOperations;

    protected FEFODispensingService $fefoDispensingService;

    public function __construct(FEFODispensingService $fefoDispensingService)
    {
        $this->fefoDispensingService = $fefoDispensingService;
    }

    public function getSales(array $filters, int $businessId, int $perPage = 15)
    {
        $query = Sale::where('business_id', $businessId);

        if (isset($filters['date_from'])) {
            $query->where('saleDate', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('saleDate', '<=', $filters['date_to']);
        }

        if (isset($filters['party_id'])) {
            $query->where('party_id', $filters['party_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['party', 'details.product', 'tax'])->latest()->paginate($perPage);
    }

    public function getSale(int $id, int $businessId): Sale
    {
        return Sale::where('id', $id)
            ->where('business_id', $businessId)
            ->with(['party', 'details.product', 'details.stock', 'tax'])
            ->firstOrFail();
    }

    public function createSale(array $data, int $businessId, int $userId): Sale
    {
        return $this->executeTransaction(function () use ($data, $businessId, $userId) {
            // Update party due amount if applicable
            if (($data['dueAmount'] ?? 0) > 0 && ($data['party_id'] ?? null)) {
                $party = Party::where('id', $data['party_id'])
                    ->where('business_id', $businessId)
                    ->firstOrFail();
                $party->increment('due', $data['dueAmount']);
            }

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
                'saleDate' => $data['saleDate'] ?? now(),
                'meta' => $data['meta'] ?? null,
                'status' => 'completed',
            ]);

            // Process sale items with FEFO dispensing
            $this->processSaleItems($sale, $data['products'], $businessId);

            return $sale->fresh(['details.product', 'details.stock', 'party', 'tax']);
        });
    }

    public function updateSale(int $id, array $data, int $businessId): Sale
    {
        return $this->executeTransaction(function () use ($id, $data, $businessId) {
            $sale = Sale::where('id', $id)
                ->where('business_id', $businessId)
                ->firstOrFail();

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
                $this->processSaleItems($sale, $data['products'], $businessId);
            }

            return $sale->fresh(['details.product', 'details.stock', 'party', 'tax']);
        });
    }

    public function deleteSale(int $id, int $businessId): bool
    {
        return $this->executeTransaction(function () use ($id, $businessId) {
            $sale = Sale::where('id', $id)
                ->where('business_id', $businessId)
                ->firstOrFail();

            $this->restoreSaleStock($sale);
            return $sale->delete();
        });
    }

    public function calculateProfitLoss(int $id, int $businessId): array
    {
        $sale = Sale::where('id', $id)
            ->where('business_id', $businessId)
            ->with('details')
            ->firstOrFail();

        $totalRevenue = $sale->totalAmount;
        $totalCost = 0;

        foreach ($sale->details as $detail) {
            $totalCost += ($detail->purchase_price ?? 0) * $detail->quantities;
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

    protected function processSaleItems(Sale $sale, array $products, int $businessId): void
    {
        foreach ($products as $productData) {
            $productId = $productData['product_id'];
            $quantity = $productData['quantities'];
            $stockId = $productData['stock_id'] ?? null;

            try {
                if ($stockId) {
                    $dispensingPlan = $this->fefoDispensingService->dispenseFromBatch($stockId, $quantity, $businessId);
                } else {
                    $dispensingPlan = $this->fefoDispensingService->dispense($productId, $quantity, $businessId);
                }

                foreach ($dispensingPlan as $batchDispense) {
                    $stock = Stock::where('id', $batchDispense['stock_id'])
                        ->where('business_id', $businessId)
                        ->lockForUpdate()
                        ->first();

                    $stock->decrement('productStock', $batchDispense['quantity']);

                    SaleDetails::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productId,
                        'stock_id' => $batchDispense['stock_id'],
                        'price' => $productData['price'],
                        'purchase_price' => $batchDispense['unit_cost'] ?? $productData['purchase_price'] ?? 0,
                        'lossProfit' => $productData['lossProfit'] ?? 0,
                        'batch_no' => $batchDispense['batch_no'],
                        'expire_date' => $batchDispense['expire_date'],
                        'quantities' => $batchDispense['quantity'],
                    ]);
                }
            } catch (InsufficientStockException $e) {
                throw new BusinessRuleException(
                    ErrorCode::BUSINESS_INSUFFICIENT_STOCK,
                    $e->getMessage(),
                    [
                        'product_id' => $productId,
                        'requested_qty' => $quantity,
                        'available_qty' => $e->getAvailableStock(),
                    ]
                );
            }
        }
    }

    protected function restoreSaleStock(Sale $sale): void
    {
        foreach ($sale->details as $detail) {
            if ($detail->stock_id) {
                $stock = Stock::where('id', $detail->stock_id)
                    ->where('business_id', $sale->business_id)
                    ->first();
            } else {
                $stock = Stock::where('product_id', $detail->product_id)
                    ->where('business_id', $sale->business_id)
                    ->where('batch_no', $detail->batch_no)
                    ->first();
            }

            if ($stock) {
                $stock->increment('productStock', $detail->quantities);
            }
        }
    }
}
