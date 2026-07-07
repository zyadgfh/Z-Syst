<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard', 'group' => 'View', 'sort_order' => 1],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'Users', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create User', 'slug' => 'users.create', 'module' => 'Users', 'group' => 'Management', 'sort_order' => 2],
            ['name' => 'Edit User', 'slug' => 'users.edit', 'module' => 'Users', 'group' => 'Management', 'sort_order' => 3],
            ['name' => 'Delete User', 'slug' => 'users.delete', 'module' => 'Users', 'group' => 'Management', 'sort_order' => 4],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'Roles', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create Role', 'slug' => 'roles.create', 'module' => 'Roles', 'group' => 'Management', 'sort_order' => 2],
            ['name' => 'Edit Role', 'slug' => 'roles.edit', 'module' => 'Roles', 'group' => 'Management', 'sort_order' => 3],
            ['name' => 'Delete Role', 'slug' => 'roles.delete', 'module' => 'Roles', 'group' => 'Management', 'sort_order' => 4],
            ['name' => 'Assign Permissions', 'slug' => 'roles.assign-permissions', 'module' => 'Roles', 'group' => 'Management', 'sort_order' => 5],

            // Permissions
            ['name' => 'View Permissions', 'slug' => 'permissions.view', 'module' => 'Permissions', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create Permission', 'slug' => 'permissions.create', 'module' => 'Permissions', 'group' => 'Management', 'sort_order' => 2],
            ['name' => 'Edit Permission', 'slug' => 'permissions.edit', 'module' => 'Permissions', 'group' => 'Management', 'sort_order' => 3],
            ['name' => 'Delete Permission', 'slug' => 'permissions.delete', 'module' => 'Permissions', 'group' => 'Management', 'sort_order' => 4],

            // Branches
            ['name' => 'View Branches', 'slug' => 'branches.view', 'module' => 'Branches', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create Branch', 'slug' => 'branches.create', 'module' => 'Branches', 'group' => 'Management', 'sort_order' => 2],
            ['name' => 'Edit Branch', 'slug' => 'branches.edit', 'module' => 'Branches', 'group' => 'Management', 'sort_order' => 3],
            ['name' => 'Delete Branch', 'slug' => 'branches.delete', 'module' => 'Branches', 'group' => 'Management', 'sort_order' => 4],

            // Products
            ['name' => 'View Products', 'slug' => 'products.view', 'module' => 'Products', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create Product', 'slug' => 'products.create', 'module' => 'Products', 'group' => 'Management', 'sort_order' => 2],
            ['name' => 'Edit Product', 'slug' => 'products.edit', 'module' => 'Products', 'group' => 'Management', 'sort_order' => 3],
            ['name' => 'Delete Product', 'slug' => 'products.delete', 'module' => 'Products', 'group' => 'Management', 'sort_order' => 4],
            ['name' => 'Import Products', 'slug' => 'products.import', 'module' => 'Products', 'group' => 'Operations', 'sort_order' => 5],
            ['name' => 'Export Products', 'slug' => 'products.export', 'module' => 'Products', 'group' => 'Operations', 'sort_order' => 6],

            // Sales
            ['name' => 'View Sales', 'slug' => 'sales.view', 'module' => 'Sales', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Create Sale', 'slug' => 'sales.create', 'module' => 'Sales', 'group' => 'Operations', 'sort_order' => 2],
            ['name' => 'Edit Sale', 'slug' => 'sales.edit', 'module' => 'Sales', 'group' => 'Operations', 'sort_order' => 3],
            ['name' => 'Delete Sale', 'slug' => 'sales.delete', 'module' => 'Sales', 'group' => 'Operations', 'sort_order' => 4],
            ['name' => 'Process Refund', 'slug' => 'sales.refund', 'module' => 'Sales', 'group' => 'Operations', 'sort_order' => 5],

            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'Reports', 'group' => 'Reporting', 'sort_order' => 1],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'Reports', 'group' => 'Reporting', 'sort_order' => 2],

            // Settings
            ['name' => 'View Settings', 'slug' => 'settings.view', 'module' => 'Settings', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Edit Settings', 'slug' => 'settings.edit', 'module' => 'Settings', 'group' => 'Management', 'sort_order' => 2],

            // Activity Logs
            ['name' => 'View Activity Logs', 'slug' => 'activity-logs.view', 'module' => 'Activity Logs', 'group' => 'Audit', 'sort_order' => 1],

            // Subscriptions
            ['name' => 'View Subscriptions', 'slug' => 'subscriptions.view', 'module' => 'Subscriptions', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Manage Subscriptions', 'slug' => 'subscriptions.manage', 'module' => 'Subscriptions', 'group' => 'Management', 'sort_order' => 2],

            // Branch Limits
            ['name' => 'View Branch Limits', 'slug' => 'branch-limits.view', 'module' => 'Branch Limits', 'group' => 'Management', 'sort_order' => 1],
            ['name' => 'Manage Branch Limits', 'slug' => 'branch-limits.manage', 'module' => 'Branch Limits', 'group' => 'Management', 'sort_order' => 2],
        ];

        foreach ($permissions as $permissionData) {
            Permission::create($permissionData);
        }
    }
}
