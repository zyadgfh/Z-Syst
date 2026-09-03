<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\SupplierInvoicePayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierInvoiceService
{
    /**
     * Create a new supplier invoice.
     */
    public function create(array $data): SupplierInvoice
    {
        return DB::transaction(function () use ($data) {
            $businessId = $data['business_id'];
            $invoice = SupplierInvoice::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'] ?? null,
                'purchase_id' => $data['purchase_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'invoice_number' => $data['invoice_number'] ?? $this->generateInvoiceNumber($businessId),
                'invoice_date' => $data['invoice_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'status' => SupplierInvoice::STATUS_PENDING,
                'currency' => $data['currency'] ?? 'SAR',
                'payment_terms' => $data['payment_terms'] ?? 'net_30',
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            // Add items
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->addItem($invoice, $item);
                }
            }

            // Handle file upload
            if (isset($data['file'])) {
                $this->uploadFile($invoice, $data['file']);
            }

            $invoice->calculateTotal();

            return $invoice;
        });
    }

    /**
     * Create invoice from purchase.
     */
    public function createFromPurchase(Purchase $purchase): SupplierInvoice
    {
        return DB::transaction(function () use ($purchase) {
            $businessId = $purchase->business_id;
            $invoice = SupplierInvoice::create([
                'supplier_id' => $purchase->party_id,
                'business_id' => $businessId,
                'branch_id' => $purchase->branch_id ?? null,
                'purchase_id' => $purchase->id,
                'invoice_number' => $this->generateInvoiceNumber($businessId),
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'tax_amount' => $purchase->tax_amount ?? 0,
                'discount_amount' => $purchase->discountAmount ?? 0,
                'status' => SupplierInvoice::STATUS_PENDING,
                'currency' => 'SAR',
                'payment_terms' => 'net_30',
                'notes' => "Created from Purchase: {$purchase->invoiceNumber}",
            ]);

            // Add items from purchase details
            foreach ($purchase->details as $detail) {
                SupplierInvoiceItem::create([
                    'supplier_invoice_id' => $invoice->id,
                    'product_id' => $detail->product_id,
                    'purchase_detail_id' => $detail->id,
                    'description' => $detail->product->name ?? 'Product',
                    'quantity' => $detail->quantities,
                    'unit_price' => $detail->purchase_without_tax,
                    'discount' => 0,
                    'tax' => $detail->purchase_with_tax - $detail->purchase_without_tax,
                    'batch_number' => $detail->batch_no,
                    'expiry_date' => $detail->expire_date,
                ]);
            }

            $invoice->calculateTotal();

            return $invoice;
        });
    }

    /**
     * Add item to invoice.
     */
    public function addItem(SupplierInvoice $invoice, array $itemData): SupplierInvoiceItem
    {
        $product = Product::find($itemData['product_id'] ?? null);

        $unitPrice = $itemData['unit_price'];
        $quantity = $itemData['quantity'];
        $discount = $itemData['discount'] ?? 0;
        $tax = $itemData['tax'] ?? 0;
        $total = ($unitPrice * $quantity) - $discount + $tax;

        $invoiceItem = SupplierInvoiceItem::create([
            'supplier_invoice_id' => $invoice->id,
            'product_id' => $itemData['product_id'] ?? null,
            'purchase_detail_id' => $itemData['purchase_detail_id'] ?? null,
            'description' => $itemData['description'] ?? ($product->name ?? 'Item'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            'batch_number' => $itemData['batch_number'] ?? null,
            'expiry_date' => $itemData['expiry_date'] ?? null,
            'notes' => $itemData['notes'] ?? null,
        ]);

        $invoice->calculateTotal();

        return $invoiceItem->refresh();
    }

    /**
     * Update invoice.
     */
    public function update(SupplierInvoice $invoice, array $data): SupplierInvoice
    {
        return DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'supplier_id' => $data['supplier_id'] ?? $invoice->supplier_id,
                'invoice_date' => $data['invoice_date'] ?? $invoice->invoice_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'tax_amount' => $data['tax_amount'] ?? $invoice->tax_amount,
                'discount_amount' => $data['discount_amount'] ?? $invoice->discount_amount,
                'currency' => $data['currency'] ?? $invoice->currency,
                'payment_terms' => $data['payment_terms'] ?? $invoice->payment_terms,
                'notes' => $data['notes'] ?? $invoice->notes,
                'internal_notes' => $data['internal_notes'] ?? $invoice->internal_notes,
            ]);

            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                $invoice->items()->delete();
                foreach ($data['items'] as $item) {
                    $this->addItem($invoice, $item);
                }
            }

            // Handle file upload
            if (isset($data['file'])) {
                $this->uploadFile($invoice, $data['file']);
            }

            $invoice->calculateTotal();

            return $invoice;
        });
    }

    /**
     * Approve invoice.
     */
    public function approve(SupplierInvoice $invoice, int $userId): SupplierInvoice
    {
        if (! $invoice->isPending()) {
            throw new \Exception('Only pending invoices can be approved');
        }

        $invoice->approve($userId);

        // Update linked purchase status if exists
        if ($invoice->purchase_id) {
            Purchase::where('id', $invoice->purchase_id)
                ->update(['status' => 'approved']);
        }

        // Notify supplier and business users
        $this->notifyStakeholders($invoice, 'approved', $userId);

        return $invoice;
    }

    /**
     * Reject invoice.
     */
    public function reject(SupplierInvoice $invoice, int $userId, string $reason): SupplierInvoice
    {
        if (! $invoice->isPending()) {
            throw new \Exception('Only pending invoices can be rejected');
        }

        $invoice->reject($userId, $reason);

        // Notify supplier and business users
        $this->notifyStakeholders($invoice, 'rejected', $userId);

        return $invoice;
    }

    /**
     * Cancel invoice.
     */
    public function cancel(SupplierInvoice $invoice, string $reason): SupplierInvoice
    {
        if ($invoice->isPaid()) {
            throw new \Exception('Cannot cancel paid invoices');
        }

        $invoice->cancel($reason);

        // Reverse any approved payments by marking them cancelled
        SupplierInvoicePayment::where('supplier_invoice_id', $invoice->id)
            ->where('status', '!=', SupplierInvoicePayment::STATUS_CANCELLED)
            ->update(['status' => SupplierInvoicePayment::STATUS_CANCELLED]);

        // Update linked purchase status if exists
        if ($invoice->purchase_id) {
            Purchase::where('id', $invoice->purchase_id)
                ->update(['status' => 'cancelled']);
        }

        // Notify stakeholders
        $this->notifyStakeholders($invoice, 'cancelled');

        return $invoice;
    }

    /**
     * Add payment to invoice.
     */
    public function addPayment(SupplierInvoice $invoice, array $paymentData): SupplierInvoicePayment
    {
        return DB::transaction(function () use ($invoice, $paymentData) {
            $payment = SupplierInvoicePayment::create([
                'supplier_invoice_id' => $invoice->id,
                'business_id' => $invoice->business_id,
                'branch_id' => $invoice->branch_id,
                'payment_number' => $paymentData['payment_number'] ?? $this->generatePaymentNumber($invoice->business_id),
                'payment_date' => $paymentData['payment_date'] ?? now(),
                'payment_method' => $paymentData['payment_method'],
                'payment_reference' => $paymentData['payment_reference'] ?? null,
                'bank_reference' => $paymentData['bank_reference'] ?? null,
                'amount' => $paymentData['amount'],
                'status' => SupplierInvoicePayment::STATUS_PENDING,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            // Handle payment file upload
            if (isset($paymentData['file'])) {
                $this->uploadPaymentFile($payment, $paymentData['file']);
            }

            // Update invoice paid_amount and balance
            $invoice->increment('paid_amount', $payment->amount);
            $invoice->refresh();
            $invoice->balance = $invoice->total_amount - $invoice->paid_amount;
            if ($invoice->balance <= 0) {
                $invoice->status = SupplierInvoice::STATUS_PAID;
                $invoice->balance = 0;
            } elseif ($invoice->paid_amount > 0) {
                $invoice->status = SupplierInvoice::STATUS_PARTIALLY_PAID;
            }
            $invoice->save();

            return $payment;
        });
    }

    /**
     * Approve payment.
     */
    public function approvePayment(SupplierInvoicePayment $payment, int $userId): SupplierInvoicePayment
    {
        if (! $payment->isPending()) {
            throw new \Exception('Only pending payments can be approved');
        }

        $payment->approve($userId);

        return $payment;
    }

    /**
     * Upload invoice file.
     */
    private function uploadFile(SupplierInvoice $invoice, $file): void
    {
        $path = $file->store('supplier-invoices', 'public');
        $invoice->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Upload payment file.
     */
    private function uploadPaymentFile(SupplierInvoicePayment $payment, $file): void
    {
        $path = $file->store('payment-receipts', 'public');
        $payment->update([
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Get invoices by business.
     */
    public function getByBusiness(int $businessId)
    {
        return SupplierInvoice::forBusiness($businessId)
            ->with(['supplier', 'items.product', 'payments'])
            ->latest()
            ->get();
    }

    /**
     * Get invoices by supplier.
     */
    public function getBySupplier(int $supplierId, int $businessId)
    {
        return SupplierInvoice::forBusiness($businessId)
            ->forSupplier($supplierId)
            ->with(['items.product', 'payments'])
            ->latest()
            ->get();
    }

    /**
     * Get pending invoices.
     */
    public function getPending(int $businessId)
    {
        return SupplierInvoice::forBusiness($businessId)
            ->pending()
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdue(int $businessId)
    {
        return SupplierInvoice::forBusiness($businessId)
            ->overdue()
            ->with(['supplier', 'items.product', 'payments'])
            ->latest()
            ->get();
    }

    /**
     * Get unpaid invoices.
     */
    public function getUnpaid(int $businessId)
    {
        return SupplierInvoice::forBusiness($businessId)
            ->unpaid()
            ->with(['supplier', 'items.product'])
            ->latest()
            ->get();
    }

    /**
     * Get invoice statistics.
     */
    public function getStatistics(int $businessId): array
    {
        $total = SupplierInvoice::forBusiness($businessId)->count();
        $pending = SupplierInvoice::forBusiness($businessId)->pending()->count();
        $overdue = SupplierInvoice::forBusiness($businessId)->overdue()->count();
        $unpaid = SupplierInvoice::forBusiness($businessId)->unpaid()->count();

        $totalAmount = SupplierInvoice::forBusiness($businessId)->sum('total_amount');
        $paidAmount = SupplierInvoice::forBusiness($businessId)->sum('paid_amount');
        $balance = SupplierInvoice::forBusiness($businessId)->sum('balance');

        return [
            'total' => $total,
            'pending' => $pending,
            'overdue' => $overdue,
            'unpaid' => $unpaid,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'balance' => $balance,
        ];
    }

    /**
     * Get aging report.
     */
    public function getAgingReport(int $businessId): array
    {
        $invoices = SupplierInvoice::forBusiness($businessId)
            ->unpaid()
            ->get();

        $period30 = 0;
        $period60 = 0;
        $period90 = 0;
        $period90Plus = 0;

        foreach ($invoices as $invoice) {
            $daysOverdue = $invoice->getDaysUntilDue();

            if ($daysOverdue <= 0) {
                $period30 += $invoice->balance;
            } elseif ($daysOverdue <= -30) {
                $period60 += $invoice->balance;
            } elseif ($daysOverdue <= -60) {
                $period90 += $invoice->balance;
            } else {
                $period90Plus += $invoice->balance;
            }
        }

        return [
            'period_30' => $period30,
            'period_60' => $period60,
            'period_90' => $period90,
            'period_90_plus' => $period90Plus,
            'total' => $period30 + $period60 + $period90 + $period90Plus,
        ];
    }

    /**
     * Notify stakeholders about invoice status changes.
     */
    private function notifyStakeholders(SupplierInvoice $invoice, string $action, ?int $userId = null): void
    {
        try {
            $users = User::where('business_id', $invoice->business_id)
                ->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['admin', 'manager', 'purchaser']);
                })
                ->get();

            foreach ($users as $user) {
                \App\Models\Notification::create([
                    'business_id' => $invoice->business_id,
                    'user_id' => $user->id,
                    'type' => 'supplier_invoice_' . $action,
                    'title' => 'Supplier Invoice ' . ucfirst($action),
                    'message' => "Invoice {$invoice->invoice_number} has been {$action}.",
                    'data' => json_encode([
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'supplier_id' => $invoice->supplier_id,
                        'action' => $action,
                    ]),
                    'read' => false,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send supplier invoice notification', [
                'invoice_id' => $invoice->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Delete invoice.
     */
    public function delete(SupplierInvoice $invoice): bool
    {
        if (! $invoice->isPending()) {
            throw new \Exception('Only pending invoices can be deleted');
        }

        return $invoice->delete();
    }

    /**
     * Generate a unique invoice number.
     */
    private function generateInvoiceNumber(int $businessId): string
    {
        $count = SupplierInvoice::where('business_id', $businessId)->count() + 1;

        return 'INV-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique payment number.
     */
    private function generatePaymentNumber(int $businessId): string
    {
        $count = SupplierInvoicePayment::where('business_id', $businessId)->count() + 1;

        return 'PAY-' . date('Y') . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
