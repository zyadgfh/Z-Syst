<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class InsurancePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Insurance Companies
            'insurance-companies-create',
            'insurance-companies-read',
            'insurance-companies-update',
            'insurance-companies-delete',

            // Insurance Policies
            'insurance-policies-create',
            'insurance-policies-read',
            'insurance-policies-update',
            'insurance-policies-delete',

            // Insurance Claims
            'insurance-claims-create',
            'insurance-claims-read',
            'insurance-claims-update',
            'insurance-claims-delete',

            // Insurance Coverages
            'insurance-coverages-create',
            'insurance-coverages-read',
            'insurance-coverages-update',
            'insurance-coverages-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Insurance permissions seeded successfully.');
    }
}
