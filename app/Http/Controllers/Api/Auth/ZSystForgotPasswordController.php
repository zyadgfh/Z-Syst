<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordReset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ZSystForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $key = 'pwd-reset:'.strtolower($request->email).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return response()->json(['message' => __('Too many attempts. Try again later.')], 429);
        }
        RateLimiter::hit($key, 900);

        $expire = now()->addHour();
        $code = random_int(100000, 999999);
        $user = User::where('email', $request->email)->firstOrFail();
        $user->update(['remember_token' => (string) $code, 'email_verified_at' => $expire]);

        $data = ['code' => $code];

        try {
            if (config('queue.default') !== 'sync' && config('mail.queue', false)) {
                Mail::to($request->email)->queue(new PasswordReset($data));
            } else {
                Mail::to($request->email)->send(new PasswordReset($data));
            }

            return response()->json(['message' => 'Password reset code has been sent to your email.']);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(['message' => __('Failed to send reset code.')], 500);
        }
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'code' => 'required|integer',
            'email' => 'required|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (! hash_equals((string) $user->remember_token, (string) $request->code)) {
            return response()->json(['error' => __('Invalid Code.')], 404);
        }
        if (! $user->email_verified_at || $user->email_verified_at <= now()) {
            return response()->json(['error' => __('The verification code has expired.')], 400);
        }

        // Mark verified without consuming — consumption happens on reset
        cache()->put('pwd_verified:'.$user->id, true, 900);

        return response()->json(['message' => __('The code has been verified.')]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|exists:users,email',
            'code' => 'required|integer',
            'password' => ['required', 'min:8', 'max:100', 'confirmed'],
            'password_confirmation' => ['required'],
        ]);

        $user = User::where('email', $request->email)->firstOrFail();

        if (! hash_equals((string) $user->remember_token, (string) $request->code)) {
            return response()->json(['error' => __('Invalid Code.')], 404);
        }
        if (! $user->email_verified_at || $user->email_verified_at <= now()) {
            return response()->json(['error' => __('The verification code has expired.')], 400);
        }
        if (! cache()->get('pwd_verified:'.$user->id)) {
            return response()->json(['error' => __('Please verify the code first.')], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'remember_token' => null,
            'email_verified_at' => null,
        ]);
        cache()->forget('pwd_verified:'.$user->id);
        // Invalidate all existing tokens
        try { $user->tokens()->delete(); } catch (\Throwable $e) {}

        return response()->json(['message' => 'Your password has been changed!']);
    }
}
