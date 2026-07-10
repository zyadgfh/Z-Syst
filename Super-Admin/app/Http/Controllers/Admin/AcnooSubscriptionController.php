<?php

namespace App\Http\Controllers\Admin;

use App\Models\PlanSubscribe;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Modules\AffiliateAddon\App\Models\Affiliate;
use Modules\AffiliateAddon\App\Models\AffiliateTransaction;


class AcnooSubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $subscribers = PlanSubscribe::with(['plan:id,subscriptionName', 'business:id,companyName,business_category_id,pictureUrl', 'business.category:id,name', 'gateway:id,name'])
            ->when($request->search, function ($q) use ($search) {
            $q->where(function ($q) use ($search) {
                $q->where('duration', 'like', '%' . $search . '%')
                    ->orWhereHas('plan', function ($q) use ($search) {
                            $q->where('subscriptionName', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('gateway', function ($q) use ($search) {
                            $q->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('business', function ($q) use ($search) {
                            $q->where('companyName', 'like', '%' . $search . '%')
                                ->orWhereHas('category', function ($q) use ($search) {
                                    $q->where('name', 'like', '%' . $search . '%');
                                });
                        });
            });
        })
        ->latest()
        ->paginate($request->per_page ?? 20)
        ->appends($request->query());

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.subscribe-order.datas', compact('subscribers'))->render()
            ]);
        }

        return view('admin.subscribe-order.index', compact('subscribers'));
    }

    public function reject(Request $request, string $id)
    {

        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        $reject = PlanSubscribe::findOrFail($id);

        if ($reject) {
            $reject->update([
                'payment_status' => 'reject',
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Status Unpaid',
                'redirect' => route('admin.subscription-orders.index'),
            ]);
        } else {
            return response()->json(['message' => 'request not found'], 404);
        }
    }

    public function paid(Request $request, string $id)
    {
        $request->validate([
            'notes' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $subscribe = PlanSubscribe::findOrFail($id);

            $existingNotes = $subscribe->notes ?? [];
            $updatedNotes = array_merge($existingNotes, ['reason' => $request->notes]);

            $subscribe->update($request->except('notes') + [
                'payment_status' => 'paid',
                'notes' => $updatedNotes,
            ]);

            $subscribe->business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($subscribe->plan->duration),
            ]);

            $business = $subscribe->business;
            $plan = $subscribe->plan;
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

            DB::commit();

            Cache::forget('plan-data-'. $subscribe->business_id);

            return response()->json([
                'message' => 'Status Paid',
                'redirect' => route('admin.subscription-orders.index'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'request not found'], 500);
        }
    }

    public function getInvoice($invoice_id)
    {
        $subscriber = PlanSubscribe::with(['plan:id,subscriptionName', 'business:id,companyName,business_category_id,phoneNumber,address', 'business.category:id,name', 'gateway:id,name'])->findOrFail($invoice_id);
        return view('admin.subscribe-order.invoice', compact('subscriber'));
    }
}
