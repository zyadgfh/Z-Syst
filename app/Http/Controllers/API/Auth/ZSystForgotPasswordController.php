<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordReset;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ZSystForgotPasswordController extends Controller
{
    public function sendResetCode(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Password reset by email is disabled.'], 503);
    }

    public function verifyResetCode(Request $request)
    {
        return response()->json(['message' => 'Password reset by email is disabled.'], 503);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Password reset by email is disabled.'], 503);
    }
}
