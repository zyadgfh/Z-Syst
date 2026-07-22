<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\SubmitOtpRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function signUp(RegisterRequest $request)
    {
        $code = random_int(100000, 999999);
        $expire = now()->addMinutes((int) (env('OTP_VISIBILITY_TIME') ?? 3));
        $data = [
            'code' => $code,
            'name' => $request->name,
        ];

        $user = User::where('email', $request->email)->first();
        if ($user && $user->business_id) {
            return response()->json([
                'message' => 'This email is already exists.',
            ], 406);
        }

        if (env('MAIL_USERNAME')) {
            if (env('QUEUE_MAIL')) {
                Mail::to($request->email)->queue(new WelcomeMail($data));
            } else {
                Mail::to($request->email)->send(new WelcomeMail($data));
            }
        } else {
            return response()->json([
                'message' => __('Mail service is not configured. Please contact your administrator.'),
            ], 406);
        }

        $user = User::updateOrCreate(
            ['email' => $request->email],
            [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'remember_token' => $code,
                'email_verified_at' => $expire,
            ]
        );

        return response()->json([
            'message' => 'An otp code has been sent to your email. Please check and confirm.',
            'data' => [
                'email' => $request->email,
                'name' => $request->name,
            ],
        ]);
    }

    public function submitOtp(SubmitOtpRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => __('User not found'),
            ], 404);
        }

        if ($user->remember_token == $request->otp) {
            if ($user->email_verified_at > now()) {

                Auth::login($user);
                $is_setup = $user->business_id ? true : false;
                $token = $user->createToken('createToken')->plainTextToken;
                $accessToken = $user->createToken('createToken');
                $this->setAccessTokenExpiration($accessToken);

                $user->update([
                    'remember_token' => NULL,
                    'email_verified_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Logged In successfully!',
                    'is_setup' => $is_setup,
                    'token' => $token,
                ]);

            } else {
                return response()->json([
                    'error' => __('The verification otp has been expired.')
                ], 400);
            }
        } else {
            return response()->json([
                'error' => __('Invalid otp.')
            ], 404);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => __('Invalid credentials'),
            ], 401);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => __('User not found'),
            ], 404);
        }

        if ($user->role != 'staff' && $user->role != 'shop-owner') {
            return response()->json([
                'message' => 'You can not login as ' . $user->role . ' from the app!'
            ], 406);
        }

        if ($user->remember_token && !$user->business_id) { // If user didn't verify email
            $code = random_int(100000, 999999);
            $expire = now()->addMinutes(env('OTP_VISIBILITY_TIME') ?? 3);
            $data = [
                'code' => $code,
                'name' => $user->name,
            ];

            if (env('MAIL_USERNAME')) {
                if (env('QUEUE_MAIL')) {
                    Mail::to($request->input('email'))->queue(new WelcomeMail($data));
                } else {
                    Mail::to($request->input('email'))->send(new WelcomeMail($data));
                }
            } else {
                return response()->json([
                    'message' => __('Mail service is not configured. Please contact your administrator.'),
                ], 406);
            }

            User::where('email', $request->input('email'))->first()->update(['remember_token' => $code, 'email_verified_at' => $expire]);

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
    }

    protected function setAccessTokenExpiration(NewAccessToken $accessToken)
    {
        $expiration = now()->addMinutes(Config::get('sanctum.expiration'));

        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update(['expires_at' => $expiration]);
    }

    public function signOut() : JsonResponse
    {
        if (auth()->user()->tokens()) {
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
        if (auth()->user()->tokens()) {

            auth()->user()->currentAccessToken()->delete();
            $data['token'] = auth()->user()->createToken('createToken')->plainTextToken;
            return response()->json($data);

        } else {
            return response()->json([
                'message' => __('Unauthorized'),
            ], 401);
        }
    }

    public function resendOtp(ResendOtpRequest $request)
    {
        $code = random_int(100000, 999999);
        $expire = now()->addMinutes(env('OTP_VISIBILITY_TIME') ?? 3);
        $data = [
            'code' => $code,
            'name' => $request->name,
        ];

        if (env('MAIL_USERNAME')) {
            if (env('QUEUE_MAIL')) {
                Mail::to($request->email)->queue(new WelcomeMail($data));
            } else {
                Mail::to($request->email)->send(new WelcomeMail($data));
            }
        } else {
            return response()->json([
                'message' => __('Mail service is not configured. Please contact your administrator.'),
            ], 406);
        }

        User::where('email', $request->email)->first()->update(['remember_token' => $code, 'email_verified_at' => $expire]);

        return response()->json([
            'message' => 'An otp code has been sent to your email. Please check and confirm.',
        ]);
    }
}
