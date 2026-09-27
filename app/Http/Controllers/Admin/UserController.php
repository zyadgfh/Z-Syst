<?php

namespace App\Http\Controllers\Admin;

use App\Exports\UserExport;
use App\Helpers\HasUploader;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    use HasUploader;

    protected UserManagementService $userManagementService;

    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
        $this->middleware('permission:users-create')->only('create', 'store');
        $this->middleware('permission:users-read')->only('index', 'show', 'statistics', 'roleStatistics');
        $this->middleware('permission:users-update')->only('edit', 'update', 'bulkChangeStatus');
        $this->middleware('permission:users-delete')->only('destroy', 'deleteAll', 'bulkDelete');
    }

    public function index(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $users = User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])
            ->when($businessId !== null, fn ($query) => $query->where('business_id', $businessId))
            ->latest()
            ->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function zsystFilter(Request $request)
    {
        $businessId = $this->currentBusinessId();
        $users = User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])
            ->when($businessId !== null, fn ($query) => $query->where('business_id', $businessId))
            ->when(request('search'), function ($q) {
            $q->where(function ($q) {
                $q->where('name', 'like', '%'.request('search').'%')
                    ->orWhere('email', 'like', '%'.request('search').'%')
                    ->orWhere('role', 'like', '%'.request('search').'%')
                    ->orWhere('phone', 'like', '%'.request('search').'%');
            });
        })
            ->latest()
            ->paginate($request->per_page ?? 10);

        if ($request->ajax()) {
            return response()->json([
                'data' => view('admin.users.datas', compact('users'))->render(),
            ]);
        }

        return redirect(url()->previous());
    }

    public function create()
    {
        $roles = Role::where('name', '!=', 'superadmin')->latest()->get();

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|not_in:superadmin',
            'phone' => 'nullable|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed',
            'image' => 'nullable|image',
            'business_id' => 'nullable|integer|exists:businesses,id',
        ]);

        $businessId = $this->currentBusinessId();
        if ($businessId === null && $request->filled('business_id')) {
            $businessId = (int) $request->business_id;
        }

        $user = User::create([
            'name' => $request->name,
            'role' => $request->role,
            'phone' => $request->phone,
            'email' => $request->email,
            'image' => $request->image ? $this->upload($request, 'image') : null,
            'password' => Hash::make($request->password),
        ]);

        if ($businessId !== null) {
            $user->assignToBusiness($businessId);
        }

        $role = Role::where('name', $request->role)->first();
        $user->roles()->sync($role->id);

        sendNotification($user->id, route('admin.users.index', ['users' => $request->role]), __(ucfirst($request->role).' has been created.'), 'action', null, null, true);

        return response()->json([
            'message' => __(ucfirst($request->role).' created successfully'),
            'redirect' => route('admin.users.index', ['users' => $request->role]),
        ]);
    }

    public function edit(User $user)
    {
        if ($user->role == 'superadmin') {
            abort(403);
        }
        $roles = Role::where('name', '!=', 'superadmin')->latest()->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        if ($user->role == 'superadmin') {
            return response()->json(__('You can not update a superadmin.'), 400);
        }
        $request->validate([
            'role' => 'required|string|not_in:superadmin',
            'phone' => 'nullable|string',
            'country' => 'nullable|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|confirmed',
            'image' => 'nullable|image',
        ]);

        $role = Role::where('name', $request->role)->firstOrFail();
        $user->roles()->sync($role->id);
        $user->update([
            'name' => $request->name,
            'role' => $request->role,
            'phone' => $request->phone,
            'country' => $request->country,
            'email' => $request->email,
            'image' => $request->image ? $this->upload($request, 'image', $user->image) : $user->image,
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);

        return response()->json([
            'message' => __('Staff updated successfully'),
            'redirect' => route('admin.users.index'),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->role == 'superadmin') {
            return response()->json(__('You can not delete a superadmin.'), 400);
        }

        if (file_exists($user->image)) {
            Storage::delete($user->image);
        }

        $user->delete();

        return response()->json([
            'message' => __('Staff deleted successfully'),
            'redirect' => route('admin.users.index'),
        ]);
    }

    public function deleteAll(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:users,id',
        ]);

        $deleted = $this->userManagementService->bulkDeleteUsers(
            $request->ids,
            $this->currentBusinessId()
        );

        return response()->json([
            'message' => __('Selected Staff deleted successfully'),
            'redirect' => route('admin.users.index'),
        ]);
    }

    /**
     * Bulk change user status
     */
    public function bulkChangeStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
            'status' => 'required|integer|in:0,1',
        ]);

        $updated = $this->userManagementService->bulkChangeUserStatus(
            $request->ids,
            $request->status,
            $this->currentBusinessId()
        );

        return response()->json([
            'message' => __('User status updated successfully'),
            'updated_count' => $updated,
        ]);
    }

    /**
     * Get user statistics
     */
    public function statistics(Request $request)
    {
        $businessId = $this->currentBusinessId();

        $request->validate([
            'business_id' => 'nullable|integer|exists:businesses,id',
            'role' => 'nullable|string',
            'status' => 'nullable|integer',
        ]);

        if ($businessId === null && $request->filled('business_id')) {
            $businessId = (int) $request->business_id;
        }

        $filters = [
            'role' => $request->role,
            'status' => $request->status,
            'business_id' => $businessId,
        ];

        $statistics = $this->userManagementService->getUserStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get role statistics
     */
    public function roleStatistics()
    {
        $statistics = $this->userManagementService->getRoleStatistics($this->currentBusinessId());

        return response()->json($statistics);
    }

    /**
     * Get permissions grouped by module
     */
    public function permissionsGrouped()
    {
        $permissions = $this->userManagementService->getPermissionsGrouped();

        return response()->json($permissions);
    }

    /**
     * Get user effective permissions
     */
    public function userPermissions(User $user)
    {
        if ($user->role == 'superadmin') {
            return response()->json(['error' => 'Cannot view superadmin permissions'], 403);
        }

        $permissions = $this->userManagementService->getUserEffectivePermissions($user);

        return response()->json($permissions);
    }

    /**
     * Clone user permissions
     */
    public function clonePermissions(Request $request)
    {
        $request->validate([
            'source_user_id' => 'required|exists:users,id',
            'target_user_id' => 'required|exists:users,id',
        ]);

        $businessId = $this->currentBusinessId();

        $sourceQuery = User::query();
        $targetQuery = User::query();

        if ($businessId !== null) {
            $sourceQuery->where('business_id', $businessId);
            $targetQuery->where('business_id', $businessId);
        }

        $sourceUser = $sourceQuery->findOrFail($request->source_user_id);
        $targetUser = $targetQuery->findOrFail($request->target_user_id);

        try {
            $user = $this->userManagementService->cloneUserPermissions($sourceUser, $targetUser, $businessId);

            return response()->json([
                'message' => __('Permissions cloned successfully'),
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error cloning permissions: ') . $e->getMessage(),
            ], 500);
        }
    }

    public function exportExcel()
    {
        return Excel::download(new UserExport($this->currentBusinessId()), 'users.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new UserExport($this->currentBusinessId()), 'users.csv');
    }

    private function currentBusinessId(): ?int
    {
        $user = auth()->user();

        if ($user?->role === 'superadmin') {
            return null;
        }

        if (empty($user?->business_id)) {
            abort(403, 'Tenant context is required.');
        }

        return (int) $user->business_id;
    }
}
