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
            // Dashboard
            'dashboard-read',

            // User Management
            'users-read', 'users-create', 'users-update', 'users-delete',
            'users-view', 'users-edit', // legacy aliases

            // Roles & Permissions
            'roles-read', 'roles-create', 'roles-update',
            'permissions-read',

            // Product Management
            'products-view', 'products-create', 'products-edit', 'products-delete',

            // Sales Management
            'sales-view', 'sales-create', 'sales-edit', 'sales-delete',

            // Purchase Management
            'purchases-view', 'purchases-create', 'purchases-edit', 'purchases-delete',

            // Inventory Management
            'inventory-view', 'inventory-create', 'inventory-edit', 'inventory-delete',

            // Stock
            'stock-read', 'stock-adjust',

            // Reports
            'reports-view', 'reports-export',
            'subscription-reports-read', 'manual-payment-reports-read',
            'active-store-reports-read', 'expired-store-reports-read',

            // Settings
            'settings-view', 'settings-edit', 'settings-read', 'settings-update',
            'web-settings-read',

            // Loyalty
            'loyalty-view', 'loyalty-create', 'loyalty-edit', 'loyalty-delete',
            'loyalty-read', 'loyalty-update',

            // Receipts
            'receipts-view', 'receipts-create', 'receipts-edit', 'receipts-delete',
            'receipts-read', 'receipts-update',

            // Supplier Management
            'suppliers-view', 'suppliers-create', 'suppliers-edit', 'suppliers-delete',

            // Business
            'business-read', 'business-create', 'business-update', 'business-delete',
            'business-categories-read', 'business-categories-update', 'business-categories-delete',

            // Testimonials (add update)
            'testimonials-update',

            // Features (add delete)
            'features-delete',

            // Messages (add update, delete)
            'messages-update', 'messages-delete',

            // Audit Logs (add show)
            'audit-logs-show',

            // Banners
            'banners-read', 'banners-create', 'banners-update', 'banners-delete',

            // Blogs
            'blogs-read', 'blogs-create', 'blogs-update', 'blogs-delete',

            // Testimonials
            'testimonials-read', 'testimonials-delete',

            // Features
            'features-read', 'features-update',

            // Interfaces
            'interfaces-read', 'interfaces-update', 'interfaces-delete',

            // Messages
            'messages-read',

            // Plans
            'plans-read', 'plans-create', 'plans-update', 'plans-delete',

            // Currencies
            'currencies-read', 'currencies-create', 'currencies-update', 'currencies-delete',

            // Gateways
            'gateways-read', 'gateways-create', 'gateways-edit', 'gateways-update', 'gateways-delete',

            // Coupons
            'coupons-read', 'coupons-create', 'coupons-edit', 'coupons-update', 'coupons-delete',

            // Notifications
            'notifications-read',

            // Prescriptions
            'prescriptions-read', 'prescriptions-update', 'prescriptions-delete',

            // Warehouses
            'warehouses-read', 'warehouses-create', 'warehouses-update', 'warehouses-delete',

            // Traceability
            'traceability-read', 'recalls-create',

            // Term & Privacy
            'term-condition-read', 'privacy-policy-read',

            // Maintenance Mode
            'maintenance-read', 'maintenance-write',

            // Audit Logs
            'audit-logs-view', 'audit-logs-read', 'audit-logs-delete',
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
            'dashboard-read',
            'users-read', 'users-create', 'users-update', 'users-delete',
            'roles-read', 'roles-create', 'roles-update', 'permissions-read',
            'products-view', 'products-create', 'products-edit', 'products-delete',
            'sales-view', 'sales-create', 'sales-edit',
            'purchases-view', 'purchases-create', 'purchases-edit',
            'inventory-view', 'inventory-create', 'inventory-edit',
            'stock-read', 'stock-adjust',
            'reports-view', 'reports-export',
            'subscription-reports-read', 'manual-payment-reports-read',
            'active-store-reports-read', 'expired-store-reports-read',
            'settings-view', 'settings-edit', 'settings-read', 'settings-update', 'web-settings-read',
            'loyalty-view', 'loyalty-create', 'loyalty-edit', 'loyalty-read', 'loyalty-update',
            'receipts-view', 'receipts-create', 'receipts-read', 'receipts-update',
            'suppliers-view', 'suppliers-create', 'suppliers-edit',
            'business-read', 'business-create', 'business-update', 'business-delete',
            'business-categories-read', 'business-categories-update', 'business-categories-delete',
            'testimonials-update',
            'features-delete',
            'messages-update', 'messages-delete',
            'audit-logs-show', 'audit-logs-read', 'audit-logs-delete',
            'banners-read', 'banners-create', 'banners-update', 'banners-delete',
            'blogs-read', 'blogs-create', 'blogs-update', 'blogs-delete',
            'testimonials-read', 'testimonials-delete',
            'features-read', 'features-update',
            'interfaces-read', 'interfaces-update', 'interfaces-delete',
            'messages-read',
            'plans-read', 'plans-create', 'plans-update', 'plans-delete',
            'currencies-read', 'currencies-create', 'currencies-update', 'currencies-delete',
            'gateways-read', 'gateways-create', 'gateways-edit', 'gateways-update', 'gateways-delete',
            'coupons-read', 'coupons-create', 'coupons-edit', 'coupons-update', 'coupons-delete',
            'notifications-read',
            'prescriptions-read', 'prescriptions-update', 'prescriptions-delete',
            'warehouses-read', 'warehouses-create', 'warehouses-update', 'warehouses-delete',
            'traceability-read', 'recalls-create',
            'term-condition-read', 'privacy-policy-read',
            'audit-logs-view',
            'maintenance-read',
        ]);

        // Give limited permissions to Staff
        $staffRole->givePermissionTo([
            'dashboard-read',
            'products-view',
            'sales-view', 'sales-create',
            'purchases-view',
            'inventory-view',
            'reports-view',
            'users-read',
        ]);

        $this->command->info('Permissions seeded successfully');
    }
}
