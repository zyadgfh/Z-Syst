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
        $users = User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])->latest()->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function zsystFilter(Request $request)
    {
        $users = User::whereNotIn('role', ['superadmin', 'staff', 'shop-owner'])->when(request('search'), function ($q) {
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
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|confirmed',
            'image' => 'nullable|image',
        ]);

        $user = User::create($request->except('image', 'password') + [
            'image' => $request->image ? $this->upload($request, 'image') : null,
            'password' => Hash::make($request->password),
        ]);

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
            'role' => 'required|string',
            'phone' => 'nullable|string',
            'country' => 'nullable|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|confirmed',
            'image' => 'nullable|image',
        ]);

        $role = Role::where('name', $request->role)->first();
        $user->roles()->sync($role->id);
        $user->update($request->except('image', 'password') + [
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
        $deleted = $this->userManagementService->bulkDeleteUsers($request->ids);

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

        $updated = $this->userManagementService->bulkChangeUserStatus($request->ids, $request->status);

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
        $filters = [
            'role' => $request->role,
            'status' => $request->status,
            'business_id' => $request->business_id,
        ];

        $statistics = $this->userManagementService->getUserStatistics($filters);

        return response()->json($statistics);
    }

    /**
     * Get role statistics
     */
    public function roleStatistics()
    {
        $statistics = $this->userManagementService->getRoleStatistics();

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

        $sourceUser = User::where('id', $request->source_user_id)
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();
        $targetUser = User::where('id', $request->target_user_id)
            ->where('business_id', auth()->user()->business_id)
            ->firstOrFail();

        try {
            $user = $this->userManagementService->cloneUserPermissions($sourceUser, $targetUser);

            return response()->json([
                'message' => __('Permissions cloned successfully'),
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => __('Error cloning permissions: ').$e->getMessage(),
            ], 500);
        }
    }

    public function exportExcel()
    {
        return Excel::download(new UserExport, 'users.xlsx');
    }

    public function exportCsv()
    {
        return Excel::download(new UserExport, 'users.csv');
    }
}
