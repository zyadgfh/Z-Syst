<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class WarehousePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Warehouses
            'warehouses-create',
            'warehouses-read',
            'warehouses-update',
            'warehouses-delete',

            // Stock Transfers
            'stock-transfers-create',
            'stock-transfers-read',
            'stock-transfers-update',
            'stock-transfers-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Warehouse permissions seeded successfully.');
    }
}
