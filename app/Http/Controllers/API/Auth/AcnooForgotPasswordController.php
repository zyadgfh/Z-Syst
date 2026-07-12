<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\VerifyResetCodeRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\PasswordReset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

class AcnooForgotPasswordController extends Controller
{
    public function sendResetCode(ForgotPasswordRequest $request) : JsonResponse
    {

        $expire = now()->addHour();
        $code = random_int(100000,999999);
        $user = User::where('email',$request->email)->first();
        $user->update(['remember_token' => $code, 'email_verified_at' => $expire]);

        $data = [
            'code' => $code
        ];

        try {
            if (env('QUEUE_MAIL')) {
                Mail::to($request->email)->queue(new PasswordReset($data));
            } else {
                Mail::to($request->email)->send(new PasswordReset($data));
            }
            return response()->json([
                'message' => 'Password reset code has been sent to your email.',
            ]);

        } catch (\Exception $exception){
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function verifyResetCode(VerifyResetCodeRequest $request)
    {

        $user = User::where('email', $request->email)->first();

        if ($user->remember_token == $request->code) {
            if ($user->email_verified_at > now()) {
                return response()->json([
                    'message' => __('The code has been verified.')
                ]);
            } else {
                return response()->json([
                    'error' => __('The verification code has expired.')
                ], 400);
            }
        } else {
            return response()->json([
                'error' => __('Invalid Code.')
            ], 404);
        }
    }

    public function resetPassword(ResetPasswordRequest $request) : JsonResponse
    {

        $user = User::where('email', $request->email)->first();

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'message' => 'Your password has been changed!',
        ]);
    }
}
