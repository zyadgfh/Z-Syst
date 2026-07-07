<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->select(['id', 'name', 'slug', 'description', 'color_badge', 'priority', 'is_system', 'status', 'created_at'])
            ->withCount('users')
            ->with('permissions')
            ->paginate(25);

        return response()->json($roles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string',
            'color_badge' => 'nullable|string|max:7',
            'priority' => 'nullable|integer|min:0',
            'is_system' => 'boolean',
            'status' => 'boolean',
        ]);

        $role = Role::create($validated);

        return response()->json(['message' => 'Role created successfully.', 'data' => $role], 201);
    }

    public function show(Role $role): JsonResponse
    {
        $role->load('permissions');

        return response()->json($role);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug,'.$role->id,
            'description' => 'nullable|string',
            'color_badge' => 'nullable|string|max:7',
            'priority' => 'nullable|integer|min:0',
            'is_system' => 'boolean',
            'status' => 'boolean',
        ]);

        $role->update($validated);

        $this->clearRoleCache($role);

        return response()->json(['message' => 'Role updated successfully.', 'data' => $role]);
    }

    public function destroy(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'Cannot delete system role.'], 403);
        }

        $role->delete();

        $this->clearRoleCache($role);

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions']);

        $this->clearRoleCache($role);

        return response()->json(['message' => 'Permissions assigned successfully.', 'data' => $role->load('permissions')]);
    }

    private function clearRoleCache(Role $role): void
    {
        Cache::forget("role:{$role->id}:permissions");
        Cache::forget('roles:all');
    }
}
