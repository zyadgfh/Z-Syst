<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleDetails;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Tax;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    use WithTransactionalOperations;

    /**
     * Generate invoice from sale.
     *
     * @param Sale $sale
     * @return Invoice
     * @throws \Exception
     */
    public function generateInvoiceFromSale(Sale $sale): Invoice
    {
        return $this->executeTransaction(function () use ($sale) {
            // Eager load details and product to avoid N+1 queries
            $sale->loadMissing('details.product:id,productName');

            $invoice = Invoice::create([
                'business_id' => $sale->business_id,
                'branch_id' => $sale->branch_id ?? null,
                'sale_id' => $sale->id,
                'party_id' => $sale->party_id,
                'invoice_number' => $this->generateInvoiceNumber($sale->business_id, $sale->branch_id),
                'invoice_date' => $sale->saleDate ?? now(),
                'due_date' => now()->addDays(30),
                'subtotal' => $this->calculateSubtotal($sale),
                'tax_amount' => $sale->tax_amount ?? 0,
                'discount_amount' => $sale->discountAmount ?? 0,
                'total_amount' => $sale->totalAmount,
                'paid_amount' => $sale->paidAmount ?? 0,
                'balance' => $sale->dueAmount ?? 0,
                'status' => $this->determineInvoiceStatus($sale),
                'payment_method' => $sale->paymentType,
                'notes' => 'Generated from sale #' . $sale->invoiceNumber,
            ]);

            // Create invoice items
            foreach ($sale->details as $detail) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $detail->product_id,
                    'description' => optional($detail->product)->productName ?? 'Product',
                    'quantity' => $detail->quantities,
                    'unit_price' => $detail->price,
                    'discount' => 0,
                    'tax' => $this->calculateItemTax($detail, $sale),
                    'total' => $detail->price * $detail->quantities,
                    'batch_no' => $detail->batch_no,
                    'expire_date' => $detail->expire_date,
                ]);
            }

            return $invoice->fresh(['items.product', 'party', 'tax']);
        });
    }

    /**
     * Create proforma invoice.
     *
     * @param array<string, mixed> $data
     * @param int $businessId
     * @return Invoice
     * @throws \Exception
     */
    public function createProformaInvoice(array $data, int $businessId): Invoice
    {
        return $this->executeTransaction(function () use ($data, $businessId) {
            $invoice = Invoice::create([
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'] ?? null,
                'party_id' => $data['party_id'] ?? null,
                'invoice_number' => $this->generateInvoiceNumber($businessId, $data['branch_id'] ?? null, 'PROFORMA'),
                'invoice_date' => $data['invoice_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'subtotal' => $data['subtotal'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0,
                'paid_amount' => 0,
                'balance' => $data['total_amount'] ?? 0,
                'status' => 'draft',
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? 'Proforma invoice',
                'is_proforma' => true,
            ]);

            // Create invoice items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $item['product_id'] ?? null,
                        'description' => $item['description'] ?? 'Item',
                        'quantity' => $item['quantity'] ?? 0,
                        'unit_price' => $item['unit_price'] ?? 0,
                        'discount' => $item['discount'] ?? 0,
                        'tax' => $item['tax'] ?? 0,
                        'total' => $item['total'] ?? 0,
                    ]);
                }
            }

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Calculate invoice totals.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function calculateInvoiceTotals(Invoice $invoice): array
    {
        $invoice->loadMissing('items');

        $subtotal = $invoice->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });

        $totalDiscount = $invoice->items->sum('discount');
        $totalTax = $invoice->items->sum('tax');
        $total = $subtotal - $totalDiscount + $totalTax;

        return [
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total' => $total,
        ];
    }

    /**
     * Apply discount to invoice.
     *
     * @param Invoice $invoice
     * @param array<string, mixed> $discountData
     * @return Invoice
     * @throws \Exception
     */
    public function applyDiscount(Invoice $invoice, array $discountData): Invoice
    {
        return $this->executeTransaction(function () use ($invoice, $discountData) {
            if ($invoice->status === 'paid') {
                throw new \Exception('Cannot apply discount to paid invoice');
            }

            $discountAmount = $discountData['amount'] ?? 0;
            $discountType = $discountData['type'] ?? 'fixed'; // fixed or percentage

            if ($discountType === 'percentage') {
                $discountAmount = ($invoice->subtotal * $discountAmount) / 100;
            }

            $invoice->update([
                'discount_amount' => $discountAmount,
                'total_amount' => $invoice->subtotal + $invoice->tax_amount - $discountAmount,
                'balance' => $invoice->total_amount - $invoice->paid_amount,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Apply tax to invoice.
     *
     * @param Invoice $invoice
     * @param array<string, mixed> $taxData
     * @return Invoice
     * @throws \Exception
     */
    public function applyTax(Invoice $invoice, array $taxData): Invoice
    {
        return $this->executeTransaction(function () use ($invoice, $taxData) {
            if ($invoice->status === 'paid') {
                throw new \Exception('Cannot apply tax to paid invoice');
            }

            $tax = Tax::findOrFail($taxData['tax_id']);
            $taxAmount = ($invoice->subtotal * $tax->rate) / 100;

            $invoice->update([
                'tax_id' => $tax->id,
                'tax_amount' => $taxAmount,
                'total_amount' => $invoice->subtotal + $taxAmount - $invoice->discount_amount,
                'balance' => $invoice->total_amount - $invoice->paid_amount,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Record payment for invoice.
     *
     * @param Invoice $invoice
     * @param array<string, mixed> $paymentData
     * @return Invoice
     * @throws \Exception
     */
    public function recordPayment(Invoice $invoice, array $paymentData): Invoice
    {
        return $this->executeTransaction(function () use ($invoice, $paymentData) {
            $paymentAmount = $paymentData['amount'];

            if ($paymentAmount > $invoice->balance) {
                throw new \Exception('Payment amount cannot exceed balance');
            }

            $invoice->update([
                'paid_amount' => $invoice->paid_amount + $paymentAmount,
                'balance' => $invoice->balance - $paymentAmount,
                'status' => $this->determineInvoiceStatus($invoice),
            ]);

            // Create payment record
            Payment::create([
                'invoice_id' => $invoice->id,
                'business_id' => $invoice->business_id,
                'amount' => $paymentAmount,
                'payment_method' => $paymentData['payment_method'],
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            return $invoice->fresh();
        });
    }

    /**
     * Calculate subtotal from sale.
     *
     * @param Sale $sale
     * @return float
     */
    protected function calculateSubtotal(Sale $sale): float
    {
        return $sale->details->sum(function ($detail) {
            return $detail->price * $detail->quantities;
        });
    }

    /**
     * Calculate item tax.
     *
     * @param SaleDetails $detail
     * @param Sale $sale
     * @return float
     */
    protected function calculateItemTax(SaleDetails $detail, Sale $sale): float
    {
        // Simplified tax calculation - can be enhanced based on tax rules
        return ($detail->price * $detail->quantities) * 0.15; // Assuming 15% tax
    }

    /**
     * Determine invoice status.
     *
     * @param Sale|Invoice $record
     * @return string
     */
    protected function determineInvoiceStatus($record): string
    {
        if ($record->balance <= 0) {
            return 'paid';
        } elseif ($record->paid_amount > 0) {
            return 'partial';
        }
        return 'unpaid';
    }

    /**
     * Generate unique invoice number.
     *
     * @param int $businessId
     * @param int|null $branchId
     * @param string|null $prefix
     * @return string
     */
    protected function generateInvoiceNumber(int $businessId, ?int $branchId = null, ?string $prefix = null): string
    {
        $invoicePrefix = $prefix ?? 'INV';
        $date = now()->format('Ymd');
        
        $query = Invoice::where('business_id', $businessId)
            ->whereDate('created_at', today());
        
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        
        $sequence = $query->count() + 1;

        $branchCode = $branchId ? sprintf('-B%d', $branchId) : '';
        return sprintf('%s%s-%s-%04d', $invoicePrefix, $branchCode, $date, $sequence);
    }
}