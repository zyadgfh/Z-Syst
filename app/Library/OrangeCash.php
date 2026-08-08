<?php

namespace App\Library;

use App\Models\Gateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class OrangeCash
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

        $data['payment_mode'] = 'orange_cash';
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
        $data['merchant_phone'] = $array['merchant_phone'] ?? '';
        $data['merchant_name'] = $array['merchant_name'] ?? '';

        Session::put('orange_cash_credentials', $data);

        // Orange Cash is manual - redirect to payment page with instructions
        return request()->expectsJson() ? route('orange-cash.view') : redirect()->route('orange-cash.view');
    }

    public function view()
    {
        if (Session::has('orange_cash_credentials')) {
            $info = Session::get('orange_cash_credentials');
            $gateway = Gateway::where('status', 1)->findOrFail($info['gateway_id']);

            return view('payments.orange-cash', compact('info', 'gateway'));
        }
        abort(404);
    }

    public function status(Request $request)
    {
        if (Session::has('orange_cash_credentials')) {
            $order_info = Session::get('orange_cash_credentials');

            // For Orange Cash, this is manual verification
            $data['payment_method'] = 'Orange Cash';
            $data['gateway_id'] = $order_info['gateway_id'];
            $data['amount'] = $order_info['amount'];
            $data['billName'] = $order_info['billName'];
            $data['charge'] = $order_info['charge'];
            $data['status'] = 'pending';
            $data['payment_status'] = 'pending';
            $data['transaction_id'] = $request->transaction_id ?? '';
            $data['sender_phone'] = $request->sender_phone ?? '';

            Session::put('payment_info', $data);
            Session::forget('orange_cash_credentials');

            return request()->expectsJson() ?
            OrangeCash::redirect_if_payment_success() :
            redirect(OrangeCash::redirect_if_payment_success());
        }
        abort(404);
    }
}