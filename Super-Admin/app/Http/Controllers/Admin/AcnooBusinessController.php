<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\User;
use App\Models\Gateway;
use App\Models\Business;
use App\Models\PaymentType;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use App\Models\BusinessCategory;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Modules\AffiliateAddon\App\Models\Affiliate;
use Modules\AffiliateAddon\App\Models\AffiliateTransaction;
use Illuminate\Validation\Rule;

class AcnooBusinessController extends Controller
{

    use HasUploader;

    public function __construct()
    {
        $this->middleware('permission:business-create')->only('create', 'store');
        $this->middleware('permission:business-read')->only('index');
        $this->middleware('permission:business-update')->only('edit', 'update', 'status');
        $this->middleware('permission:business-delete')->only('destroy', 'deleteAll');
    }

    public function index(Request $request)
    {
        $plans = Plan::latest()->get();
        $gateways = Gateway::latest()->get();

        $search = $request->input('search');

        $businesses = Business::with(['enrolled_plan:id,plan_id', 'enrolled_plan.plan:id,subscriptionName', 'category:id,name'])
            ->when($request->search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('companyName', 'like', '%' . $search . '%')
                        ->orWhere('phoneNumber', 'like', '%' . $search . '%')
                        ->orWhereHas('category', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('user', function ($q) use ($search) {
                            $q->where('email', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('enrolled_plan.plan', function ($q) use ($search) {
                            $q->where('subscriptionName', 'like', '%' . $search . '%');
                        });
            });
        })->latest()->paginate($request->per_page ?? 20)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.business.datas', compact('businesses'))->render()
            ]);
        }

        return view('admin.business.index', compact('businesses', 'gateways', 'plans'));
    }


    public function create()
    {
        $plans = Plan::where('status', 1)->latest()->get();
        $categories = BusinessCategory::latest()->get();
        return view('admin.business.create', compact('plans', 'categories'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'address' => 'nullable|max:250',
            'companyName' => 'required|max:250',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'pictureUrl' => 'nullable|image',
            'phoneNumber' => 'nullable',
            'shopOpeningBalance' => 'nullable|numeric',
            'business_category_id' => 'required|exists:business_categories,id',
            'plan_subscribe_id' => 'nullable|exists:plans,id',
        ]);

        DB::beginTransaction();
        try {

            $user = auth()->user();

            $business = Business::create([
                'companyName' => $request->companyName,
                'address' => $request->address,
                'email' =>  $request->email,
                'phoneNumber' => $request->phoneNumber,
                'shopOpeningBalance' => $request->shopOpeningBalance,
                'business_category_id' => $request->business_category_id,
                'pictureUrl' => $request->pictureUrl ? $this->upload($request, 'pictureUrl') : NULL,
                'user_id' => $user->id,
            ]);

            PaymentType::create([
                'name' => "Cash",
                'business_id' => $business->id
            ]);

            User::create([
                'business_id' => $business->id,
                'name' => $request->companyName,
                'email' =>  $request->email,
                'phone' => $request->phoneNumber,
                'image' => $request->pictureUrl ? $this->upload($request, 'pictureUrl') : NULL,
                'password' => Hash::make($request->password),
                'user_id' => $user->id,
            ]);

            if ($request->plan_subscribe_id) {
                $plan = Plan::findOrFail($request->plan_subscribe_id);

                $subscribe = PlanSubscribe::create([
                    'plan_id' => $plan->id,
                    'notes' => $request->notes,
                    'duration' => $plan->duration,
                    'business_id' => $business->id,
                    'price' => $plan->subscriptionPrice,
                    'gateway_id' => $request->gateway_id,
                    'allow_multibranch' => $plan->allow_multibranch,
                    'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                    'subdomain_limit' => $plan->subdomain_limit ?? 0,
                ]);

                $business->update([
                    'subscriptionDate' => now(),
                    'plan_subscribe_id' => $subscribe->id,
                    'will_expire' => now()->addDays($plan->duration),
                ]);

                sendNotification($subscribe->id, route('admin.subscription-reports.index', ['id' => $subscribe->id]), __('Plan subscribed by ' . $user->name));
            }

            DB::commit();

            return response()->json([
                'message' => __('Business created successfully.'),
                'redirect' => route('admin.business.index'),
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(__('Something went wrong.'), 403);
        }
    }

    public function edit(string $id)
    {
        $plans = Plan::latest()->get();
        $business = Business::findOrFail($id);
        $categories = BusinessCategory::latest()->get();
        $user = User::where('business_id', $business->id)->firstOrFail();
        return view('admin.business.edit', compact('business', 'plans', 'categories', 'user'));
    }

    public function update(Request $request, string $id)
    {

        $business = Business::findOrFail($id);
        $user = User::where('business_id', $business->id)->firstOrFail();

        $request->validate([
            'address' => 'nullable|max:250',
            'companyName' => 'required|max:250',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:4|confirmed',
            'pictureUrl' => 'nullable|image',
            'phoneNumber' => 'nullable',
            'shopOpeningBalance' => 'nullable|numeric',
            'business_category_id' => 'required|exists:business_categories,id',
            'plan_subscribe_id' => 'nullable|exists:plans,id',
        ]);

        DB::beginTransaction();

        try {

            $business->update([
                'companyName' => $request->companyName,
                'address' => $request->address,
                'email' =>  $request->email,
                'phoneNumber' => $request->phoneNumber,
                'shopOpeningBalance' => $request->shopOpeningBalance,
                'business_category_id' => $request->business_category_id,
                'pictureUrl' => $request->pictureUrl ? $this->upload($request, 'pictureUrl', $business->pictureUrl) : $business->pictureUrl,
            ]);

            $user->update([
                'name' => $request->companyName,
                'email' => $request->email,
                'phone' => $request->phoneNumber,
                'image' => $request->pictureUrl ? $this->upload($request, 'pictureUrl') : $user->image,
                'password' => $request->password ? Hash::make($request->password) : $user->password,
            ]);

            $prev_subscription = PlanSubscribe::where('business_id', $business->id)->latest()->first();

            if ($request->plan_subscribe_id && ($prev_subscription->plan_id != $request->plan_subscribe_id)) {
                $plan = Plan::findOrFail($request->plan_subscribe_id);
                $subscribe = PlanSubscribe::create([
                    'plan_id' => $plan->id,
                    'notes' => $request->notes,
                    'duration' => $plan->duration,
                    'business_id' => $business->id,
                    'price' => $plan->subscriptionPrice,
                    'gateway_id' => $request->gateway_id,
                    'allow_multibranch' => $plan->allow_multibranch,
                    'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                    'subdomain_limit' => $plan->subdomain_limit ?? 0,
                ]);

                $business->update([
                    'subscriptionDate' => now(),
                    'plan_subscribe_id' => $subscribe->id,
                    'will_expire' => now()->addDays($plan->duration),
                ]);

                sendNotification($subscribe->id, route('admin.subscription-reports.index', ['id' => $subscribe->id]), __('Plan subscribed by ' . auth()->user()->name));
            }

            DB::commit();

            return response()->json([
                'message' => __('Business updated successfully.'),
                'redirect' => route('admin.business.index'),
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(__('Something went wrong.'), 403);
        }
    }

    public function destroy($id)
    {
        $business = Business::findOrFail($id);
        $business->delete();
        return response()->json([
            'message'   => __('Business deleted successfully'),
            'redirect'  => route('admin.business.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Business::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message'   => __('Selected Business deleted successfully'),
            'redirect'  => route('admin.business.index')
        ]);
    }

    // Upgrade plan code

    public function upgradePlan(Request $request)
    {
        $request->validate([
            'price' => 'required|string',
            'notes' => 'required|string',
            'plan_id' => 'required|exists:plans,id',
            'business_id' => 'required|exists:businesses,id',
        ]);

        DB::beginTransaction();
        try {

            $plan = Plan::findOrFail($request->plan_id);
            $business = Business::findOrFail($request->business_id);

            $subscribe = PlanSubscribe::create([
                'plan_id' => $plan->id,
                'payment_status' => 'paid',
                'notes' => $request->notes,
                'duration' => $plan->duration,
                'business_id' => $business->id,
                'price' => $plan->subscriptionPrice,
                'gateway_id' => $request->gateway_id,
                'allow_multibranch' => $plan->allow_multibranch,
                'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                'subdomain_limit' => $plan->subdomain_limit ?? 0
            ]);

            Cache::forget('plan-data-' . $business->id);

            $business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($plan->duration),
            ]);

            if (moduleCheck('AffiliateAddon') && $business->affiliator_id) {
                $affiliateUser = User::find($business->affiliator_id);

                if ($affiliateUser && $plan->affiliate_commission > 0) {
                    $commission = ($plan->subscriptionPrice * $plan->affiliate_commission) / 100;

                    Affiliate::where('user_id', $affiliateUser->id)->increment('balance', $commission);

                    AffiliateTransaction::create([
                        'user_id'        => $affiliateUser->id,
                        'business_id'    => $business->id,
                        'trx'            => strtoupper(str()->random(10)),
                        'type'           => 'credit',
                        'amount'         => $commission,
                        'title'          => 'Commission from Order #' . $subscribe->id,
                        'reference_id'   => $subscribe->id,
                        'reference_type' => 'PlanSubscribe',
                        'note'           => 'User purchased via your referral link',
                    ]);
                }
            }

            sendNotification($subscribe->id, route('admin.subscription-reports.index', ['id' => $subscribe->id]), __('Plan subscribed by ' . auth()->user()->name));

            DB::commit();
            return response()->json([
                'message' => __('Subscription enrolled successfully.'),
                'redirect' => route('admin.subscription-reports.index'),
            ]);
        } catch (\Throwable $th) {
            DB::rollback();
            return response()->json(__('Something went wrong.'), 403);
        }
    }

    public function status(Request $request, $id)
    {
        $business = Business::findOrFail($id);
        $business->update(['status' => $request->status]);
        return response()->json(['message' => 'Business']);
    }
}
