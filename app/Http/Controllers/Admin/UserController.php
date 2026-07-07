<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->select(['id', 'name', 'username', 'email', 'phone', 'status', 'job_title', 'branch_id', 'department_id', 'created_at'])
            ->with(['roles', 'branch', 'department'])
            ->paginate(25);

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'profile_photo' => 'nullable|string',
            'status' => 'required|string|in:active,inactive,suspended',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $roleIds = $validated['role_ids'] ?? [];
        unset($validated['role_ids'], $validated['password_confirmation']);

        $user = User::create($validated);

        if (! empty($roleIds)) {
            $user->roles()->attach($roleIds);
        }

        return response()->json(['message' => 'User created successfully.', 'data' => $user->load('roles')], 201);
    }

    public function show(User $user): JsonResponse
    {
        $user->load('roles', 'branch', 'department', 'company');

        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,'.$user->id,
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'profile_photo' => 'nullable|string',
            'status' => 'required|string|in:active,inactive,suspended',
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'job_title' => 'nullable|string|max:255',
            'company_id' => 'nullable|exists:companies,id',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'integer|exists:roles,id',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $roleIds = $validated['role_ids'] ?? [];
        unset($validated['role_ids'], $validated['password_confirmation']);

        $user->update($validated);

        if (! empty($roleIds)) {
            $user->roles()->sync($roleIds);
        }

        Cache::forget("user:{$user->id}:permissions");

        return response()->json(['message' => 'User updated successfully.', 'data' => $user->load('roles')]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function assignRoles(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'integer|exists:roles,id',
        ]);

        $user->roles()->sync($validated['roles']);

        Cache::forget("user:{$user->id}:permissions");

        return response()->json(['message' => 'Roles assigned successfully.', 'data' => $user->load('roles')]);
    }

    public function removeRole(Request $request, User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);

        Cache::forget("user:{$user->id}:permissions");

        return response()->json(['message' => 'Role removed successfully.']);
    }
}
