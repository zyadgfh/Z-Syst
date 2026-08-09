<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\Purchase;
use App\Models\PurchaseBudget;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;

class IntegrationService
{
    /**
     * PO ↔ Purchase integration
     */
    public function syncPOToPurchase(int $poId): void
    {
        $po = PurchaseOrder::find($poId);
        if (! $po) {
            return;
        }

        app(PurchaseOrderService::class)->convertToPurchase($po);
    }

    /**
     * PO ↔ GRN integration
     */
    public function syncPOToGRN(int $poId): void
    {
        $po = PurchaseOrder::find($poId);
        if (! $po) {
            return;
        }

        // Create GRN from PO items
        $grnData = [
            'purchase_order_id' => $po->id,
            'supplier_id' => $po->supplier_id,
            'business_id' => $po->business_id,
            'branch_id' => $po->branch_id,
            'received_by' => $po->created_by,
            'received_date' => now(),
            'items' => $po->items->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'ordered_quantity' => $item->quantity,
                    'received_quantity' => $item->quantity,
                    'accepted_quantity' => $item->quantity,
                    'purchase_price' => $item->unit_price,
                ];
            })->toArray(),
        ];

        app(GRNService::class)->create($grnData);
    }

    /**
     * GRN ↔ Stock integration
     */
    public function syncGRNToStock(int $grnId): void
    {
        $grn = GoodsReceivedNote::find($grnId);
        if (! $grn) {
            return;
        }

        app(GRNService::class)->verify($grn, $grn->received_by);
    }

    /**
     * Supplier ↔ All systems integration
     */
    public function syncSupplierData(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);
        if (! $supplier) {
            return;
        }

        // Update supplier references across all systems
        $supplier->calculatePerformanceScore();
    }

    /**
     * Payment ↔ Purchase integration
     */
    public function syncPaymentToPurchase(int $paymentId): void
    {
        $payment = SupplierPayment::find($paymentId);
        if (! $payment) {
            return;
        }

        // Update related purchase invoices
        SupplierInvoice::where('supplier_id', $payment->supplier_id)
            ->where('status', 'pending')
            ->update(['status' => 'paid']);
    }

    /**
     * Quality ↔ Supplier performance integration
     */
    public function syncQualityToPerformance(int $supplierId): void
    {
        $supplier = Supplier::find($supplierId);
        if (! $supplier) {
            return;
        }

        app(SupplierService::class)->calculatePerformance($supplier);
    }

    /**
     * Budget ↔ Purchase integration
     */
    public function syncPurchaseToBudget(int $purchaseId): void
    {
        $purchase = Purchase::find($purchaseId);
        if (! $purchase) {
            return;
        }

        $budget = PurchaseBudget::where('business_id', $purchase->business_id)
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($budget) {
            app(BudgetService::class)->recordTransaction($budget, [
                'purchase_id' => $purchase->id,
                'amount' => $purchase->totalAmount,
                'transaction_date' => $purchase->purchaseDate,
                'reference' => $purchase->invoiceNumber,
            ]);
        }
    }

    /**
     * Full system sync
     */
    public function fullSync(int $businessId): array
    {
        $results = [
            'suppliers_synced' => 0,
            'budgets_updated' => 0,
            'quality_reports_generated' => 0,
            'aging_reports_generated' => 0,
        ];

        // Sync all suppliers
        $suppliers = Supplier::forBusiness($businessId)->get();
        foreach ($suppliers as $supplier) {
            $this->syncSupplierData($supplier->id);
            $results['suppliers_synced']++;
        }

        // Generate quality reports
        app(QualityService::class)->generateReport($businessId);
        $results['quality_reports_generated']++;

        // Generate aging reports
        app(SupplierPaymentService::class)->generateAgingReport($businessId);
        $results['aging_reports_generated']++;

        return $results;
    }
}
