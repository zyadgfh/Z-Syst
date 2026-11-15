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
        return response()->json(['message' => 'Email-based registration is disabled.'], 503);
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

        if ($user->remember_token == $request->otp) {
            if ($user->email_verified_at > now()) {

                Auth::login($user);
                $is_setup = $user->business_id ? true : false;
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

            } else {
                return response()->json([
                    'error' => __('The verification otp has been expired.'),
                ], 400);
            }
        } else {
            return response()->json([
                'error' => __('Invalid otp.'),
            ], 404);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'email' => 'required|email',
        ]);

        if (auth()->attempt($request->only('email', 'password'))) {
            $user = auth()->user();

            if ($user->role != 'staff' && $user->role != 'shop-owner') {
                return response()->json([
                    'message' => 'You can not login as '.$user->role.' from the app!',
                ], 406);
            }

            if ($user->remember_token && ! $user->business_id) { // If user didn't verify email

                return response()->json(['message' => 'Email verification is disabled; contact an administrator.'], 503);
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
            return response()->json([
                'message' => 'Invalid email or password!',
            ], 404);
        }
    }

    protected function setAccessTokenExpiration(NewAccessToken $accessToken)
    {
        $expiration = now()->addMinutes(Config::get('sanctum.expiration'));

        DB::table('personal_access_tokens')
            ->where('id', $accessToken->accessToken->id)
            ->update(['expires_at' => $expiration]);
    }

    public function signOut(): JsonResponse
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

    public function resendOtp(Request $request)
    {
        return response()->json(['message' => 'Email verification is disabled.'], 503);
    }
}
