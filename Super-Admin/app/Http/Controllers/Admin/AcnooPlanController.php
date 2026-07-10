<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AcnooPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:plans-create')->only('create', 'store');
        $this->middleware('permission:plans-read')->only('index');
        $this->middleware('permission:plans-update')->only('edit', 'update', 'status');
        $this->middleware('permission:plans-delete')->only('destroy', 'deleteAll');
    }

    public function index(Request $request)
    {
        $search = $request->input('search');

        $plans = Plan::when($request->search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('subscriptionName', 'like', '%' . $search . '%')
                    ->orWhere('duration', 'like', '%' . $search . '%')
                    ->orWhere('subscriptionPrice', 'like', '%' . $search . '%');
            });
        })->latest()->paginate($request->per_page ?? 20)->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.plans.datas', compact('plans'))->render()
            ]);
        }

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'duration' => 'required|string',
            'offerPrice' => 'nullable|numeric|min:0|max:9999999999999',
            'subscriptionName' => 'required|string|max:255|unique:plans,subscriptionName',
            'subscriptionPrice' => 'required|numeric|min:0|max:9999999999999',
            'addon_domain_limit' => 'nullable|integer|min:0',
            'subdomain_limit' => 'nullable|integer|min:0',
        ]);

        Plan::create($request->except(['offerPrice', 'status', 'allow_multibranch']) + [
            'offerPrice' => $request->offerPrice ?? NULL,
            'status' => $request->status ? 1 : 0,
            'allow_multibranch' => $request->allow_multibranch ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Subscription Plan created successfully'),
            'redirect' => route('admin.plans.index')
        ]);
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'duration' => 'required|string',
            'offerPrice' => 'nullable|numeric|min:0|max:9999999999999',
            'subscriptionPrice' => 'required|numeric|min:0|max:9999999999999',
            'subscriptionName' => 'required|string|max:255|unique:plans,subscriptionName,' . $plan->id,
            'addon_domain_limit' => 'nullable|integer|min:0',
            'subdomain_limit' => 'nullable|integer|min:0',
        ]);


        if ($plan->subscriptionName == 'Free' && ($plan->subscriptionName != $request->subscriptionName || $plan->subscriptionPrice != $request->subscriptionPrice || $plan->offerPrice != $request->offerPrice)) {
            return response()->json([
                'message' => __('You can not change the package name & price of free plan.'),
            ], 406);
        }

        DB::beginTransaction();
        try {
            $plan->update($request->except(['offerPrice', 'status', 'allow_multibranch']) + [
                    'offerPrice' => $request->offerPrice ?? NULL,
                    'status' => $request->status ? 1 : 0,
                    'allow_multibranch' => $request->allow_multibranch ? 1 : 0,
                ]);

            if ($request->allow_existing_subscriber){
                $updateData = [
                    'allow_multibranch' => $request->allow_multibranch ? 1 : 0,
                    'addon_domain_limit' => $request->addon_domain_limit ?? 0,
                    'subdomain_limit' => $request->subdomain_limit ?? 0,
                ];

                PlanSubscribe::where('plan_id', $plan->id)->update($updateData);
            }

            DB::commit();

            return response()->json([
                'message' => __('Subscription Plan updated successfully'),
                'redirect' => route('admin.plans.index')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }

    }

    public function status(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        if ($plan->subscriptionName == 'Free') {
            return response()->json([
                'message' => __('You can not change the status for free plan.'),
            ], 406);
        }
        $plan->update(['status' => $request->status]);
        return response()->json(['message' => 'Plan']);
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        if ($plan->subscriptionName == 'Free') {
            return response()->json([
                'message' => __('You can not delete free plan.'),
            ], 406);
        }

        $plan->delete();

        return response()->json([
            'message'   => __('Subscription Plan deleted successfully'),
            'redirect'  => route('admin.plans.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Plan::whereIn('id', $request->ids)->where('subscriptionName', '!=', 'Free')->delete();
        return response()->json([
            'message' => __('Subscription plan deleted successfully'),
            'redirect' => route('admin.plans.index')
        ]);
    }
}
