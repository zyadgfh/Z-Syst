<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PermissionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Permission::query();

        if ($request->has('module')) {
            $query->byModule($request->module);
        }

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        $permissions = $query->select(['id', 'name', 'slug', 'description', 'module', 'group', 'sort_order', 'status', 'created_at'])
            ->ordered()
            ->paginate(50);

        return response()->json($permissions);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug',
            'description' => 'nullable|string',
            'module' => 'nullable|string|max:100',
            'group' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'boolean',
        ]);

        $permission = Permission::create($validated);

        $this->clearPermissionsCache();

        return response()->json(['message' => 'Permission created successfully.', 'data' => $permission], 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        $permission->load('roles');

        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:permissions,slug,'.$permission->id,
            'description' => 'nullable|string',
            'module' => 'nullable|string|max:100',
            'group' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
            'status' => 'boolean',
        ]);

        $permission->update($validated);

        $this->clearPermissionsCache();

        return response()->json(['message' => 'Permission updated successfully.', 'data' => $permission]);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $permission->delete();

        $this->clearPermissionsCache();

        return response()->json(['message' => 'Permission deleted successfully.']);
    }

    public function modules(): JsonResponse
    {
        $modules = Cache::remember('permissions:modules', 3600, function () {
            return Permission::select('module')
                ->distinct()
                ->whereNotNull('module')
                ->pluck('module');
        });

        return response()->json($modules);
    }

    public function groups(Request $request): JsonResponse
    {
        $module = $request->query('module');

        $groups = Cache::remember("permissions:groups:{$module}", 3600, function () use ($module) {
            $query = Permission::select('group')->distinct()->whereNotNull('group');

            if ($module) {
                $query->where('module', $module);
            }

            return $query->pluck('group');
        });

        return response()->json($groups);
    }

    private function clearPermissionsCache(): void
    {
        Cache::forget('permissions:all');
        Cache::forget('permissions:modules');
        Cache::flush();
    }
}
