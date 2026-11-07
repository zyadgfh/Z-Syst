<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Canonical permission vocabulary: <resource>-read/create/update/delete —
     * exactly the names the `permission:` middleware declares in
     * app/Http/Controllers/Admin/*.php, plus the POS override ability.
     * MUST stay in sync with config('supabase.permissions') — the runtime
     * authority (HasSupabaseRoles reads that map; spatie tables mirror it).
     */
    public function run(): void
    {
        // Reset cached roles/permissions so re-seeds stay consistent.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $resourcesCrud = [
            'users', 'warehouses', 'stock-transfers', 'traceability',
            'prescriptions', 'receipts',
            'insurance-companies', 'insurance-policies', 'insurance-claims',
            'loyalty', 'banners',
            // platform-level management (Super Admin only in practice)
            'roles', 'plans', 'business', 'currencies',
        ];
        $permissions = [];
        foreach ($resourcesCrud as $resource) {
            foreach (['read', 'create', 'update', 'delete'] as $action) {
                $permissions[] = "$resource-$action";
            }
        }
        $permissions = array_merge($permissions, [
            'dashboard-read', 'reports-read', 'notifications-read',
            'settings-read', 'settings-update',
            'audit-logs-read', 'audit-logs-delete',
            'permissions-read', 'permissions-create',
            // POS overrides (H-01): allow trusted roles to sell off list price
            'sale.price-override',
        ]);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff']);

        // Super Admin: everything.
        $superAdminRole->givePermissionTo(Permission::all());
        $superAdminRole->givePermissionTo('sale.price-override');

        // Admin: business CRUD without platform management, user deletion,
        // audit-log deletion, or price overrides — mirrors config('supabase.permissions.admin').
        $adminRole->givePermissionTo([
            'dashboard-read', 'reports-read', 'notifications-read',
            'settings-read', 'settings-update', 'audit-logs-read',
            'users-read', 'users-create', 'users-update',
            'warehouses-read', 'warehouses-create', 'warehouses-update', 'warehouses-delete',
            'stock-transfers-read', 'stock-transfers-create', 'stock-transfers-update', 'stock-transfers-delete',
            'traceability-read', 'traceability-create', 'traceability-update', 'traceability-delete',
            'prescriptions-read', 'prescriptions-create', 'prescriptions-update', 'prescriptions-delete',
            'receipts-read', 'receipts-create', 'receipts-update', 'receipts-delete',
            'insurance-companies-read', 'insurance-companies-create', 'insurance-companies-update', 'insurance-companies-delete',
            'insurance-policies-read', 'insurance-policies-create', 'insurance-policies-update', 'insurance-policies-delete',
            'insurance-claims-read', 'insurance-claims-create', 'insurance-claims-update', 'insurance-claims-delete',
            'loyalty-read', 'loyalty-create', 'loyalty-update', 'loyalty-delete',
            'banners-read', 'banners-create', 'banners-update', 'banners-delete',
        ]);

        // Staff: read-only + file insurance claims + raise prescriptions.
        $staffRole->givePermissionTo([
            'dashboard-read', 'reports-read', 'notifications-read',
            'warehouses-read', 'stock-transfers-read', 'traceability-read',
            'prescriptions-read', 'prescriptions-create',
            'receipts-read', 'loyalty-read',
            'insurance-claims-read', 'insurance-claims-create',
        ]);

        $this->command?->info('Permissions seeded (canonical CRUD vocabulary).');
    }
}
