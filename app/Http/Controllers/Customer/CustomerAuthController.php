<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerAuthController extends Controller
{
    /**
     * Show registration form
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('customer.dashboard');
        }
        return view('customer.auth.register');
    }

    /**
     * Handle customer registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'status' => 1,
        ]);

        Auth::login($user);

        // Merge guest cart items
        $this->mergeGuestCart($user->id);

        // Merge guest wishlist items
        Wishlist::mergeSessionToUser($user->id);

        return redirect()->route('customer.dashboard')
            ->with('success', __('Account created successfully!'));
    }

    /**
     * Show login form
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('customer.dashboard');
        }
        return view('customer.auth.login');
    }

    /**
     * Handle customer login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'email' => __('The provided credentials do not match our records.'),
            ])->onlyInput('email');
        }

        // Only allow customers to login via this form
        if (!in_array($user->role, ['customer', null])) {
            return back()->withErrors([
                'email' => __('This account is not a customer account. Please use the staff login.'),
            ])->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));

        // Merge guest cart items
        $this->mergeGuestCart($user->id);

        // Merge guest wishlist items
        Wishlist::mergeSessionToUser($user->id);

        $request->session()->regenerate();

        return redirect()->intended(route('customer.dashboard'))
            ->with('success', __('Welcome back!'));
    }

    /**
     * Logout customer
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Merge guest cart into user cart
     */
    protected function mergeGuestCart(int $userId): void
    {
        $sessionId = session()->getId();
        $guestItems = \App\Models\CartItem::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();

        foreach ($guestItems as $guestItem) {
            $existingItem = \App\Models\CartItem::where('user_id', $userId)
                ->where('product_id', $guestItem->product_id)
                ->first();

            if ($existingItem) {
                $existingItem->increment('quantity', $guestItem->quantity);
                $guestItem->delete();
            } else {
                $guestItem->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);
            }
        }
    }
}
