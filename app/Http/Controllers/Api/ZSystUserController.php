<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ZSystUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = User::where('business_id', auth()->user()->business_id)->where('role', 'staff')->latest()->get();

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:30',
            'password' => 'required|min:10|max:72',
            'email' => 'required|email',
        ]);

        try {
            $data = app(\App\Services\UserManagementService::class)->createUserWithRole([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'business_id' => auth()->user()->business_id,
                'role' => 'staff',
                'status' => 'active',
                'visibility' => [
                'dashboardPermission' => $request->dashboardPermission == 'true' ? true : false,
                'addExpensePermission' => $request->addExpensePermission == 'true' ? true : false,
                'dueListPermission' => $request->dueListPermission == 'true' ? true : false,
                'lossProfitPermission' => $request->lossProfitPermission == 'true' ? true : false,
                'partiesPermission' => $request->partiesPermission == 'true' ? true : false,
                'productPermission' => $request->productPermission == 'true' ? true : false,
                'profileEditPermission' => $request->profileEditPermission == 'true' ? true : false,
                'purchaseListPermission' => $request->purchaseListPermission == 'true' ? true : false,
                'purchasePermission' => $request->purchasePermission == 'true' ? true : false,
                'reportsPermission' => $request->reportsPermission == 'true' ? true : false,
                'salePermission' => $request->salePermission == 'true' ? true : false,
                'salesListPermission' => $request->salesListPermission == 'true' ? true : false,
                'stockPermission' => $request->stockPermission == 'true' ? true : false,
                'addIncomePermission' => $request->addIncomePermission == 'true' ? true : false,
                ],
            ], 'staff');
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'message' => __('Data saved successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|max:30',
            'password' => 'nullable|min:10|max:72',
            'email' => 'required|email',
        ]);

        try {
            $user = app(\App\Services\UserManagementService::class)->updateUserWithRole($user, [
                'name' => $request->name,
                'business_id' => auth()->user()->business_id,
                'status' => $user->status,
                'password' => $request->password,
                'visibility' => [
                'dashboardPermission' => $request->dashboardPermission == 'true' ? true : false,
                'addExpensePermission' => $request->addExpensePermission == 'true' ? true : false,
                'dueListPermission' => $request->dueListPermission == 'true' ? true : false,
                'lossProfitPermission' => $request->lossProfitPermission == 'true' ? true : false,
                'partiesPermission' => $request->partiesPermission == 'true' ? true : false,
                'productPermission' => $request->productPermission == 'true' ? true : false,
                'profileEditPermission' => $request->profileEditPermission == 'true' ? true : false,
                'purchaseListPermission' => $request->purchaseListPermission == 'true' ? true : false,
                'purchasePermission' => $request->purchasePermission == 'true' ? true : false,
                'reportsPermission' => $request->reportsPermission == 'true' ? true : false,
                'salePermission' => $request->salePermission == 'true' ? true : false,
                'salesListPermission' => $request->salesListPermission == 'true' ? true : false,
                'stockPermission' => $request->stockPermission == 'true' ? true : false,
                'addIncomePermission' => $request->addIncomePermission == 'true' ? true : false,
                ],
            ], (string) $user->role);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => __('Data saved successfully.'),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'message' => __('Data deleted successfully.'),
        ]);
    }
}
