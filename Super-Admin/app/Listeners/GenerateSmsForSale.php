<?php

namespace App\Listeners;

use App\Events\SaleSms;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\InteractsWithQueue;
use Modules\MarketingAddon\App\Models\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateSmsForSale
{
    /**
     * Handle the event.
     */
    public function handle(SaleSms $event): void
    {
        try {
            $sale = $event->sale;
            $party = $sale->party;
            $business = $sale->business;

            $sms_settings = get_option('sms-templates-' . $business->id);
            $message = $sms_settings['sale_sms'] ?? false;

            if (($sms_settings['status_for_sale'] ?? false) && $message && $party->phone) {

                sendMessage([$party->phone], $message, $business->id);

                $data = [
                    'customer_name' => $party->name,
                    'invoice_no' => $sale->invoiceNumber,
                    'amount' => $sale->totalAmount,
                    'date' => formatted_date($sale->saleDate),
                    'business_name' => $business->companyName,
                    'business_phone' => $business->phoneNumber
                ];

                foreach ($data as $key => $value) {
                    $message = str_replace("[$key]", $value, $message);
                }

                Sms::create([
                    'type'          => 'sms',
                    'schedule'      => now(),
                    'user_id'       => Auth::id(),
                    'number'        => $party->phone,
                    'business_id'   => $business->id,
                    'content'       => $message,
                    'smsgateway_id' => session('gateway_id'),
                    'sms_for'       => 'sale',
                    'status'        => get_option('sms-gateway-setting-1') == 'api' ? 'success' : 'pending',
                ]);

                session()->forget('gateway_id');
            }
        } catch (\Throwable $th) {
            Log::error('Failed to send SMS: '.$th->getMessage());
        }
    }
}
