<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User Management
            'users-view',
            'users-create',
            'users-edit',
            'users-delete',

            // Product Management
            'products-view',
            'products-create',
            'products-edit',
            'products-delete',

            // Sales Management
            'sales-view',
            'sales-create',
            'sales-edit',
            'sales-delete',

            // Purchase Management
            'purchases-view',
            'purchases-create',
            'purchases-edit',
            'purchases-delete',

            // Inventory Management
            'inventory-view',
            'inventory-create',
            'inventory-edit',
            'inventory-delete',

            // Reports
            'reports-view',
            'reports-export',

            // Settings
            'settings-view',
            'settings-edit',

            // Loyalty
            'loyalty-view',
            'loyalty-create',
            'loyalty-edit',
            'loyalty-delete',

            // Receipts
            'receipts-view',
            'receipts-create',
            'receipts-edit',
            'receipts-delete',

            // Audit Logs
            'audit-logs-view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);

        // Give all permissions to Super Admin
        $superAdminRole->givePermissionTo(Permission::all());

        // Give most permissions to Admin
        $adminRole->givePermissionTo([
            'users-view', 'users-create', 'users-edit',
            'products-view', 'products-create', 'products-edit', 'products-delete',
            'sales-view', 'sales-create', 'sales-edit',
            'purchases-view', 'purchases-create', 'purchases-edit',
            'inventory-view', 'inventory-create', 'inventory-edit',
            'reports-view', 'reports-export',
            'settings-view', 'settings-edit',
            'loyalty-view', 'loyalty-create', 'loyalty-edit',
            'receipts-view', 'receipts-create',
            'audit-logs-view',
        ]);

        // Give limited permissions to Staff
        $staffRole->givePermissionTo([
            'products-view',
            'sales-view', 'sales-create',
            'purchases-view',
            'inventory-view',
            'reports-view',
        ]);

        $this->command->info('Permissions seeded successfully');
    }
}
