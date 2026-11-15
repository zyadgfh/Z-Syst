<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function signUp(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|min:8|max:100',
            'email' => 'required|email|max:255',
        ]);

        $code = random_int(100000, 999999);
        $expire = now()->addMinutes((int) config('auth.otp_visibility_minutes', 10));
        $data = [
            'code' => $code,
            'name' => $request->name,
        ];

        $existing = User::where('email', $request->email)->first();
        if ($existing && $existing->business_id) {
            return response()->json([
                'message' => 'This email is already exists.',
            ], 406);
        }
        // Prevent overwriting an existing superadmin / privileged account via sign-up
        if ($existing && in_array($existing->role, ['superadmin', 'admin'], true)) {
            return response()->json([
                'message' => 'This email is already registered.',
            ], 406);
        }

        if (! config('mail.from.address') || ! config('mail.mailers.smtp.host')) {
            // Fallback to env check for backwards compat
            if (! config('mail.from.address')) {
                return response()->json([
                    'message' => __('Mail service is not configured. Please contact your administrator.'),
                ], 406);
            }
        }

        if (config('mail.from.address')) {
            try {
                if (config('queue.default') !== 'sync' && config('mail.queue', false)) {
                    Mail::to($request->email)->queue(new WelcomeMail($data));
                } else {
                    Mail::to($request->email)->send(new WelcomeMail($data));
                }
            } catch (\Throwable $e) {
                report($e);
                return response()->json(['message' => __('Failed to send verification email.')], 500);
            }
        } else {
            return response()->json([
                'message' => __('Mail service is not configured. Please contact your administrator.'),
            ], 406);
        }

        // Explicit allowlist — never mass-assign role/business_id/status
        $user = User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'remember_token' => $code,
                'email_verified_at' => $expire,
                'password' => Hash::make($request->password),
            ]
        );

        return response()->json([
            'message' => 'An otp code has been sent to your email. Please check and confirm.',
            'data' => ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
        ]);
    }

    public function submitOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|min:4|max:15',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return response()->json([
                'status' => 404,
                'message' => __('User not found'),
            ], 404);
        }

        if (! hash_equals((string) $user->remember_token, (string) $request->otp)) {
            return response()->json([
                'error' => __('Invalid otp.'),
            ], 404);
        }

        if (! $user->email_verified_at || $user->email_verified_at <= now()) {
            return response()->json([
                'error' => __('The verification otp has been expired.'),
            ], 400);
        }

        Auth::login($user);
        $is_setup = (bool) $user->business_id;
        $token = $user->createToken('createToken')->plainTextToken;
        $accessToken = $user->createToken('createToken');
        $this->setAccessTokenExpiration($accessToken);

        $user->update([
            'remember_token' => null,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'message' => 'Logged In successfully!',
            'is_setup' => $is_setup,
            'token' => $token,
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email' => 'required|email',
        ]);

        if (auth()->attempt($request->only('email', 'password'))) {
            $user = auth()->user();

            if ($user->status !== null && $user->status !== 'active' && $user->status !== 1 && $user->status !== '1') {
                auth()->logout();
                return response()->json(['message' => __('Account is disabled.')], 403);
            }

            if ($user->role != 'staff' && $user->role != 'shop-owner') {
                return response()->json([
                    'message' => 'You can not login as '.$user->role.' from the app!',
                ], 406);
            }

            if ($user->remember_token && ! $user->business_id) {
                $code = random_int(100000, 999999);
                $expire = now()->addMinutes((int) config('auth.otp_visibility_minutes', 10));
                $data = [
                    'code' => $code,
                    'name' => $user->name,
                ];

                try {
                    if (config('queue.default') !== 'sync' && config('mail.queue', false)) {
                        Mail::to($request->email)->queue(new WelcomeMail($data));
                    } else {
                        Mail::to($request->email)->send(new WelcomeMail($data));
                    }
                } catch (\Throwable $e) {
                    report($e);
                    return response()->json(['message' => __('Failed to send verification email.')], 500);
                }

                User::where('email', $request->email)->first()?->update(['remember_token' => $code, 'email_verified_at' => $expire]);

                return response()->json([
                    'message' => 'An otp code has been sent to your email. Please check and confirm.',
                ], 201);
            }

            return response()->json([
                'message' => 'User login successfully!',
                'data' => [
                    'message' => 'Logged In successfully!',
                    'is_setup' => $user->business_id ? true : false,
                    'token' => $user->createToken('createToken')->plainTextToken,
                ],
            ]);
        } else {
            // Same audit trail as the web login: the route-level throttle:10,1
            // bounds how fast an address can try, and this row is what remains
            // afterwards to show that somebody did. Identifier only — the
            // password never reaches the log. Wrapped so an audit failure can
            // never change the response the app receives.
            try {
                app(\App\Services\AuditService::class)->logFailedLogin((string) $request->input('email'));
            } catch (\Throwable $e) {
                report($e);
            }

            return response()->json([
                'message' => 'Invalid email or password!',
            ], 404);
        }
    }

    protected function setAccessTokenExpiration(NewAccessToken $accessToken)
    {
        $expiration = now()->addMinutes(Config::get('sanctum.expiration', 60));

        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update(['expires_at' => $expiration]);
    }

    public function signOut(): JsonResponse
    {
        if (auth()->user()?->tokens()) {
            auth()->user()->tokens()->delete();

            return response()->json([
                'message' => __('Sign out successfully'),
            ]);
        } else {
            return response()->json([
                'message' => __('Unauthorized'),
            ], 401);
        }
    }

    public function refreshToken()
    {
        if (auth()->user()?->tokens()) {
            auth()->user()->currentAccessToken()->delete();
            $data['token'] = auth()->user()->createToken('createToken')->plainTextToken;

            return response()->json($data);
        } else {
            return response()->json([
                'message' => __('Unauthorized'),
            ], 401);
        }
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $code = random_int(100000, 999999);
        $expire = now()->addMinutes((int) config('auth.otp_visibility_minutes', 10));
        $data = [
            'code' => $code,
            'name' => $request->name,
        ];

        try {
            if (config('queue.default') !== 'sync' && config('mail.queue', false)) {
                Mail::to($request->email)->queue(new WelcomeMail($data));
            } else {
                Mail::to($request->email)->send(new WelcomeMail($data));
            }
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['message' => __('Failed to send verification email.')], 500);
        }

        User::where('email', $request->email)->first()?->update(['remember_token' => $code, 'email_verified_at' => $expire]);

        return response()->json([
            'message' => 'An otp code has been sent to your email. Please check and confirm.',
        ]);
    }
}
