<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Nwidart\Modules\Facades\Module;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Branch;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
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

            $module = Module::find('Business');

            if ($module) {
                if ($module->isEnabled()) {

                    $business = $user->business;
                    $branch = Branch::find($user->branch_id);

                    if ($business && !$business->status) {
                        Auth::logout();
                        return response()->json([
                            'message' => 'Your business is inactive. Please contact your administrator.',
                            'redirect' => route('login')
                        ], 406);
                    }

                    if (multibranch_active() && branch_count()) {
                        if ($branch && !$branch->status && $user->branch_id && !$branch->is_main) {

                            Auth::logout();
                            return response()->json([
                                'message' => 'This branch is not active, Please contact with manager.',
                                'redirect' => route('login'),
                            ], 406);
                        }
                    } elseif (!multibranch_active()) {
                        if ($user->active_branch_id) {
                            $user->update([
                                'active_branch_id' => NULL
                            ]);
                        } elseif ($user->branch_id && !$branch->is_main) {

                            Auth::logout();
                            return response()->json([
                                'message' => 'Multibranch is not allowed in your current package, please upgrade your subscription plan.',
                                'redirect' => route('login'),
                            ], 406);
                        }
                    } elseif (!$branch && $user->branch_id) {
                        Auth::logout();
                        return response()->json([
                            'message' => 'Your current branch has been deleted, Please contact with manager.',
                            'redirect' => route('login'),
                        ], 406);
                    }

                    $redirect_url = route('business.dashboard.index');
                } else {
                    Auth::logout();
                    return response()->json([
                        'message' => 'Web addon is not active.',
                        'redirect' => route('login'),
                    ], 406);
                }
            } else {
                Auth::logout();
                return response()->json([
                    'message' => 'Web addon is not installed.',
                    'redirect' => route('login'),
                ], 406);
            }
        } else if ($user->role == 'affiliator') {

            $module = Module::find('AffiliateAddon');

            if ($module) {
                if ($module->isEnabled()) {

                    $redirect_url = route('business.dashboard.index');
                } else {
                    Auth::logout();
                    return response()->json([
                        'message' => 'Affiliate addon is not active.',
                        'redirect' => route('login'),
                    ], 406);
                }
            } else {
                Auth::logout();
                return response()->json([
                    'message' => 'affiliate addon is not installed.',
                    'redirect' => route('login'),
                ], 406);
            }
        } else {
            $redirect_url = route('admin.dashboard.index');
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
