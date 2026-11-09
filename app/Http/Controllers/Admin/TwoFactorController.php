<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Totp;
use Illuminate\Http\Request;

/** TOTP 2FA: enable (secret+QR), verify (activates), disable (password). */
class TwoFactorController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $enabled = (bool) $user->two_factor_secret;

        return view('admin.profile.twofactor', [
            'enabled' => $enabled,
            // Never expose existing secret — only show during enrollment via enable()
            'secret' => null,
            'uri' => null,
        ]);
    }

    public function enable(Request $request)
    {
        $user = $request->user();
        $secret = Totp::generateSecret();
        // keep in session until verified so a stray secret never locks anyone out
        $request->session()->put('2fa_pending_secret', $secret);

        return response()->json([
            'secret' => $secret,
            'uri' => Totp::uri($secret, $user->email ?? 'user', config('app.name')),
        ]);
    }

    public function verify(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|size:6']);
        $secret = $request->session()->get('2fa_pending_secret') ?: $request->user()->two_factor_secret;

        if (!$secret || !Totp::verify($secret, $data['code'])) {
            return response()->json(['success' => false, 'message' => __('كود غير صحيح')], 422);
        }

        $request->user()->update(['two_factor_secret' => $secret]);
        $request->session()->forget('2fa_pending_secret');

        return response()->json(['success' => true, 'message' => __('تم تفعيل التحقق بخطوتين')]);
    }

    public function disable(Request $request)
    {
        $data = $request->validate(['password' => 'required|string']);
        if (!\Illuminate\Support\Facades\Hash::check($data['password'], $request->user()->password)) {
            return response()->json(['success' => false, 'message' => __('كلمة المرور غير صحيحة')], 422);
        }

        $request->user()->update(['two_factor_secret' => null]);

        return response()->json(['success' => true, 'message' => __('أُلغي التحقق بخطوتين')]);
    }
}
