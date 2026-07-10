<?php

namespace App\Http\Controllers\Admin;

use App\Models\Option;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AcnooSettingsManagerController extends Controller
{
    public function index()
    {
        $otp = Option::where('key', 'email-varification')->first();
        $domain = Option::where('key', 'domain-setting')->first();
        return view('admin.manage-settings.index', compact('otp', 'domain'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'otp_status' => 'required|string|max:100|in:on,off',
            'otp_expiration_time' => 'nullable|string|max:100',
            'otp_duration_type' => 'nullable|string|max:100|in:minute,second',
        ]);

        $otpExpiration = $request->otp_status === 'on' ? $request->otp_expiration_time : null;
        $otpDurationType = $request->otp_status === 'on' ? $request->otp_duration_type : null;

        Option::updateOrCreate(
            ['key' => 'email-varification'],
            ['value' => [
                'otp_status' => $request->otp_status,
                'otp_expiration_time' => $otpExpiration,
                'otp_duration_type' => $otpDurationType,
            ]]
        );

        Cache::forget('email-varification');

        return response()->json(__('Otp setting updated successfully.'));
    }

    public function domain(Request $request)
    {
        $request->validate([
            'ssl_required' => 'required|string|max:100|in:on,off',
            'automatic_approve' => 'required|string|max:100|in:on,off',
        ]);

        Option::updateOrCreate(
            ['key' => 'domain-setting'],
            ['value' => [
                'ssl_required' => $request->ssl_required,
                'automatic_approve' => $request->automatic_approve,
            ]]
        );

        Cache::forget('domain-setting');

        return response()->json(__('Domain setting updated successfully.'));
    }
}
