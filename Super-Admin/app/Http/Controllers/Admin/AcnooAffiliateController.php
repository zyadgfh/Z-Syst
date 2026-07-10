<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Models\Gateway;
use App\Models\Business;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\AffiliateAddon\App\Models\Affiliate;

class AcnooAffiliateController extends Controller
{
    public function index()
    {
        $plans = Plan::latest()->get();
        $gateways = Gateway::latest()->get();
        $affiliates = Affiliate::with(['user:id,business_id,name,email','user.business:id,plan_subscribe_id,will_expire,companyName,phoneNumber,address,subscriptionDate,created_at,business_category_id:id,name','user.business.enrolled_plan:id,plan_id,business_id,plan_id','user.business.enrolled_plan.plan:id,subscriptionName'])->latest()->paginate(20);
        return view('admin.affiliate-modules.affiliate.index', compact('affiliates','plans','gateways'));
    }

    public function acnooFilter(Request $request)
    {
        $search = $request->input('search');

        $affiliates = Affiliate::with(['user:id,business_id,name,email','user.business:id,plan_subscribe_id,will_expire,companyName,phoneNumber,address,subscriptionDate,created_at,business_category_id:id,name','user.business.enrolled_plan:id,plan_id,business_id,plan_id','user.business.enrolled_plan.plan:id,subscriptionName'])->when($search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('balance', 'like', '%' . $search . '%')
                   ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                          ->orwhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user.business.enrolled_plan.plan', function ($q) use ($search) {
                        $q->where('subscriptionName', 'like', '%' . $search . '%');
                    });
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 20);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.affiliate-modules.affiliate.datas', compact('affiliates'))->render()
            ]);
        }

        return redirect(url()->previous());
    }

    public function destroy($id)
    {
        Affiliate::findOrFail($id)->delete();
        return response()->json([
            'message'   => __('Affiliate deleted successfully'),
            'redirect'  => route('admin.affiliates.index')
        ]);
    }

    public function deleteAll(Request $request)
    {
        Affiliate::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message'   => __('Selected Affiliate deleted successfully'),
            'redirect'  => route('admin.affiliates.index')
        ]);
    }


}
