<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ZSystUserController extends Controller
{
    /**
     * Permission keys used for staff visibility.
     */
    private const VISIBILITY_KEYS = [
        'dashboardPermission',
        'addExpensePermission',
        'dueListPermission',
        'lossProfitPermission',
        'partiesPermission',
        'productPermission',
        'profileEditPermission',
        'purchaseListPermission',
        'purchasePermission',
        'reportsPermission',
        'salePermission',
        'salesListPermission',
        'stockPermission',
        'addIncomePermission',
    ];

    /**
     * Display a listing of staff users for the current business.
     */
    public function index(): JsonResponse
    {
        $data = User::where('business_id', auth()->user()->business_id)
            ->where('role', 'staff')
            ->latest()
            ->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created staff user.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|max:30',
            'password' => 'required|min:4|max:15',
            'email' => 'required|email|unique:users,email',
        ]);

        $businessId = auth()->user()->business_id;

        $data = User::create([
            'role' => 'staff',
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'business_id' => $businessId,
            'visibility' => $this->buildVisibility($request),
        ]);

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified staff user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        if ($user->business_id != $businessId) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $request->validate([
            'name' => 'required|max:30',
            'password' => 'nullable|min:4|max:15',
            'email' => 'required|email|unique:users,email,'.$user->id,
        ]);

        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'visibility' => $this->buildVisibility($request),
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified staff user.
     */
    public function destroy(User $user): JsonResponse
    {
        $businessId = auth()->user()->business_id;

        if ($user->business_id != $businessId) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $user->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }

    /**
     * Build the visibility permissions array from request input.
     */
    private function buildVisibility(Request $request): array
    {
        return collect(self::VISIBILITY_KEYS)->mapWithKeys(
            fn (string $key) => [$key => $request->input($key) === 'true']
        )->toArray();
    }
}
