<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SupabaseAuthController extends Controller
{
    protected SupabaseAuthService $supabaseAuth;

    public function __construct(SupabaseAuthService $supabaseAuth)
    {
        $this->supabaseAuth = $supabaseAuth;
    }

    /**
     * Register new user with Supabase and Laravel.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'business_id' => 'nullable|exists:businesses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Register with Supabase
            $supabaseResponse = $this->supabaseAuth->register(
                $request->email,
                $request->password,
                [
                    'name' => $request->name,
                    'phone' => $request->phone,
                ]
            );

            if (!$supabaseResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Supabase registration failed',
                    'error' => $supabaseResponse['error'],
                ], 400);
            }

            // Create Laravel user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'business_id' => $request->business_id,
                'supabase_id' => $supabaseResponse['user']['id'] ?? null,
                'supabase_access_token' => $supabaseResponse['access_token'] ?? null,
                'status' => 'active',
            ]);

            // Create token for Laravel Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'business_id' => $user->business_id,
                ],
                'token' => $token,
                'supabase_session' => $supabaseResponse['session'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user with Supabase and Laravel.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Login with Supabase
            $supabaseResponse = $this->supabaseAuth->login(
                $request->email,
                $request->password
            );

            if (!$supabaseResponse['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'error' => $supabaseResponse['error'],
                ], 401);
            }

            // Find or create Laravel user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found in Laravel',
                ], 404);
            }

            // Update Supabase tokens
            $user->update([
                'supabase_access_token' => $supabaseResponse['access_token'],
                'supabase_refresh_token' => $supabaseResponse['session']['refresh_token'] ?? null,
                'supabase_token_expires_at' => now()->addSeconds($supabaseResponse['session']['expires_in'] ?? 3600),
            ]);

            // Create token for Laravel Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'business_id' => $user->business_id,
                    'role' => $user->role,
                ],
                'token' => $token,
                'supabase_session' => $supabaseResponse['session'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user from both Supabase and Laravel.
     */
    public function logout(Request $request)
    {
        try {
            $user = Auth::user();

            // Logout from Supabase
            if ($user->supabase_access_token) {
                $this->supabaseAuth->logout($user->supabase_access_token);
            }

            // Revoke Laravel tokens
            $user->tokens()->delete();

            // Clear Supabase tokens
            $user->update([
                'supabase_access_token' => null,
                'supabase_refresh_token' => null,
                'supabase_token_expires_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh Supabase session.
     */
    public function refresh(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user->supabase_refresh_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No refresh token available',
                ], 400);
            }

            $response = $this->supabaseAuth->refreshSession($user->supabase_refresh_token);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session refresh failed',
                    'error' => $response['error'],
                ], 400);
            }

            // Update tokens
            $user->update([
                'supabase_access_token' => $response['access_token'],
                'supabase_refresh_token' => $response['refresh_token'],
                'supabase_token_expires_at' => now()->addSeconds(3600),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Session refreshed successfully',
                'supabase_session' => $response,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Session refresh failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user from Supabase.
     */
    public function me(Request $request)
    {
        try {
            $user = Auth::user();

            $supabaseUser = null;
            if ($user->supabase_access_token) {
                $response = $this->supabaseAuth->getCurrentUser($user->supabase_access_token);
                if ($response['success']) {
                    $supabaseUser = $response['user'];
                }
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'business_id' => $user->business_id,
                    'role' => $user->role,
                    'supabase_id' => $user->supabase_id,
                ],
                'supabase_user' => $supabaseUser,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send password reset email via Supabase.
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $response = $this->supabaseAuth->sendPasswordReset($request->email);

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send password reset email',
                    'error' => $response['error'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset email sent successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send password reset email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password via Supabase.
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = Auth::user();

            if (!$user->supabase_access_token) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Supabase session available',
                ], 400);
            }

            $response = $this->supabaseAuth->updatePassword(
                $user->supabase_access_token,
                $request->password
            );

            if (!$response['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update password',
                    'error' => $response['error'],
                ], 400);
            }

            // Update Laravel password
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}