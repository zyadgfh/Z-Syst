<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\LoyaltyPointsExpiringMail;
use App\Models\CustomerInteraction;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
        $this->middleware('permission:loyalty-read')->only('index', 'programs', 'transactions', 'interactions');
        $this->middleware('permission:loyalty-create')->only('create', 'store', 'createInteraction');
        $this->middleware('permission:loyalty-update')->only('edit', 'update');
        $this->middleware('permission:loyalty-delete')->only('destroy');
    }

    public function index()
    {
        return view('admin.loyalty.index');
    }

    public function programs()
    {
        $businessId = auth()->user()->business_id;
        $programs = LoyaltyProgram::forBusiness($businessId)
            ->withCount('transactions') // Fix N+1 query
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.loyalty.programs', compact('programs'));
    }

    public function create()
    {
        return view('admin.loyalty.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'points_per_currency' => 'required|integer|min:1',
            'min_points_for_reward' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $program = $this->loyaltyService->createProgram($request->all());

            return response()->json([
                'message' => __('Loyalty program created successfully'),
                'redirect' => route('admin.loyalty.programs'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating loyalty program: ').$e->getMessage(),
            ], 500);
        }
    }

    public function edit(LoyaltyProgram $program)
    {
        return view('admin.loyalty.edit', compact('program'));
    }

    public function update(Request $request, LoyaltyProgram $program)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'points_per_currency' => 'required|integer|min:1',
            'min_points_for_reward' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $program = $this->loyaltyService->updateProgram($program, $request->all());

            return response()->json([
                'message' => __('Loyalty program updated successfully'),
                'redirect' => route('admin.loyalty.programs'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating loyalty program: ').$e->getMessage(),
            ], 500);
        }
    }

    public function destroy(LoyaltyProgram $program)
    {
        try {
            $program->delete();

            return response()->json([
                'message' => __('Loyalty program deleted successfully'),
                'redirect' => route('admin.loyalty.programs'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error deleting loyalty program: ').$e->getMessage(),
            ], 500);
        }
    }

    public function transactions()
    {
        $businessId = auth()->user()->business_id;
        $transactions = LoyaltyTransaction::forBusiness($businessId)
            ->with(['party:id,name', 'program:id,name']) // Fix N+1
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.loyalty.transactions', compact('transactions'));
    }

    public function interactions()
    {
        $businessId = auth()->user()->business_id;
        $interactions = CustomerInteraction::forBusiness($businessId)
            ->with(['party:id,name', 'user:id,name', 'program:id,name']) // Fix N+1
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.loyalty.interactions', compact('interactions'));
    }

    /**
     * Create customer interaction
     */
    public function createInteraction(Request $request)
    {
        $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'party_id' => 'required|exists:parties,id',
            'type' => 'required|in:call,visit,email,meeting,support',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        try {
            $interaction = $this->loyaltyService->createInteraction($request->all());

            return response()->json([
                'message' => __('Customer interaction created successfully'),
                'redirect' => route('admin.loyalty.interactions'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating customer interaction: ').$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loyalty statistics
     */
    public function statistics(Request $request)
    {
        $businessId = $request->business_id ?? auth()->user()->business_id;
        $programId = $request->program_id ?? null;

        $statistics = $this->loyaltyService->getProgramStatistics($businessId, $programId);

        return response()->json($statistics);
    }

    /**
     * Get CRM statistics
     */
    public function crmStatistics(Request $request)
    {
        $filters = [
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $statistics = $this->loyaltyService->getCrmStatistics($businessId, $filters);

        return response()->json($statistics);
    }

    /**
     * Get customer loyalty data
     */
    public function customerLoyalty(Request $request)
    {
        $request->validate([
            'party_id' => 'required|exists:parties,id',
            'program_id' => 'required|exists:loyalty_programs,id',
        ]);

        $loyaltyData = $this->loyaltyService->getCustomerTransactions(
            $request->party_id,
            $request->program_id
        );

        return response()->json($loyaltyData);
    }

    /**
     * Get customer interactions
     */
    public function customerInteractions(Request $request)
    {
        $request->validate([
            'party_id' => 'required|exists:parties,id',
        ]);

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $interactions = $this->loyaltyService->getCustomerInteractions(
            $request->party_id,
            $businessId
        );

        return response()->json($interactions);
    }

    /**
     * Get top loyal customers
     */
    public function topLoyalCustomers(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:loyalty_programs,id',
        ]);

        $businessId = $request->business_id ?? auth()->user()->business_id;
        $limit = $request->limit ?? 10;

        $topCustomers = $this->loyaltyService->getTopLoyalCustomers($businessId, $request->program_id, $limit);

        return response()->json($topCustomers);
    }

    /**
     * Loyalty points analytics dashboard with charts.
     */
    public function analytics()
    {
        $businessId = auth()->user()->business_id;

        // Points earned vs redeemed over last 12 months (monthly)
        $monthlyTrend = LoyaltyPoint::where('business_id', $businessId)
            ->where('created_at', '>=', now()->subMonths(12))
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("SUM(CASE WHEN type = 'earned' AND points > 0 THEN points ELSE 0 END) as earned")
            ->selectRaw("SUM(CASE WHEN type = 'redeemed' THEN ABS(points) ELSE 0 END) as redeemed")
            ->selectRaw("SUM(CASE WHEN type = 'expired' THEN ABS(points) ELSE 0 END) as expired")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Points by type (earned, redeemed, expired)
        $byType = LoyaltyPoint::where('business_id', $businessId)
            ->selectRaw('type, SUM(ABS(points)) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        // Top point earners (users with most earned points)
        $topEarnersRows = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->select('user_id', DB::raw('SUM(points) as total_earned'), DB::raw('COUNT(*) as transactions'))
            ->groupBy('user_id')
            ->orderByDesc('total_earned')
            ->limit(10)
            ->get();

        // Batch-load users to avoid N+1 User::find() per row
        $userIds = $topEarnersRows->pluck('user_id')->filter()->values()->all();
        $usersById = User::select('id', 'name', 'email', 'image')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $topEarners = $topEarnersRows->map(function ($row) use ($usersById) {
            return [
                'user'          => $usersById->get($row->user_id),
                'total_earned'  => (int) $row->total_earned,
                'transactions'  => (int) $row->transactions,
            ];
        });

        // Points by status (active, expiring soon, expired)
        $activePoints = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('points');

        $expiringSoonPoints = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->sum('points');

        $expiredPoints = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'expired')
            ->sum(DB::raw('ABS(points)'));

        // Summary stats
        $totalIssued = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->sum('points');

        $totalRedeemed = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'redeemed')
            ->sum(DB::raw('ABS(points)'));

        $uniqueMembers = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->distinct('user_id')
            ->count('user_id');

        return view('admin.loyalty.analytics', compact(
            'monthlyTrend', 'byType', 'topEarners',
            'activePoints', 'expiringSoonPoints', 'expiredPoints',
            'totalIssued', 'totalRedeemed', 'uniqueMembers'
        ));
    }

    /**
     * Get loyalty points expiring soonest across all customers.
     */
    public function expiringSoonest(Request $request)
    {
        $businessId = auth()->user()->business_id;
        $days = $request->integer('days', 90);

        $expiringByUser = LoyaltyPoint::where('business_id', $businessId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expiration_notification_sent', false)
            ->select(
                'user_id',
                DB::raw('SUM(points) as total_points'),
                DB::raw('MIN(expires_at) as earliest_expiry'),
                DB::raw('COUNT(*) as record_count')
            )
            ->groupBy('user_id')
            ->orderBy('earliest_expiry')
            ->limit(20)
            ->get();

        // Batch-load users to avoid N+1 User::find() per row
        $userIds = $expiringByUser->pluck('user_id')->filter()->values()->all();
        $usersById = User::select('id', 'name', 'email', 'image')
            ->whereIn('id', $userIds)
            ->get()
            ->keyBy('id');

        $result = $expiringByUser->map(function ($row) use ($usersById) {
            return [
                'user'            => $usersById->get($row->user_id),
                'user_id'         => $row->user_id,
                'total_points'    => (int) $row->total_points,
                'earliest_expiry' => $row->earliest_expiry,
                'days_left'       => (int) now()->diffInDays($row->earliest_expiry, false),
                'record_count'    => (int) $row->record_count,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $result,
        ]);
    }

    /**
     * Send a one-click reminder email for a specific user's expiring points.
     */
    public function sendExpiryReminder(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $userId = $request->integer('user_id');
        $user = User::find($userId);

        if (!$user || !$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم لا يملك بريد إلكتروني',
            ], 400);
        }

        $expiringPoints = LoyaltyPoint::where('user_id', $userId)
            ->where('type', 'earned')
            ->where('points', '>', 0)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now())
            ->where('expires_at', '<=', now()->addDays(30))
            ->get();

        if ($expiringPoints->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد نقاط على وشك الانتهاء لهذا المستخدم',
            ], 404);
        }

        $totalPoints = $expiringPoints->sum('points');
        $daysUntilExpiry = (int) now()->diffInDays($expiringPoints->min('expires_at'), false);

        try {
            Mail::to($user->email)->send(new LoyaltyPointsExpiringMail(
                $user,
                $totalPoints,
                max($daysUntilExpiry, 1),
            ));

            return response()->json([
                'success' => true,
                'message' => "تم إرسال تذكير الانتهاء لـ {$user->name} بنجاح",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل إرسال البريد: ' . $e->getMessage(),
            ], 500);
        }
    }
}
