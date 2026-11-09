<?php

namespace App\Services\Integrations;

use App\Models\Sale;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * EtaService — Egyptian Tax Authority e-invoicing (eReceipt/eInvoice).
 *
 * Scope here: build the ETA document JSON for a POS sale and submit it
 * when credentials are configured. There are two regimes:
 *   - B2C eReceipt  (POS receipts, simpler document)
 *   - B2B eInvoice  (registered buyers, requires buyer tax ID)
 * The payload builder below targets the B2B eInvoice document shape
 * (the strictest one); B2C eReceipt can reuse the item mapping.
 *
 * Config: config/integrations.php (ETA_* envs). Disabled by default.
 */
class EtaService
{
    public function enabled(): bool
    {
        return (bool) config('integrations.eta.enabled')
            && config('integrations.eta.client_id')
            && config('integrations.eta.client_secret');
    }

    /**
     * Map a POS sale onto the ETA eInvoice document structure.
     */
    public function buildDocument(Sale $sale, array $buyer = []): array
    {
        $lines = $sale->saleDetails()->get();
        $items = [];
        $total = 0.0;
        $totalSales = 0.0;

        foreach ($lines as $line) {
            $qty = (float) $line->qty;
            $unitPrice = (float) $line->price;
            $net = round($qty * $unitPrice, 5);
            $totalSales += $net;

            $items[] = [
                'description' => $line->product->productName ?? ('item-' . $line->product_id),
                'itemType' => 'GS1',
                'itemCode' => $line->product->productCode ?? ('P-' . $line->product_id),
                'unitType' => 'LTR', // TODO: map units to ETA unit types (LTR/KGM/EA…)
                'quantity' => $qty,
                'unitPrice' => $unitPrice,
                'salesTotal' => $net,
                'total' => $net,
                'valueDifference' => 0,
                'totalTaxableFees' => 0,
                'netTotal' => $net,
                'itemsDiscount' => 0,
                'taxableItems' => [], // TODO: map business tax lines when tax regs settle
            ];
            $total += $net;
        }

        $discount = max(0.0, $totalSales - (float) $sale->total_amount);

        return [
            'issuer' => [
                'type' => 'B',
                'id' => (string) config('integrations.eta.issuer_tax_id'),
                'name' => (string) config('integrations.eta.issuer_name', 'Pharmacy'),
                'address' => [
                    'country' => 'EG',
                    'governate' => (string) config('integrations.eta.issuer_governate', 'Cairo'),
                    'regionCity' => (string) config('integrations.eta.issuer_city', 'Cairo'),
                    'street' => (string) config('integrations.eta.issuer_street', '-'),
                    'buildingNumber' => '0',
                ],
            ],
            'receiver' => [
                'type' => $buyer['tax_id'] ?? null ? 'B' : 'P',
                'id' => $buyer['tax_id'] ?? null,
                'name' => $buyer['name'] ?? 'Walk-in Customer',
                'address' => [
                    'country' => 'EG',
                    'governate' => $buyer['governate'] ?? 'Cairo',
                    'regionCity' => $buyer['city'] ?? 'Cairo',
                    'street' => $buyer['street'] ?? '-',
                    'buildingNumber' => '0',
                ],
            ],
            'documentType' => 'I', // Invoice
            'documentTypeVersion' => '1.0',
            'dateTimeIssued' => ($sale->created_at ?? now())->toIso8601String(),
            'taxpayerActivityCode' => (string) config('integrations.eta.activity_code', '4772'), // pharmacy retail
            'invoiceLines' => $items,
            'totalDiscountAmount' => round($discount, 5),
            'totalSalesAmount' => round($totalSales, 5),
            'netAmount' => round($totalSales - $discount, 5),
            'taxTotals' => [],
            'total_amount' => round((float) $sale->total_amount, 5),
            'extraDiscountAmount' => 0,
            'totalItemsDiscountAmount' => 0,
        ];
    }

    /**
     * Submit a document for signature/submission through the ETA API.
     * Returns [ok, submissionUuid|error].
     */
    public function submit(array $document): array
    {
        if (! $this->enabled()) {
            return [false, 'ETA not configured (integrations.eta.*)'];
        }

        $base = rtrim(config('integrations.eta.base_url', 'https://api.invoicing.eta.gov.eg/api/v1'), '/');

        try {
            $tokenResponse = Http::asForm()->timeout(config('integrations.http_timeout', 20))->post($base . '/token', [
                'grant_type' => 'client_credentials',
                'client_id' => config('integrations.eta.client_id'),
                'client_secret' => config('integrations.eta.client_secret'),
                'scope' => 'InvoicingAPI',
            ]);
            if (! $tokenResponse->successful()) {
                return [false, 'token: HTTP ' . $tokenResponse->status()];
            }
            $token = $tokenResponse->json('access_token');

            // Production requires a pre-signed document (CSR/USB token);
            // the sandbox accepts raw documents — kept configurable.
            $submitResponse = Http::withToken($token)
                ->timeout(config('integrations.http_timeout', 20))
                ->post($base . '/documents' . (config('integrations.eta.sandbox') ? '/sandbox' : ''), [
                    'documents' => [$document],
                ]);

            if (! $submitResponse->successful()) {
                Log::warning('ETA submit failed', ['status' => $submitResponse->status()]);
                return [false, 'submit: HTTP ' . $submitResponse->status()];
            }

            return [true, $submitResponse->json('submissionUUID')];
        } catch (\Throwable $e) {
            Log::warning('ETA submit error', ['error' => $e->getMessage()]);
            return [false, $e->getMessage()];
        }
    }
}
