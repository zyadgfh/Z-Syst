<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoyaltyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();

        $transactions = LoyaltyPoint::where('user_id', $user->id)
            ->with('order')
            ->latest()
            ->paginate(15);

        $stats = [
            'balance'    => $user->loyalty_points_balance,
            'earned'     => LoyaltyPoint::where('user_id', $user->id)->where('type', 'earned')->sum('points'),
            'redeemed'   => abs(LoyaltyPoint::where('user_id', $user->id)->where('type', 'redeemed')->sum('points')),
            'conversion' => LoyaltyPoint::getRedemptionRate(),
        ];

        return view('customer.loyalty.index', compact('transactions', 'stats'));
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $points = $request->points;
        $redemptionRate = LoyaltyPoint::getRedemptionRate();
        $discountAmount = round($points / $redemptionRate, 2);

        if ($user->loyalty_points_balance < $points) {
            return back()->with('error', 'رصيد النقاط غير كافٍ');
        }

        // Create a coupon code for the redeemed amount
        $code = 'LP-' . strtoupper(uniqid());

        \App\Models\Coupon::create([
            'business_id'       => $user->business_id,
            'code'              => $code,
            'description'       => "استبدال {$points} نقطة ولاء",
            'type'              => 'fixed',
            'value'             => $discountAmount,
            'minimum_order_amount' => 0,
            'usage_limit'       => 1,
            'usage_limit_per_user' => 1,
            'expires_at'        => now()->addMonths(3),
            'active'            => true,
            'single_use'        => true,
        ]);

        LoyaltyPoint::redeem($user, $points, $discountAmount, "استبدال {$points} نقطة → كوبون {$code}");

        return back()->with('success', "تم إنشاء كوبون {$code} بقيمة {$discountAmount} — صالح لمدة 3 أشهر");
    }
}
