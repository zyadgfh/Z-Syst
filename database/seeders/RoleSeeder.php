<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Administrator',
                'slug' => 'super-admin',
                'description' => 'Full system access with all permissions',
                'color_badge' => '#DC2626',
                'priority' => 100,
                'is_system' => true,
                'status' => true,
            ],
            [
                'name' => 'Company Owner',
                'slug' => 'company-owner',
                'description' => 'Company owner with management access',
                'color_badge' => '#7C3AED',
                'priority' => 90,
                'is_system' => true,
                'status' => true,
            ],
            [
                'name' => 'General Manager',
                'slug' => 'general-manager',
                'description' => 'General manager with broad permissions',
                'color_badge' => '#2563EB',
                'priority' => 80,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Branch Manager',
                'slug' => 'branch-manager',
                'description' => 'Branch-level manager',
                'color_badge' => '#059669',
                'priority' => 70,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Pharmacist',
                'slug' => 'pharmacist',
                'description' => 'Pharmacy professional',
                'color_badge' => '#0891B2',
                'priority' => 60,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Point of sale operator',
                'color_badge' => '#D97706',
                'priority' => 50,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Accountant',
                'slug' => 'accountant',
                'description' => 'Financial operations',
                'color_badge' => '#65A30D',
                'priority' => 60,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Inventory Manager',
                'slug' => 'inventory-manager',
                'description' => 'Inventory and stock management',
                'color_badge' => '#9333EA',
                'priority' => 60,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'HR Manager',
                'slug' => 'hr-manager',
                'description' => 'Human resources management',
                'color_badge' => '#DB2777',
                'priority' => 60,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Sales Manager',
                'slug' => 'sales-manager',
                'description' => 'Sales team management',
                'color_badge' => '#EA580C',
                'priority' => 60,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Receptionist',
                'slug' => 'receptionist',
                'description' => 'Front desk operations',
                'color_badge' => '#0284C7',
                'priority' => 40,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Customer Support',
                'slug' => 'customer-support',
                'description' => 'Customer service',
                'color_badge' => '#16A34A',
                'priority' => 40,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Audit and compliance',
                'color_badge' => '#B91C1C',
                'priority' => 70,
                'is_system' => false,
                'status' => true,
            ],
            [
                'name' => 'Read Only User',
                'slug' => 'read-only',
                'description' => 'View-only access',
                'color_badge' => '#6B7280',
                'priority' => 10,
                'is_system' => false,
                'status' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::create($roleData);
        }
    }
}
