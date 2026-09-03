<?php

namespace App\Services;

use App\Mail\RecallNotificationMail;
use App\Models\RecallEvent;
use App\Models\Sale;
use App\Models\SaleDetails;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class RecallNotificationService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Notify all affected customers for a recall event.
     *
     * Finds all sales that included the recalled product/batch,
     * then sends email + SMS to each unique customer.
     *
     * @return array Summary of notifications sent
     */
    public function notifyAffectedCustomers(RecallEvent $recall): array
    {
        $affectedSales = $this->findAffectedSales($recall);

        $notifiedCustomers = [];
        $emailSent = 0;
        $smsSent = 0;
        $failed = 0;

        // Group by party (customer) to avoid duplicate notifications
        $customerSales = $affectedSales->groupBy('party_id');

        foreach ($customerSales as $partyId => $sales) {
            $sale = $sales->first();
            $party = $sale->party;

            if (! $party) {
                continue;
            }

            $recallContext = [
                'customer_name' => $party->name,
                'product_name' => $recall->product?->name ?? 'Unknown Product',
                'batch_number' => $recall->batch_lot_number ?? 'Multiple batches',
                'reason' => $recall->reason,
                'description' => $recall->description,
                'recall_date' => $recall->initiated_at->format('Y-m-d'),
                'invoice_numbers' => $sales->pluck('invoiceNumber')->filter()->implode(', '),
                'total_quantity' => $sales->sum('quantity'),
            ];

            // Send email notification
            if ($party->email) {
                try {
                    Mail::to($party->email)->send(new RecallNotificationMail($recall, $recallContext));
                    $emailSent++;
                } catch (\Throwable $e) {
                    Log::error('Failed to send recall email', [
                        'recall_id' => $recall->id,
                        'party_id' => $partyId,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            // Send SMS notification
            if ($party->phone) {
                $smsMessage = $this->buildSmsMessage($recallContext);
                try {
                    $this->smsService->send($party->phone, $smsMessage);
                    $smsSent++;
                } catch (\Throwable $e) {
                    Log::error('Failed to send recall SMS', [
                        'recall_id' => $recall->id,
                        'party_id' => $partyId,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            }

            $notifiedCustomers[] = [
                'party_id' => $partyId,
                'name' => $party->name,
                'email' => $party->email,
                'phone' => $party->phone,
                'sales_count' => $sales->count(),
            ];
        }

        return [
            'recall_id' => $recall->id,
            'total_affected_sales' => $affectedSales->count(),
            'total_customers_found' => $customerSales->count(),
            'notifications_sent' => [
                'emails' => $emailSent,
                'sms' => $smsSent,
                'failed' => $failed,
            ],
            'customers' => $notifiedCustomers,
        ];
    }

    /**
     * Find all sales that included the recalled product/batch.
     */
    public function findAffectedSales(RecallEvent $recall)
    {
        $query = Sale::where('business_id', $recall->business_id)
            ->with(['party:id,name,email,phone', 'details']);

        if ($recall->product_id) {
            $query->whereHas('details', function ($q) use ($recall) {
                $q->where('product_id', $recall->product_id);
            });
        }

        if ($recall->batch_lot_number) {
            $query->whereHas('details', function ($q) use ($recall) {
                $q->where('batch_no', $recall->batch_lot_number);
            });
        }

        return $query->get();
    }

    /**
     * Build SMS message for recall notification.
     */
    protected function buildSmsMessage(array $context): string
    {
        return __(
            "🚨 DRUG RECALL NOTICE\n\nHi :name,\nA product you purchased has been recalled.\n\nProduct: :product\nBatch: :batch\nReason: :reason\n\nPlease stop using this product immediately and contact us for a refund.\n\nThank you,\nPharmacy Team",
            [
                'name' => $context['customer_name'],
                'product' => $context['product_name'],
                'batch' => $context['batch_number'],
                'reason' => $context['reason'],
            ]
        );
    }
}
