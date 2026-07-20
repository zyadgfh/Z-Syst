<?php

namespace App\Modules\Auth\Infrastructure\Controllers;

use App\Core\Exceptions\AuthenticationException;
use App\Core\Exceptions\ValidationException;
use App\Models\User;
use Illuminate\Auth\AuthenticationException as LaravelAuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException as LaravelValidationException;

class AuthController
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'phone' => 'nullable|string|max:20',
                'company_id' => 'nullable|exists:companies,id',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
                'company_id' => $validated['company_id'] ?? auth()?->user()?->company_id,
                'status' => 'active',
            ]);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ], 201);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($validated)) {
                throw new AuthenticationException('Invalid credentials');
            }

            $user = Auth::user();

            return response()->json([
                'message' => 'Login successful',
                'user' => $user,
                'token' => $user->createToken('auth_token')->plainTextToken,
            ]);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Get authenticated user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
                'lang' => 'sometimes|string|in:ar,en',
            ]);

            $request->user()->update($validated);

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $request->user(),
            ]);
        } catch (LaravelValidationException $e) {
            throw new ValidationException($e->errors(), 422);
        }
    }
}
