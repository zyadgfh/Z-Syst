<?php

namespace App\Library;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class BankCard
{
    public static function redirect_if_payment_success()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['success_url']);
        } else {
            return url('payment/success');
        }
    }

    public static function redirect_if_payment_faild()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['cancel_url']);
        } else {
            return url('payment/failed');
        }
    }

    public static function make_payment($array)
    {
        $gateway = Gateway::findOrFail($array['gateway_id']);
        $amount = $array['pay_amount'];

        $data['payment_mode'] = 'bank_card';
        $data['amount'] = $amount;
        $data['charge'] = $array['charge'];
        $data['main_amount'] = $array['amount'];
        $data['gateway_id'] = $array['gateway_id'];
        $data['payment_type'] = $array['payment_type'] ?? '';
        $data['billName'] = $array['billName'];
        $data['name'] = $array['name'];
        $data['email'] = $array['email'];
        $data['phone'] = $array['phone'];
        $data['currency'] = $array['currency'];
        $data['bank_name'] = $array['bank_name'] ?? '';
        $data['account_number'] = $array['account_number'] ?? '';
        $data['merchant_name'] = $array['merchant_name'] ?? '';

        Session::put('bank_card_credentials', $data);

        // Bank Card is manual - redirect to payment page with instructions
        return request()->expectsJson() ? route('bank-card.view') : redirect()->route('bank-card.view');
    }

    public function view()
    {
        if (Session::has('bank_card_credentials')) {
            $info = Session::get('bank_card_credentials');
            $gateway = Gateway::where('status', 1)->findOrFail($info['gateway_id']);

            return view('payments.bank-card', compact('info', 'gateway'));
        }
        abort(404);
    }

    public function status(Request $request)
    {
        if (Session::has('bank_card_credentials')) {
            $order_info = Session::get('bank_card_credentials');

            // For Bank Card, this is manual verification
            $data['payment_method'] = 'Bank Card';
            $data['gateway_id'] = $order_info['gateway_id'];
            $data['amount'] = $order_info['amount'];
            $data['billName'] = $order_info['billName'];
            $data['charge'] = $order_info['charge'];
            $data['status'] = 'pending';
            $data['payment_status'] = 'pending';
            $data['transaction_id'] = $request->transaction_id ?? '';
            $data['card_last_four'] = $request->card_last_four ?? '';

            Session::put('payment_info', $data);
            Session::forget('bank_card_credentials');

            return request()->expectsJson() ?
            BankCard::redirect_if_payment_success() :
            redirect(BankCard::redirect_if_payment_success());
        }
        abort(404);
    }
}