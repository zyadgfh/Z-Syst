<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyTransaction;
use App\Models\CustomerInteraction;
use App\Models\Party;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;

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
            'business_id' => 'nullable|integer|exists:businesses,id',
            'name' => 'required|string|max:255',
            'points_per_currency' => 'required|integer|min:1',
            'min_points_for_reward' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        try {
            $program = $this->loyaltyService->createProgram([
                'business_id' => auth()->user()->role === 'superadmin'
                    ? $request->business_id
                    : auth()->user()->business_id,
                'name' => $request->name,
                'points_per_currency' => $request->points_per_currency,
                'min_points_for_reward' => $request->min_points_for_reward,
                'is_active' => $request->boolean('is_active'),
            ]);

            return response()->json([
                'message' => __('Loyalty program created successfully'),
                'redirect' => route('admin.loyalty.programs'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating loyalty program: ') . $e->getMessage(),
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
            $program = $this->loyaltyService->updateProgram($program, [
                'name' => $request->name,
                'points_per_currency' => $request->points_per_currency,
                'min_points_for_reward' => $request->min_points_for_reward,
                'is_active' => $request->boolean('is_active'),
            ]);

            return response()->json([
                'message' => __('Loyalty program updated successfully'),
                'redirect' => route('admin.loyalty.programs'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error updating loyalty program: ') . $e->getMessage(),
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
                'message' => __('Error deleting loyalty program: ') . $e->getMessage(),
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
            'business_id' => 'nullable|integer|exists:businesses,id',
            'party_id' => 'required|integer|exists:parties,id',
            'type' => 'required|in:call,visit,email,meeting,support',
            'notes' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
            'loyalty_program_id' => 'nullable|integer|exists:loyalty_programs,id',
        ]);

        try {
            $interaction = $this->loyaltyService->createInteraction([
                'business_id' => auth()->user()->role === 'superadmin'
                    ? $request->business_id
                    : auth()->user()->business_id,
                'party_id' => $request->party_id,
                'type' => $request->type,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
                'loyalty_program_id' => $request->loyalty_program_id,
            ]);

            return response()->json([
                'message' => __('Customer interaction created successfully'),
                'redirect' => route('admin.loyalty.interactions'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error creating customer interaction: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get loyalty statistics
     */
    public function statistics(Request $request)
    {
        $businessId = auth()->user()->role === 'superadmin' && $request->filled('business_id')
            ? (int) $request->business_id
            : (int) auth()->user()->business_id;
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

        $program = LoyaltyProgram::where('business_id', (int) auth()->user()->business_id)->findOrFail($request->program_id);
        CustomerInteraction::query(); // keep model import usage stable
        Party::where('business_id', (int) auth()->user()->business_id)->findOrFail($request->party_id);

        $loyaltyData = $this->loyaltyService->getCustomerTransactions(
            $request->party_id,
            $program->id
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
}
