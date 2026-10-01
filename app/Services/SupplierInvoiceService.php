<?php

namespace App\Services;

use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use App\Models\SupplierInvoicePayment;
use App\Models\Purchase;
use App\Models\PurchaseDetails;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SupplierInvoiceService
{
    /**
     * Notification dispatcher used to inform suppliers about invoice events.
     */
    protected NotificationService $notifications;

    public function __construct(?NotificationService $notifications = null)
    {
        $this->notifications = $notifications ?? app(NotificationService::class);
    }

    /**
     * Create a new supplier invoice.
     */
    public function create(array $data): SupplierInvoice
    {
        return DB::transaction(function () use ($data) {
            $invoice = SupplierInvoice::create([
                'supplier_id' => $data['supplier_id'] ?? null,
                'business_id' => $data['business_id'],
                'branch_id' => $data['branch_id'] ?? null,
                'purchase_id' => $data['purchase_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'invoice_date' => $data['invoice_date'] ?? now(),
                'due_date' => $data['due_date'] ?? now()->addDays(30),
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
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
            $invoice = SupplierInvoice::create([
                'supplier_id' => $purchase->party_id,
                'business_id' => $purchase->business_id,
                'branch_id' => $purchase->branch_id ?? null,
                'purchase_id' => $purchase->id,
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'tax_amount' => $purchase->tax_amount ?? 0,
                'discount_amount' => $purchase->discountAmount ?? 0,
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

        $invoiceItem = SupplierInvoiceItem::create([
            'supplier_invoice_id' => $invoice->id,
            'product_id' => $itemData['product_id'] ?? null,
            'purchase_detail_id' => $itemData['purchase_detail_id'] ?? null,
            'description' => $itemData['description'] ?? ($product->name ?? 'Item'),
            'quantity' => $itemData['quantity'],
            'unit_price' => $itemData['unit_price'],
            'discount' => $itemData['discount'] ?? 0,
            'tax' => $itemData['tax'] ?? 0,
            'batch_number' => $itemData['batch_number'] ?? null,
            'expiry_date' => $itemData['expiry_date'] ?? null,
            'notes' => $itemData['notes'] ?? null,
        ]);

        $invoice->calculateTotal();

        return $invoiceItem;
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
        if (!$invoice->isPending()) {
            throw new \Exception('Only pending invoices can be approved');
        }

        $invoice->approve($userId);

        // Inform the supplier the invoice was approved.
        $this->notifySupplier($invoice, 'approved');

        // Keep the linked purchase in sync (audit trail + status where applicable).
        $this->syncLinkedPurchase($invoice, 'approved');

        return $invoice;
    }

    /**
     * Reject invoice.
     */
    public function reject(SupplierInvoice $invoice, int $userId, string $reason): SupplierInvoice
    {
        if (!$invoice->isPending()) {
            throw new \Exception('Only pending invoices can be rejected');
        }

        $invoice->reject($userId, $reason);

        // Inform the supplier why the invoice was rejected.
        $this->notifySupplier($invoice, 'rejected', $reason ? "Reason: {$reason}" : null);

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

        // Reverse any payments recorded against this invoice.
        $this->reversePayments($invoice);

        // Keep the linked purchase in sync.
        $this->syncLinkedPurchase($invoice, 'cancelled');

        // Inform the supplier about the cancellation.
        $this->notifySupplier($invoice, 'cancelled', $reason ? "Reason: {$reason}" : null);

        return $invoice;
    }

    /**
     * Reverse all non-cancelled payments attached to a cancelled invoice.
     *
     * Approved/completed payments contributed to the invoice balance and are
     * reversed through the model's cancel() (which restores paid_amount /
     * balance). Pending payments never touched the balance, so they are
     * simply closed out.
     */
    protected function reversePayments(SupplierInvoice $invoice): void
    {
        $payments = $invoice->payments()->get();

        foreach ($payments as $payment) {
            if ($payment->status === SupplierInvoicePayment::STATUS_CANCELLED) {
                continue;
            }

            if ($payment->isPending()) {
                $payment->update(['status' => SupplierInvoicePayment::STATUS_CANCELLED]);

                continue;
            }

            // Approved / completed payment: cancel() reverses the amounts.
            $payment->cancel();
        }

        $invoice->refresh();
    }

    /**
     * Sync the purchase linked to an invoice after a lifecycle event.
     *
     * The Purchase model has no dedicated status column, so this records an
     * audit-trail entry tying the invoice event to the linked purchase.
     */
    protected function syncLinkedPurchase(SupplierInvoice $invoice, string $event): void
    {
        if (! $invoice->purchase_id) {
            return;
        }

        $purchase = $invoice->purchase;

        if (! $purchase) {
            return;
        }

        AuditLogger::log(
            'supplier_invoice.purchase_synced',
            "Supplier invoice {$invoice->invoice_number} {$event}; linked purchase #{$purchase->id}.",
            [
                'supplier_invoice_id' => $invoice->id,
                'purchase_id' => $purchase->id,
                'event' => $event,
            ]
        );

        Log::info("Linked purchase synced after invoice {$event}", [
            'supplier_invoice_id' => $invoice->id,
            'purchase_id' => $purchase->id,
        ]);
    }

    /**
     * Notify the linked supplier (email + SMS) about an invoice lifecycle event.
     *
     * @param  SupplierInvoice  $invoice  The supplier invoice.
     * @param  string  $event  Past-tense verb, e.g. "approved", "rejected".
     * @param  string|null  $note  Optional extra line (e.g. a reason).
     */
    protected function notifySupplier(SupplierInvoice $invoice, string $event, ?string $note = null): void
    {
        $invoice->loadMissing(['supplier', 'items.product']);

        $supplier = $invoice->supplier;

        $lines = [
            "Supplier invoice {$invoice->invoice_number} has been {$event}.",
            'Status: '.$invoice->status,
            'Total: '.number_format((float) ($invoice->total_amount ?? 0), 2),
            'Balance: '.number_format((float) ($invoice->balance ?? 0), 2),
            'Currency: '.($invoice->currency ?? 'N/A'),
        ];

        if ($invoice->due_date) {
            $lines[] = 'Due date: '.$invoice->due_date->toDateString();
        }

        if ($note) {
            $lines[] = $note;
        }

        $table = [
            'headers' => ['Description', 'Quantity', 'Unit Price', 'Total'],
            'rows' => $invoice->items->map(function (SupplierInvoiceItem $item) {
                return [
                    $item->description ?? ($item->product?->name ?? 'Item'),
                    (string) $item->quantity,
                    number_format((float) $item->unit_price, 2),
                    number_format((float) $item->total, 2),
                ];
            })->values()->toArray(),
        ];

        $result = $this->notifications->notifySupplier(
            $supplier,
            "Supplier Invoice {$invoice->invoice_number} {$event}",
            $lines,
            $table
        );

        Log::info("Supplier notified about invoice {$event}", [
            'supplier_invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'supplier_id' => $supplier?->id,
            'email_sent' => $result['email'],
            'sms_sent' => $result['sms'],
        ]);
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

            return $payment;
        });
    }

    /**
     * Approve payment.
     */
    public function approvePayment(SupplierInvoicePayment $payment, int $userId): SupplierInvoicePayment
    {
        if (!$payment->isPending()) {
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
     * Delete invoice.
     */
    public function delete(SupplierInvoice $invoice): bool
    {
        if (!$invoice->isPending()) {
            throw new \Exception('Only pending invoices can be deleted');
        }

        return $invoice->delete();
    }
}
