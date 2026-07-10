<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AcnooUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();

        $data = User::with('branch:id,name')
            ->where('business_id', $user->business_id)
            ->when($user->role == 'staff', function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id);
            })
            ->when($user->branch_id || $user->active_branch_id, function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id ?? $user->active_branch_id);
            })
            ->where('id', '!=', auth()->id())
            ->where('role', 'staff')
            ->latest()
            ->get();

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
            'password' => 'required|min:4|max:15',
            'email' => 'required|email|unique:users,email',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $data = User::create([
            'role' => 'staff',
            'name' => $request->name,
            'email' => $request->email,
            'visibility' => $request->visibility,
            'password' => Hash::make($request->password),
            'business_id' => auth()->user()->business_id,
            'branch_id' => $request->branch_id ?? auth()->user()->branch_id ?? auth()->user()->active_branch_id,
        ]);

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
            'password' => 'nullable|min:4|max:15',
            'branch_id' => 'nullable|exists:branches,id',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'branch_id' => $request->branch_id,
            'visibility' => $request->visibility,
            'business_id' => auth()->user()->business_id,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
            'branch_id' => $request->branch_id ?? auth()->user()->branch_id ?? auth()->user()->active_branch_id,
        ]);

        return response()->json([
            'message' => __('Data saved successfully.')
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
