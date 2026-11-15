<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RegistrationMail;
use App\Mail\WelcomeMail;
use App\Models\Business;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\NewAccessToken;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        return response()->json(['message' => 'Email-based registration and verification are disabled.'], 503);
    }

    public function otpResend(Request $request)
    {
        return response()->json(['message' => 'Email-based registration and verification are disabled.'], 503);
    }

    public function otpSubmit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|min:4|max:15',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json(['message' => __('User not found.')], 400);
        }

        if ($user->remember_token == $request->otp) {
            if ($user->email_verified_at > now()) {
                $user->update([
                    'remember_token' => null,
                    'email_verified_at' => now(),
                ]);

                return response()->json([
                    'message' => 'OTP verified successfully. Please confirm your action in the modal.',
                    'openModal' => true,
                    'email' => $request->email,
                ]);
            } else {
                return response()->json(['message' => __('The verification otp has been expired.')], 400);
            }
        } else {
            return response()->json(['message' => __('Invalid otp.')], 400);
        }
    }

    protected function setAccessTokenExpiration(NewAccessToken $accessToken)
    {
        $expiration = now()->addMinutes(Config::get('sanctum.expiration'));

        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update(['expires_at' => $expiration]);
    }
}
