<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Business;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use App\Models\BusinessCategory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Session;
use Modules\AffiliateAddon\App\Models\Affiliate;

class AcnooBusinessController extends Controller
{
    public function index()
    {
        $categories = BusinessCategory::select('id', 'name')->whereStatus(1)->get();
        return response()->json($categories);
    }

    public function getBusinessCategories()
    {
        $categories = BusinessCategory::select('id', 'name')->whereStatus(1)->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validate_data = $request->validate([
            'companyName' => 'required|max:250',
            'business_category_id' => 'required|exists:business_categories,id',
            'plan_id' => 'required|exists:plans,id',
            'address' => 'nullable|max:250',
            'password' => 'required|min:6|max:100',
            'email' => 'required|email|unique:users,email',
        ]);

        if (moduleCheck('AffiliateAddon')) {
            $refCode = Cookie::get('ref_code');
            if ($refCode) {
                $affiliator = Affiliate::where('ref_code', $refCode)->first();
                if ($affiliator) {
                    $validate_data['affiliator_id'] = $affiliator->user_id;
                }
            }
        }

        $business = Business::create($validate_data);

        PaymentType::create([
            'name' => "Cash",
            'business_id' => $business->id
        ]);

        Session::put('plan_id', $request->plan_id);

        return response()->json([
            'message' => 'Business created successfully.',
            'redirect' => route('payments-gateways.index', ['plan_id' => $request->plan_id, 'business_id' => $business->id]),
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|min:4|max:15',
        ]);

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
}
