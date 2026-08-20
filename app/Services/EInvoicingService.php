<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Party;
use App\Models\Sale;
use App\Models\Tax;
use App\Traits\WithTransactionalOperations;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class EInvoicingService
{
    use WithTransactionalOperations;

    /**
     * Generate QR code for invoice.
     *
     * @param Invoice $invoice
     * @return string Base64 encoded QR code
     */
    public function generateQrCode(Invoice $invoice): string
    {
        $qrData = $this->buildQrCodeData($invoice);

        return QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->encoding('UTF-8')
            ->errorCorrection('H')
            ->generate($qrData);
    }

    /**
     * Build QR code data according to e-invoicing standard (e.g., ZATCA, Egypt e-invoice).
     *
     * @param Invoice $invoice
     * @return string JSON string for QR code
     */
    protected function buildQrCodeData(Invoice $invoice): string
    {
        $data = [
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toIso8601String(),
            'invoice_time' => $invoice->invoice_date->format('H:i:s'),
            'seller_name' => $invoice->business->companyName ?? config('app.name'),
            'seller_tax_number' => $invoice->business->tax_number ?? config('app.tax_number'),
            'buyer_name' => $invoice->party->name ?? 'Walk-in Customer',
            'buyer_tax_number' => $invoice->party->tax_id ?? '',
            'currency' => 'SAR',
            'total_amount' => number_format($invoice->total_amount, 2, '.', ''),
            'tax_amount' => number_format($invoice->tax_amount, 2, '.', ''),
            'items_count' => $invoice->items()->count(),
        ];

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Validate invoice for e-invoicing compliance.
     *
     * @param Invoice $invoice
     * @return array ['is_valid' => bool, 'errors' => array, 'warnings' => array]
     */
    public function validateInvoice(Invoice $invoice): array
    {
        $errors = [];
        $warnings = [];

        // Validate required fields
        if (empty($invoice->invoice_number)) {
            $errors[] = 'Invoice number is required';
        }

        if (empty($invoice->invoice_date)) {
            $errors[] = 'Invoice date is required';
        }

        if (empty($invoice->party_id)) {
            $errors[] = 'Customer (party) is required';
        } else {
            $party = $invoice->party;
            if (empty($party->name)) {
                $errors[] = 'Customer name is required';
            }
            // Tax ID validation for B2B invoices
            if ($party->type === 'customer' && empty($party->tax_id)) {
                $warnings[] = 'Customer tax ID is recommended for B2B transactions';
            }
        }

        // Validate items
        $items = $invoice->items;
        if ($items->isEmpty()) {
            $errors[] = 'At least one invoice item is required';
        } else {
            foreach ($items as $index => $item) {
                if (empty($item->product_name) || empty($item->description)) {
                    $errors[] = "Item #{$index}: Product name/description is required";
                }
                if (empty($item->quantity) || $item->quantity <= 0) {
                    $errors[] = "Item #{$index}: Valid quantity is required";
                }
                if (empty($item->unit_price) || $item->unit_price < 0) {
                    $errors[] = "Item #{$index}: Valid unit price is required";
                }
                if (empty($item->tax_id) && $invoice->tax_amount > 0) {
                    $warnings[] = "Item #{$index}: Tax ID is recommended when tax is applied";
                }
            }
        }

        // Validate amounts
        $calculatedSubtotal = $items->sum(fn($item) => $item->quantity * $item->unit_price);
        $calculatedTax = $items->sum(fn($item) => ($item->quantity * $item->unit_price) * ($item->tax_rate ?? 0) / 100);
        $calculatedTotal = $calculatedSubtotal + $calculatedTax - ($invoice->discount_amount ?? 0);

        if (abs($calculatedSubtotal - $invoice->subtotal) > 0.01) {
            $errors[] = "Subtotal mismatch: calculated {$calculatedSubtotal}, stored {$invoice->subtotal}";
        }

        if (abs($calculatedTax - $invoice->tax_amount) > 0.01) {
            $warnings[] = "Tax amount mismatch: calculated {$calculatedTax}, stored {$invoice->tax_amount}";
        }

        if (abs($calculatedTotal - $invoice->total_amount) > 0.01) {
            $errors[] = "Total amount mismatch: calculated {$calculatedTotal}, stored {$invoice->total_amount}";
        }

        // Validate payment info for paid invoices
        if ($invoice->status === 'paid' && empty($invoice->paid_amount)) {
            $errors[] = 'Paid amount is required for paid invoices';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Submit invoice to e-invoicing portal (ZATCA, Egypt e-invoice, etc.).
     *
     * @param Invoice $invoice
     * @return array
     */
    public function submitInvoice(Invoice $invoice): array
    {
        $submissionResult = [
            'success' => false,
            'external_id' => null,
            'status' => 'failed',
            'message' => '',
            'uuid' => null,
        ];

        try {
            // Validate before submission
            $validation = $this->validateInvoice($invoice);
            if (!$validation['is_valid']) {
                $submissionResult['message'] = 'Validation failed: ' . implode('; ', $validation['errors']);
                return $submissionResult;
            }

            // Prepare submission payload
            $payload = $this->buildSubmissionPayload($invoice);

            // Submit to e-invoicing system
            $response = $this->submitToPortal($payload, $invoice);

            if ($response['success']) {
                $invoice->update([
                    'einvoice_uuid' => $response['uuid'],
                    'einvoice_status' => 'submitted',
                    'einvoice_submitted_at' => now(),
                    'einvoice_external_id' => $response['external_id'] ?? null,
                    'einvoice_qr_code' => $this->generateQrCode($invoice),
                ]);

                $submissionResult = [
                    'success' => true,
                    'external_id' => $response['external_id'] ?? null,
                    'uuid' => $response['uuid'],
                    'status' => 'submitted',
                    'message' => 'Invoice submitted successfully to e-invoicing portal',
                ];
            } else {
                $submissionResult['message'] = $response['error'] ?? 'Submission failed';
            }

        } catch (\Exception $e) {
            Log::error('E-invoicing submission failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $submissionResult['message'] = 'Submission failed: ' . $e->getMessage();
        }

        return $submissionResult;
    }

    /**
     * Build submission payload for e-invoicing portal.
     *
     * @param Invoice $invoice
     * @return array
     */
    protected function buildSubmissionPayload(Invoice $invoice): array
    {
        $items = [];
        foreach ($invoice->items as $item) {
            $items[] = [
                'name' => $item->product_name ?? $item->description,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => number_format($item->unit_price, 2, '.', ''),
                'discount' => $item->discount ?? 0,
                'tax_rate' => $item->tax_rate ?? 0,
                'tax_amount' => number_format($item->tax_amount ?? 0, 2, '.', ''),
                'total' => number_format(($item->quantity * $item->unit_price) - ($item->discount ?? 0) + ($item->tax_amount ?? 0), 2, '.', ''),
                'unit_of_measure' => $item->unit_of_measure ?? 'EA',
            ];
        }

        return [
            'invoice_type' => $invoice->is_proforma ? 'proforma' : 'standard',
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date->toDateString(),
            'invoice_time' => $invoice->invoice_date->format('H:i:s'),
            'seller' => [
                'name' => $invoice->business->companyName ?? config('app.name'),
                'tax_number' => $invoice->business->tax_number ?? '',
                'address' => $invoice->business->address ?? '',
                'city' => $invoice->business->city ?? '',
                'country' => $invoice->business->country ?? 'SA',
            ],
            'buyer' => [
                'name' => $invoice->party->name ?? 'Walk-in Customer',
                'tax_number' => $invoice->party->tax_id ?? '',
                'address' => $invoice->party->address ?? '',
                'city' => $invoice->party->city ?? '',
                'country' => $invoice->party->country ?? 'SA',
            ],
            'currency' => 'SAR',
            'payment_terms' => $invoice->payment_method ?? 'cash',
            'items' => $items,
            'totals' => [
                'subtotal' => number_format($invoice->subtotal, 2, '.', ''),
                'discount' => number_format($invoice->discount_amount ?? 0, 2, '.', ''),
                'tax' => number_format($invoice->tax_amount, 2, '.', ''),
                'total' => number_format($invoice->total_amount, 2, '.', ''),
                'paid' => number_format($invoice->paid_amount ?? 0, 2, '.', ''),
                'balance' => number_format($invoice->balance, 2, '.', ''),
            ],
        ];
    }

    /**
     * Submit to e-invoicing portal (simulated).
     *
     * @param array $payload
     * @param Invoice $invoice
     * @return array
     */
    protected function submitToPortal(array $payload, Invoice $invoice): array
    {
        // In production, this would make actual API call to ZATCA, Egypt e-invoice, etc.
        // For now, simulate the response
        
        $uuid = 'EINV-' . strtoupper(uniqid());
        $externalId = 'EXT-' . strtoupper(uniqid());

        // Simulate API call delay
        usleep(100000); // 100ms

        return [
            'success' => true,
            'uuid' => $uuid,
            'external_id' => $externalId,
            'message' => 'Invoice submitted successfully',
        ];
    }

    /**
     * Sync invoice status from e-invoicing portal.
     *
     * @param Invoice $invoice
     * @return array
     */
    public function syncInvoiceStatus(Invoice $invoice): array
    {
        if (empty($invoice->einvoice_uuid)) {
            return [
                'success' => false,
                'message' => 'No e-invoicing UUID found',
            ];
        }

        try {
            // In production, this would query the e-invoicing portal
            // For now, simulate the response
            $status = $this->getInvoiceStatusFromPortal($invoice->einvoice_uuid);

            $invoice->update([
                'einvoice_status' => $status['status'],
                'einvoice_synced_at' => now(),
            ]);

            return [
                'success' => true,
                'status' => $status['status'],
                'message' => 'Status synced successfully',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get invoice status from e-invoicing portal (simulated).
     *
     * @param string $uuid
     * @return array
     */
    protected function getInvoiceStatusFromPortal(string $uuid): array
    {
        // In production, this would query the e-invoicing portal
        // For now, simulate the response
        $statuses = ['submitted', 'validated', 'accepted', 'rejected'];
        
        return [
            'status' => $statuses[array_rand($statuses)],
            'validation_details' => [
                'structure_valid' => true,
                'content_valid' => true,
                'signature_valid' => true,
            ],
        ];
    }

    /**
     * Cancel submitted invoice in e-invoicing portal.
     *
     * @param Invoice $invoice
     * @param string $reason
     * @return array
     */
    public function cancelInvoice(Invoice $invoice, string $reason): array
    {
        if (empty($invoice->einvoice_uuid)) {
            return [
                'success' => false,
                'message' => 'Invoice not submitted to e-invoicing portal',
            ];
        }

        if ($invoice->einvoice_status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Invoice already cancelled',
            ];
        }

        try {
            // In production, this would call the cancellation API
            // For now, simulate the response
            
            $invoice->update([
                'einvoice_status' => 'cancelled',
                'einvoice_cancelled_at' => now(),
                'einvoice_cancellation_reason' => $reason,
            ]);

            return [
                'success' => true,
                'message' => 'Invoice cancelled successfully in e-invoicing portal',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Cancellation failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get e-invoicing portal status.
     *
     * @return array
     */
    public function getPortalStatus(): array
    {
        // In production, this would check the e-invoicing portal health
        // For now, simulate the response
        
        return [
            'status' => 'operational',
            'last_sync' => now()->toIso8601String(),
            'api_version' => '1.0.0',
            'maintenance_windows' => [],
            'supported_features' => [
                'standard_invoice' => true,
                'simplified_invoice' => true,
                'credit_note' => true,
                'debit_note' => true,
                'qr_code' => true,
                'digital_signature' => true,
            ],
        ];
    }

    /**
     * Generate invoice PDF with QR code.
     *
     * @param Invoice $invoice
     * @return string PDF content
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $qrCode = $this->generateQrCode($invoice);
        
        // In production, this would use a PDF library like Dompdf or TCPDF
        // For now, return HTML that can be converted to PDF
        $html = view('einvoice.pdf', [
            'invoice' => $invoice,
            'qrCode' => $qrCode,
        ])->render();

        return $html;
    }

    /**
     * Batch submit multiple invoices.
     *
     * @param array $invoiceIds
     * @return array
     */
    public function batchSubmitInvoices(array $invoiceIds): array
    {
        $results = [
            'total' => count($invoiceIds),
            'successful' => 0,
            'failed' => 0,
            'details' => [],
        ];

        foreach ($invoiceIds as $invoiceId) {
            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                $results['failed']++;
                $results['details'][] = [
                    'invoice_id' => $invoiceId,
                    'success' => false,
                    'message' => 'Invoice not found',
                ];
                continue;
            }

            $result = $this->submitInvoice($invoice);
            $results['details'][] = array_merge(['invoice_id' => $invoiceId], $result);

            if ($result['success']) {
                $results['successful']++;
            } else {
                $results['failed']++;
            }
        }

        return $results;
    }
}