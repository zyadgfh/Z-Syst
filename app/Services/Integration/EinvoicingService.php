<?php

namespace App\Services\Integration;

use App\Models\Invoice;
use App\Models\Business;
use App\Traits\WithTransactionalOperations;
use Illuminate\Support\Collection;

class EinvoicingService
{
    use WithTransactionalOperations;

    /**
     * Generate e-invoice.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function generateEinvoice(Invoice $invoice): array
    {
        return $this->executeTransaction(function () use ($invoice) {
            // Prepare e-invoice data according to local e-invoicing standards
            $einvoiceData = $this->prepareEinvoicingData($invoice);

            // Submit to e-invoicing system
            $response = $this->submitToEinvoicingSystem($einvoiceData);

            if ($response['success']) {
                $invoice->update([
                    'einvoice_id' => $response['einvoice_id'],
                    'einvoice_status' => 'submitted',
                    'einvoice_submitted_at' => now(),
                ]);

                return [
                    'success' => true,
                    'einvoice_id' => $response['einvoice_id'],
                    'qr_code' => $response['qr_code'] ?? null,
                    'message' => 'E-invoice generated successfully',
                ];
            }

            return [
                'success' => false,
                'message' => $response['error'] ?? 'Failed to generate e-invoice',
            ];
        });
    }

    /**
     * Prepare e-invoicing data.
     *
     * @param Invoice $invoice
     * @return array
     */
    protected function prepareEinvoicingData(Invoice $invoice): array
    {
        $business = $invoice->business;
        $customer = $invoice->customer;

        return [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'due_date' => $invoice->due_date ? $invoice->due_date->toDateString() : null,
            'total_amount' => $invoice->total_amount,
            'tax_amount' => $invoice->tax_amount,
            'currency' => $invoice->currency,
            
            'seller' => [
                'name' => $business->companyName,
                'tax_id' => $business->tax_id ?? null,
                'address' => $business->address ?? null,
                'phone' => $business->phone ?? null,
                'email' => $business->email ?? null,
            ],
            
            'buyer' => [
                'name' => $customer ? $customer->name : 'Walk-in Customer',
                'tax_id' => $customer ? $customer->tax_id : null,
                'address' => $customer ? $customer->address : null,
                'phone' => $customer ? $customer->phone : null,
                'email' => $customer ? $customer->email : null,
            ],
            
            'items' => $invoice->items->map(function ($item) {
                return [
                    'product_code' => $item->product->productCode ?? null,
                    'product_name' => $item->product->productName ?? 'Unknown',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'tax_rate' => $item->tax_rate ?? 0,
                    'total' => $item->total,
                ];
            })->toArray(),
        ];
    }

    /**
     * Submit to e-invoicing system.
     *
     * @param array $einvoiceData
     * @return array
     */
    protected function submitToEinvoicingSystem(array $einvoiceData): array
    {
        // Integrate with local e-invoicing system (e.g., ZATCA in Saudi Arabia, Fatoora)
        // For now, simulate the response
        
        $einvoiceId = 'EINV-' . strtoupper(uniqid());
        
        return [
            'success' => true,
            'einvoice_id' => $einvoiceId,
            'qr_code' => $this->generateQRCode($einvoiceId),
            'message' => 'E-invoice submitted successfully',
        ];
    }

    /**
     * Generate QR code for e-invoice.
     *
     * @param string $einvoiceId
     * @return string
     */
    protected function generateQRCode(string $einvoiceId): string
    {
        // Generate QR code containing e-invoice data
        // This would use a QR code library
        
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    /**
     * Get e-invoice status.
     *
     * @param string $einvoiceId
     * @return array
     */
    public function getEinvoicingStatus(string $einvoiceId): array
    {
        // Query e-invoicing system for status
        // For now, simulate the response
        
        return [
            'einvoice_id' => $einvoiceId,
            'status' => 'approved',
            'approved_at' => now()->toIso8601String(),
            'verified' => true,
        ];
    }

    /**
     * Cancel e-invoice.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function cancelEinvoicing(Invoice $invoice): array
    {
        if (!$invoice->einvoice_id) {
            return [
                'success' => false,
                'message' => 'No e-invoice found for this invoice',
            ];
        }

        // Submit cancellation to e-invoicing system
        $response = $this->submitCancellation($invoice->einvoice_id);

        if ($response['success']) {
            $invoice->update([
                'einvoice_status' => 'cancelled',
                'einvoice_cancelled_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'E-invoice cancelled successfully',
            ];
        }

        return [
            'success' => false,
            'message' => $response['error'] ?? 'Failed to cancel e-invoice',
        ];
    }

    /**
     * Submit cancellation to e-invoicing system.
     *
     * @param string $einvoiceId
     * @return array
     */
    protected function submitCancellation(string $einvoiceId): array
    {
        // Integrate with e-invoicing system
        // For now, simulate the response
        
        return [
            'success' => true,
            'message' => 'Cancellation submitted successfully',
        ];
    }

    /**
     * Get e-invoicing compliance report.
     *
     * @param int $businessId
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getComplianceReport(int $businessId, array $filters = []): array
    {
        $query = Invoice::where('business_id', $businessId);

        if (isset($filters['from_date'])) {
            $query->where('invoice_date', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('invoice_date', '<=', $filters['to_date']);
        }

        $invoices = $query->get();

        $totalInvoices = $invoices->count();
        $einvoiceCompliant = $invoices->whereNotNull('einvoice_id')->count();
        $complianceRate = $totalInvoices > 0 ? ($einvoiceCompliant / $totalInvoices) * 100 : 0;

        return [
            'total_invoices' => $totalInvoices,
            'einvoice_compliant' => $einvoiceCompliant,
            'compliance_rate' => round($complianceRate, 2),
            'non_compliant' => $totalInvoices - $einvoiceCompliant,
        ];
    }

    /**
     * Sync e-invoicing status for pending invoices.
     *
     * @param int $businessId
     * @return array
     */
    public function syncEinvoicingStatus(int $businessId): array
    {
        $pendingInvoices = Invoice::where('business_id', $businessId)
            ->where('einvoice_status', 'submitted')
            ->whereNull('einvoice_verified_at')
            ->get();

        $syncedCount = 0;

        foreach ($pendingInvoices as $invoice) {
            try {
                $status = $this->getEinvoicingStatus($invoice->einvoice_id);
                
                if ($status['status'] === 'approved') {
                    $invoice->update([
                        'einvoice_status' => 'approved',
                        'einvoice_verified_at' => now(),
                    ]);
                    $syncedCount++;
                }
            } catch (\Exception $e) {
                // Log error and continue
                continue;
            }
        }

        return [
            'success' => true,
            'synced_count' => $syncedCount,
            'total_pending' => $pendingInvoices->count(),
        ];
    }
}