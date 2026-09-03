<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ItemsPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Items / Products Management
            'items-view',
            'items-create',
            'items-edit',
            'items-delete',

            // Product pricing (separate from general edit)
            'items-manage-prices',

            // Stock management
            'items-manage-stock',
            'items-view-stock',

            // Barcode management
            'items-manage-barcodes',
            'items-print-barcodes',

            // Import / Export
            'items-import',
            'items-export',

            // Categories management
            'items-manage-categories',

            // Bulk operations
            'items-bulk-update',

            // View cost and profit (sensitive data)
            'items-view-cost',
            'items-view-profit',

            // Archive / Restore
            'items-archive',
            'items-restore',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        // Assign permissions to existing roles
        $this->assignToRoles($permissions);

        $this->command->info('Items management permissions seeded successfully.');
    }

    protected function assignToRoles(array $permissions): void
    {
        // Shop Owner / Staff — full access to items
        foreach (['shop-owner', 'staff'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissions);
            }
        }

        // Manager — most items permissions except bulk and archive
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $managerRole->givePermissionTo(array_filter($permissions, fn ($p) => ! in_array($p, [
                'items-bulk-update',
                'items-archive',
                'items-restore',
            ])));
        }

        // Cashier — view items, search, print barcodes
        $cashierRole = Role::where('name', 'cashier')->first();
        if ($cashierRole) {
            $cashierRole->givePermissionTo([
                'items-view',
                'items-view-stock',
                'items-print-barcodes',
                'items-view-cost',
            ]);
        }

        // Pharmacist — view items, manage stock, view cost/profit
        $pharmacistRole = Role::where('name', 'pharmacist')->first();
        if ($pharmacistRole) {
            $pharmacistRole->givePermissionTo([
                'items-view',
                'items-view-stock',
                'items-manage-stock',
                'items-print-barcodes',
                'items-view-cost',
                'items-view-profit',
            ]);
        }

        // Warehouse — view items, manage stock, import
        $warehouseRole = Role::where('name', 'warehouse')->first();
        if ($warehouseRole) {
            $warehouseRole->givePermissionTo([
                'items-view',
                'items-view-stock',
                'items-manage-stock',
                'items-import',
                'items-print-barcodes',
            ]);
        }
    }
}
