<?php

namespace App\Listeners;

use App\Events\DuePaymentReceived;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Modules\MarketingAddon\App\Models\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateSmsForDuePayment
{
    /**
     * Handle the event.
     */
    public function handle(DuePaymentReceived $event)
    {
        try {
            $payment = $event->due_collect;
            $party = $payment->party;
            $business = $payment->business;

            $sms_settings = get_option('sms-templates-' . $business->id);
            $message = $sms_settings[$party->type == 'Supplier' ? 'payment_paid_sms' : 'payment_received_sms'] ?? false;

            if (($party->type == 'Supplier' && ($sms_settings['status_for_payment_paid'] ?? false)) || (in_array($party->type, ['Retailer', 'Dealer', 'Wholesaler']) && ($sms_settings['status_for_payment_received'] ?? false)) && $message && $party->phone) {

                sendMessage([$party->phone], $message, $business->id);

                $data = [
                    'due_amount'      => $party->due - $payment->payDueAmount,
                    'supplier_name'   => $party->name,
                    'customer_name'   => $party->name,
                    'paid_amount'     => $payment->payDueAmount,
                    'invoice_no'      => $payment->invoiceNumber,
                    'received_amount' => $payment->payDueAmount,
                    'date'            => formatted_date($payment->paymentDate),
                    'business_name'   => $business->companyName,
                    'business_phone'  => $business->phoneNumber,
                ];

                foreach ($data as $key => $value) {
                    $message = str_replace("[$key]", $value, $message);
                }

                Sms::create([
                    'type'          => 'sms',
                    'schedule'      => now(),
                    'user_id'       => Auth::id(),
                    'number'        => $party->phone,
                    'business_id'   => $payment->business_id,
                    'content'       => $message,
                    'smsgateway_id' => session('gateway_id'),
                    'sms_for'       => $party->type == 'Supplier' ? 'payment_paid' : 'payment_received',
                    'status'        => get_option('sms-gateway-setting-1') == 'api' ? 'success' : 'pending',
                ]);

                session()->forget('gateway_id');
            }
        } catch (\Throwable $th) {
            Log::error('Failed to send SMS: '.$th->getMessage());
        }
    }
}
