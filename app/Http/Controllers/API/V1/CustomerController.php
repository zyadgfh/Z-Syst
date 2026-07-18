<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Customer Controller for CRM
 * 
 * Enhanced customer management with credit control and loyalty
 */
class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter by type
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->get('customer_type'));
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by loyalty tier
        if ($request->filled('loyalty_tier')) {
            $query->where('loyalty_tier', $request->get('loyalty_tier'));
        }

        // Top customers
        if ($request->boolean('top_customers')) {
            $query->withSum('sales', 'total_amount')
                ->orderByDesc('sales_sum_total_amount');
        }

        $customers = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $customers->items(),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_type' => 'required|in:individual,pharmacy,clinic,hospital',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|array',
            'credit_limit' => 'nullable|numeric|min:0',
            'insurance_company_id' => 'nullable|exists:insurance_companies,id',
        ]);

        $validated['company_id'] = app('tenant.company_id');
        $validated['created_by'] = auth()->id();

        $customer = Customer::create($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'تم إنشاء العميل بنجاح',
        ], 201);
    }

    /**
     * Display a single customer.
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'sales' => function ($q) {
                $q->latest()->limit(10);
            },
            'prescriptions' => function ($q) {
                $q->latest()->limit(10);
            },
            'loyaltyTransactions' => function ($q) {
                $q->latest()->limit(20);
            },
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer,
        ]);
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'customer_type' => 'sometimes|in:individual,pharmacy,clinic,hospital',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'allergies' => 'nullable|array',
            'medical_history' => 'nullable|array',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['updated_by'] = auth()->id();

        $customer->update($validated);

        return response()->json([
            'success' => true,
            'data' => $customer,
            'message' => 'تم تحديث العميل بنجاح',
        ]);
    }

    /**
     * Add loyalty points to customer.
     */
    public function addLoyaltyPoints(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'reason' => 'nullable|string',
        ]);

        $customer->addLoyaltyPoints($validated['points'], $validated['reason'] ?? 'manual');

        return response()->json([
            'success' => true,
            'data' => $customer->fresh(),
            'message' => 'تم إضافة نقاط الولاء بنجاح',
        ]);
    }

    /**
     * Get customer statistics.
     */
    public function statistics(Customer $customer): JsonResponse
    {
        $stats = [
            'total_sales' => $customer->sales()->sum('total_amount'),
            'total_transactions' => $customer->sales()->count(),
            'loyalty_points' => $customer->loyalty_points,
            'loyalty_tier' => $customer->loyalty_tier,
            'last_purchase' => $customer->sales()->latest()->first()?->created_at,
            'outstanding_balance' => $customer->outstanding_balance,
            'credit_utilization' => $customer->credit_limit > 0 
                ? round(($customer->outstanding_balance / $customer->credit_limit) * 100, 2) 
                : 0,
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get customers needing follow-up.
     */
    public function needsFollowUp(): JsonResponse
    {
        $customers = Customer::needFollowUp()
            ->withCount('sales')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers,
        ]);
    }
}