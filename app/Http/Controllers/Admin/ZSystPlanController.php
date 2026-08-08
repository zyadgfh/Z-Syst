<?php

namespace App\Http\Controllers\Admin;

use App\Exports\PlanExport;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class ZSystPlanController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->middleware('permission:plans-create')->only('create', 'store');
        $this->middleware('permission:plans-read')->only('index', 'statistics', 'popularPlans');
        $this->middleware('permission:plans-update')->only('edit', 'update', 'status');
        $this->middleware('permission:plans-delete')->only('destroy', 'deleteAll');
    }

    public function index()
    {
        $plans = Plan::latest()->paginate(10);

        return view('admin.plans.index', compact('plans'));
    }

    public function zsystFilter(Request $request)
    {
        $plans = Plan::when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->orWhere('subscriptionName', 'like', '%'.request('search').'%')
                    ->orWhere('duration', 'like', '%'.request('search').'%')
                    ->orWhere('subscriptionPrice', 'like', '%'.request('search').'%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.plans.datas', compact('plans'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'author')->get();

        return view('admin.plans.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subscriptionName' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'offerPrice' => 'nullable|numeric|min:0|max:9999999999999',
            'subscriptionPrice' => 'required|numeric|min:0|max:9999999999999',
            'features' => 'nullable|array',
            'status' => 'boolean',
        ]);

        $plan = Plan::create($request->except(['offerPrice', 'status']) + [
            'offerPrice' => $request->offerPrice ?? null,
            'status' => $request->status ? 1 : 0,
            'features' => $request->features ?? [],
        ]);

        return response()->json([
            'message' => __('Subscription Plan created successfully'),
            'redirect' => route('admin.plans.index'),
        ]);
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan)
    {
        $request->validate([
            'subscriptionName' => 'required|string|max:255',
            'duration' => 'required|string',
            'offerPrice' => 'nullable|numeric|min:0|max:9999999999999',
            'subscriptionPrice' => 'required|numeric|min:0|max:9999999999999',
        ]);

        $plan->update($request->except(['offerPrice', 'status']) + [
            'offerPrice' => $request->offerPrice ?? null,
            'status' => $request->status ? 1 : 0,
        ]);

        return response()->json([
            'message' => __('Subscription Plan updated successfully'),
            'redirect' => route('admin.plans.index'),
        ]);
    }

    public function status(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);
        $plan->update(['status' => $request->status]);

        return response()->json(['message' => 'Plan']);
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();

        return response()->json([
            'message' => __('Subscription Plan deleted successfully'),
            'redirect' => route('admin.plans.index'),
        ]);
    }

    public function deleteAll(Request $request)
    {
        Plan::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => __('Subscription plan deleted successfully'),
            'redirect' => route('admin.plans.index'),
        ]);
    }

    public function exportExcel()
    {
        return Excel::download(new PlanExport, 'plans.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new PlanExport, 'plans.csv');
    }

    /**
     * Get subscription statistics
     */
    public function statistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'plan_id' => $request->plan_id,
        ];

        $statistics = $this->subscriptionService->getSubscriptionStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get popular plans
     */
    public function popularPlans(Request $request)
    {
        $limit = $request->limit ?? 5;
        $popularPlans = $this->subscriptionService->getPopularPlans($limit);

        return response()->json($popularPlans);
    }

    /**
     * Get plan usage statistics
     */
    public function planUsage(Request $request, Plan $plan)
    {
        $statistics = $this->subscriptionService->getPlanUsageStatistics($plan->id);

        return response()->json($statistics);
    }

    /**
     * Calculate proration for plan upgrade
     */
    public function calculateProration(Request $request)
    {
        $request->validate([
            'current_plan_id' => 'required|exists:plans,id',
            'new_plan_id' => 'required|exists:plans,id',
        ]);

        $proration = $this->subscriptionService->calculateProration(
            $request->current_plan_id,
            $request->new_plan_id
        );

        return response()->json($proration);
    }
}
