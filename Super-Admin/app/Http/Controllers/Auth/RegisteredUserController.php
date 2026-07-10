<?php

namespace App\Http\Controllers\Auth;

use App\Models\Plan;
use App\Models\User;
use App\Models\Business;
use App\Models\Currency;
use App\Mail\WelcomeMail;
use App\Models\UserCurrency;
use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use App\Mail\RegistrationMail;
use App\Models\BusinessCategory;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cookie;
use Modules\AffiliateAddon\App\Models\Affiliate;

class RegisteredUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'password' => 'required|max:25|min:4',
            'plan_id' => 'required|exists:plans,id',
        ]);

        DB::beginTransaction();
        try {

            $user = User::where('email', $request->email)->first();

            if (($user ?? false) && $user->is_verified) {
                return response()->json([
                    'message' => 'This email is already exists.',
                ], 406);
            }

            if (!$user) {
                $user = User::create([
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                ]);
            }


            $otpSettings = get_option('email-varification');
            $verify_email = ($otpSettings['otp_status'] ?? 'off') === 'on';

            session()->put('user_id', $user->id);
            session()->put('plan_id', $request->plan_id);

            if ($verify_email) {
                // Generate OTP
                $code = random_int(100000, 999999);
                $visibility_time = $this->getOtpTimeInSeconds();
                $expire = now()->addSeconds($visibility_time);

                $user->update([
                    'remember_token' => $code,
                    'email_verified_at' => $expire,
                ]);

                // Send welcome mail
                if (env('MAIL_USERNAME')) {
                    if (env('QUEUE_MAIL')) {
                        Mail::to($request->email)->queue(new RegistrationMail($code));
                    } else {
                        Mail::to($request->email)->send(new RegistrationMail($code));
                    }
                } else {
                    return response()->json([
                        'message' => 'Mail service is not configured. Please contact your administrator.',
                    ], 406);
                }
            } else {
                $business_categories = BusinessCategory::where('status', 1)->latest()->get();
            }

            DB::commit();

            return response()->json([
                'message' => $verify_email ? 'An otp code has been sent to your email. Please check and confirm.' : 'Sign Up completed. Please setup your profile.',
                'openModal' => true,
                'email' => $request->email,
                'business_categories' => $business_categories ?? [],
                'otp_expiration' => $verify_email ? now()->diffInSeconds($expire) : false,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong. Please contact the admin.',
            ], 403);
        }
    }

    public function otpResend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $code = random_int(100000, 999999);
        $visibility_time = $this->getOtpTimeInSeconds();
        $expire = now()->addSeconds($visibility_time);

        $data = [
            'code' => $code,
            'name' => $request->name,
        ];

        if (env('MAIL_USERNAME')) {
            if (env('QUEUE_MAIL')) {
                Mail::to($request->email)->queue(new WelcomeMail($data));
            } else {
                Mail::to($request->email)->send(new WelcomeMail($data));
            }
        } else {
            return response()->json([
                'message' => __('Mail service is not configured. Please contact your administrator.'),
            ], 406);
        }

        User::where('email', $request->email)->first()->update(['remember_token' => $code, 'email_verified_at' => $expire]);

        return response()->json([
            'message' => 'An otp code has been sent to your email. Please check and confirm.',
            'otp_expiration' => now()->diffInSeconds($expire),
        ]);
    }

    public function otpSubmit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|min:4|max:15',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => __('User not found.')], 400);
        }

        if ($user->remember_token == $request->otp) {
            if ($user->email_verified_at > now()) {

                $business_categories = BusinessCategory::where('status', 1)->latest()->get();

                $user->update([
                    'is_verified' => 1,
                    'remember_token' => NULL,
                    'email_verified_at' => now(),
                ]);

                return response()->json([
                    'message' => 'The otp has been verified successfully!',
                    'business_categories' => $business_categories
                ]);
            } else {
                return response()->json(['message' => __('The verification otp has been expired.')], 400);
            }
        } else {
            return response()->json(['message' => __('Invalid otp.')], 400);
        }
    }

    public function businessSetup(Request $request)
    {
        $request->validate([
            'address' => 'nullable|max:250',
            'companyName' => 'required|max:250',
            'shopOpeningBalance' => 'nullable|numeric',
            'business_category_id' => 'required|exists:business_categories,id',
            'phoneNumber' => 'required|max:20',
        ]);

        DB::beginTransaction();
        try {

            $plan = Plan::find(session('plan_id'));
            $user = User::find(session('user_id'));

            if (!$user) {
                return response()->json([
                    'message' => 'Something went wrong. Please try again.',
                    'redirect' => route('home'),
                ], 403);
            }

            if (moduleCheck('AffiliateAddon')) {
                $refId = null;
                $refCode = Cookie::get('ref_code');
                if ($refCode) {
                    $affiliator = Affiliate::where('ref_code', $refCode)->first();
                    if ($affiliator) {
                        $refId = $affiliator->user_id;
                    }
                }

                $data['affiliator_id'] = $refId;
            }

            $data = [
                'address' => $request->address,
                'companyName' => $request->companyName,
                'phoneNumber' => $request->phoneNumber,
                'shopOpeningBalance' => $request->shopOpeningBalance ?? 0,
                'business_category_id' => $request->business_category_id,
            ];

            $business = Business::create($data);

            $currency = Currency::where('is_default', 1)->first();
            UserCurrency::create([
                'name' => $currency->name,
                'code' => $currency->code,
                'rate' => $currency->rate,
                'business_id' => $business->id,
                'symbol' => $currency->symbol,
                'currency_id' => $currency->id,
                'position' => $currency->position,
                'country_name' => $currency->country_name,
            ]);

            $user->update([
                'business_id' => $business->id,
            ]);

            if (moduleCheck('Business')) {
                Auth::login($user);

                $message = 'Your business setup is completed.';
                $redirect_url = route('business.dashboard.index');
            } else {
                $success_modal = true;
                $message = 'Your business setup is completed. Please download the apk for manage your business.';
            }

            if ($plan) {

                $plan_price = $plan->offerPrice == 0 && $plan->offerPrice != null ? $plan->offerPrice : $plan->subscriptionPrice;

                if ($plan_price <= 0) {
                    $subscribe = PlanSubscribe::create([
                        'plan_id' => $plan->id,
                        'business_id' => $business->id,
                        'duration' => $plan->duration,
                        'allow_multibranch' => $plan->allow_multibranch
                    ]);

                    $business->update([
                        'plan_subscribe_id' => $subscribe->id,
                        'subscriptionDate' => $plan ? now() : null,
                        'will_expire' => $plan ? now()->addDays($plan->duration) : null,
                    ]);
                } else {
                    $message = 'Your business setup is completed. Now you are going to the payment page.';
                    $redirect_url = route('payments-gateways.index', ['plan_id' => $plan->id, 'business_id' => $business->id]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => $message,
                'redirect' => $redirect_url ?? false,
                'success_modal' => $success_modal ?? false,
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong. Please contact the admin.',
            ], 403);
        }
    }

    public function getOtpTimeInSeconds()
    {
        $otpSettings = get_option('email-varification');

        $time = $otpSettings['otp_expiration_time'] ?? null;
        $durationType = $otpSettings['otp_duration_type'] ?? 'minute';
        $defaultFromEnv = env('OTP_VISIBILITY_TIME', 3);

        // Use default if DB value is null
        if (empty($time)) {
            return $defaultFromEnv * 60;
        }

        // Convert minutes to seconds
        return $durationType == 'minute' ? $time * 60 : $time;
    }
}
