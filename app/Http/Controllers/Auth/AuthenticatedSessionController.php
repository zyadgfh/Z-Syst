<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\BusinessCategory;
use App\Models\Option;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        $login_page = Option::where('key', 'login-page')->first();
        $business_categories = BusinessCategory::latest()->get();

        return view('auth.login', compact('login_page', 'business_categories'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        $remember = $request->filled('remember') ? 1 : 0;
        $redirect_url = url('/');
        $user = auth()->user();

        if ($user->role == 'shop-owner' || $user->role == 'staff') {
            Auth::logout();

            return response()->json([
                'redirect' => route('login'),
                'message' => __('You can not login as an business account right now.'),
            ]);
        } else {
            $role = Role::where('name', $user->role)->first();
            $first_role = $role->permissions->pluck('name')->all()[0];
            $page = explode('-', $first_role);
            $redirect_url = route('admin.'.$page[0].'.index');
        }

        return response()->json([
            'message' => __('Logged In Successfully'),
            'remember' => $remember,
            'redirect' => $redirect_url,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
