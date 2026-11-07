<?php

namespace App\Services;

use App\Models\EtaInvoice;
use App\Models\Sale;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

/**
 * Egyptian Tax Authority e-invoicing (ETA) submission.
 *
 * Drivers:
 *  - "simulation" (default, no credentials needed): builds the full UBL 2.0
 *    payload per ETA spec and fakes a document-internal UUID + acceptance.
 *  - "api": POSTs to the ETA ID/PR endpoints with the tenant credentials
 *    stored in business settings; refreshes the bearer token and submits.
 *
 * Settings keys (business_settings): eta_driver, eta_client_id, eta_client_secret,
 * eta_issuer_rin, eta_issuer_name, eta_branch_code, eta_sandbox (bool).
 */
class EtaInvoiceService
{
    public function driver(int $businessId): string
    {
        return BusinessSetting::get($businessId, 'eta_driver', 'simulation');
    }

    public function submit(Sale $sale): EtaInvoice
    {
        $businessId = (int) $sale->business_id;

        $existing = EtaInvoice::where('sale_id', $sale->id)
            ->whereIn('status', ['pending', 'submitted', 'accepted'])->first();
        if ($existing) {
            return $existing;
        }

        $payload = $this->buildDocument($sale);
        $invoice = EtaInvoice::create([
            'business_id' => $businessId,
            'sale_id' => $sale->id,
            'request_payload' => $payload,
            'status' => 'pending',
        ]);

        if ($this->driver($businessId) === 'api') {
            $this->submitViaApi($invoice, $payload, $businessId);
        } else {
            // Simulation: deterministic offline acceptance.
            $invoice->update([
                'status' => 'accepted',
                'submission_uuid' => (string) \Illuminate\Support\Str::uuid(),
                'long_id' => 'SIM-'.now()->format('YmdHis'),
                'response_payload' => ['simulation' => true, 'acceptedAt' => now()->toIso8601String()],
                'submitted_at' => now(),
            ]);
        }

        return $invoice->fresh();
    }

    /** UBL 2.0 JSON per ETA SDK spec (simplified invoice shape). */
    public function buildDocument(Sale $sale): array
    {
        $businessId = (int) $sale->business_id;
        $issuerRin = BusinessSetting::get($businessId, 'eta_issuer_rin', '000000000000003');
        $issuerName = BusinessSetting::get($businessId, 'eta_issuer_name', 'Pharmacy');

        $lines = $sale->details()->with('product')->get();
        $items = [];
        foreach ($lines as $i => $detail) {
            $net = round((float) $detail->price * (int) $detail->quantities, 5);
            $items[] = [
                'description' => $detail->product?->name ?? 'Item',
                'itemType' => 'GS1',
                'itemCode' => 'EG-'.($detail->product?->productCode ?? $detail->product_id),
                'unitType' => 'URE',
                'quantity' => (int) $detail->quantities,
                'unitPrice' => round((float) $detail->price, 5),
                'salesTotal' => $net,
                'total' => $net,
                'valueDifference' => 0,
                'totalTaxableFees' => 0,
                'netTotal' => $net,
                'itemsDiscount' => 0,
                'unitValue' => ['currencySold' => 'EGP', 'amountEGP' => round((float) $detail->price, 5)],
                'taxableItems' => [[
                    'taxType' => 'T1', // VAT
                    'amount' => round($net * 0.14, 5),
                    'subType' => 'V001',
                    'rate' => 14,
                ]],
            ];
        }

        $salesTotal = round((float) collect($items)->sum('salesTotal'), 5);
        $vatTotal = round($salesTotal * 0.14, 5);

        return [
            'issuer' => [
                'type' => 'B', 'id' => $issuerRin, 'name' => $issuerName,
                'address' => [
                    'country' => 'EG', 'governate' => BusinessSetting::get($businessId, 'eta_governate', 'Cairo'),
                    'regionCity' => BusinessSetting::get($businessId, 'eta_city', 'Nasr City'),
                    'street' => BusinessSetting::get($businessId, 'eta_street', 'Main St'),
                    'buildingNumber' => BusinessSetting::get($businessId, 'eta_building', '0'),
                    'postalCode' => BusinessSetting::get($businessId, 'eta_postal', '11511'),
                ],
            ],
            'receiver' => [
                'type' => 'P', 'id' => null, 'name' => $sale->party?->name ?? 'Walk-in customer',
                'address' => ['country' => 'EG', 'governate' => 'Cairo', 'regionCity' => 'Cairo', 'street' => '-', 'buildingNumber' => '0'],
            ],
            'documentType' => 'I', 'documentTypeVersion' => '1.0',
            'dateTimeIssued' => now()->format('Y-m-d\TH:i:s\Z'),
            'taxpayerActivityCode' => BusinessSetting::get($businessId, 'eta_activity_code', '4771'),
            'invoiceLines' => $items,
            'totalDiscountAmount' => round((float) ($sale->discountAmount ?? 0), 5),
            'totalSalesAmount' => $salesTotal,
            'netAmount' => $salesTotal,
            'taxTotals' => [['taxType' => 'T1', 'amount' => $vatTotal]],
            'totalAmount' => round($salesTotal + $vatTotal, 5),
            'extraDiscountAmount' => 0,
            'totalItemsDiscountAmount' => 0,
        ];
    }

    protected function submitViaApi(EtaInvoice $invoice, array $payload, int $businessId): void
    {
        $sandbox = filter_var(BusinessSetting::get($businessId, 'eta_sandbox', '1'), FILTER_VALIDATE_BOOL);
        $base = $sandbox ? 'https://api.invoicing.eta.gov.eg' : 'https://api.invoicing.horizon.eta.gov.eg';
        $clientId = BusinessSetting::get($businessId, 'eta_client_id');
        $clientSecret = BusinessSetting::get($businessId, 'eta_client_secret');

        if (!$clientId || !$clientSecret) {
            $invoice->update(['status' => 'error', 'error_message' => 'ETA credentials missing (eta_client_id / eta_client_secret).']);
            return;
        }

        try {
            $token = Http::asForm()->post("$base/api/v1/auth/token", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ])->throw()->json('access_token');

            $resp = Http::withToken($token)
                ->post("$base/api/v1/documentsubmissions", ['documents' => [$payload]])
                ->throw()->json();

            $doc = Arr::first($resp['documents'] ?? [], fn ($d) => true, []);
            $invoice->update([
                'status' => $doc['status'] ?? 'submitted',
                'submission_uuid' => $resp['submissionUUID'] ?? null,
                'long_id' => $doc['longId'] ?? null,
                'response_payload' => $resp,
                'submitted_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $invoice->update(['status' => 'error', 'error_message' => substr($e->getMessage(), 0, 500)]);
        }
    }
}
