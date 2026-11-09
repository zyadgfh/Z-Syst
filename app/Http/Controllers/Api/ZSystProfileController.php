<?php

namespace App\Http\Controllers\Api;

use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ZSystProfileController extends Controller
{
    use HasUploader;

    public function index()
    {
        $user = User::with('business')->findOrFail(auth()->id());

        if ($user->id !== auth()->id()) {
            return response()->json(['message' => __('Forbidden.')], 403);
        }

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $user,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:250',
            'email' => ['required', 'email', Rule::unique('users')->ignore(auth()->id())],
            'image' => 'nullable|image|mimes:jpeg,png,gif|dimensions:max_width=2000,max_height=2000|max:1048',
        ]);

        $user = User::findOrFail(auth()->id());

        if ($user->id !== auth()->id()) {
            return response()->json(['message' => __('Forbidden.')], 403);
        }

        $user->update($request->except('image') + [
            'image' => $request->image ? $this->upload($request, 'image', $user->image) : $user->image,
        ]);

        $user = User::findOrFail(auth()->id());

        AuditLogger::log('profile.updated', 'User profile updated.', [
            'user_id' => $user->id,
        ]);

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'image' => $user->image,
        ];

        return response()->json([
            'message' => __('Profile updated successfully.'),
            'data' => $data,
        ]);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|string|min:10',
            'password_confirmation' => 'required',
        ]);

        $user = auth()->user();
        $service = app(\App\Services\UserManagementService::class);

        // PATH B: production verifies through GoTrue. In Supabase fallback
        // mode (dev / CI) the bcrypt hash lives on app_users — verify and
        // update it locally, no GoTrue round-trip.
        if (config('supabase.fallback_eloquent')) {
            $hash = \App\Models\AppUser::query()->whereKey($user->getKey())->value('password');

            if ($hash === null || ! \Illuminate\Support\Facades\Hash::check((string) $request->current_password, (string) $hash)) {
                return response()->json([
                    'error' => __('Current password does not match with old password.'),
                ], 422);
            }

            \App\Models\AppUser::whereKey($user->getKey())->update([
                'password' => \Illuminate\Support\Facades\Hash::make((string) $request->password),
            ]);
        } else {
            $email = (string) $user->email;
            echo 'PW-B: email='.($email === '' ? 'EMPTY' : $email).', fallback='.var_export(config('supabase.fallback_eloquent'), true)."\n"; // TEMP-DIAG

            if ($email === '' || ! $service->verifyPassword($email, (string) $request->current_password)) {
                return response()->json([
                    'error' => __('Current password does not match with old password.'),
                ], 422);
            }

            if (! $service->changePassword($user, (string) $request->password)) {
                return response()->json([
                    'error' => __('Password change is not available right now. Please contact support.'),
                ], 503);
            }
        }

        AuditLogger::log('password.changed', 'User password changed.', [
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => __('Password changed successfully.'),
        ]);
    }
}
