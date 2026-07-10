<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
        /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rolesStructure = [
            'Super Admin' => [
                'dashboard' => 'r',
                'users' => 'r,c,u,d',
                'banners' => 'r,c,u,d',
                'business' => 'r,c,u,d',
                'business-categories' => 'r,c,u,d',
                'plans' => 'r,c,u,d',
                'subscription-reports' => 'r',
                'blogs' => 'r,c,u,d',
                'testimonials' => 'r,c,u,d',
                'interfaces' => 'r,c,u,d',
                'features' => 'r,c,u,d',
                'term-condition' => 'r,u',
                'privacy-policy' => 'r,u',
                'messages' => 'r,c,u,d',
                'manual-payment-reports' => 'r',
                'active-store-reports' => 'r',
                'expired-store-reports' => 'r',

                // settings
                'sms-settings' => 'r,u',
                'gateways' => 'r,u',
                'currencies' => 'r,c,u,d',
                'settings' => 'r,u',
                'web-settings' => 'r,u',
                'roles' => 'r,c,u,d',
                'permissions' => 'r,c',
                'notifications' => 'r,u',
            ],

            'Admin' => [
                'dashboard' => 'r',
                'users' => 'r,c,u,d',
                'banners' => 'r,c,u,d',
                'business' => 'r,c,u,d',
                'business-categories' => 'r,c,u,d',
                'plans' => 'r,c,u,d',
                'subscription-reports' => 'r',

                // settings
                'sms-settings' => 'r,u',
                'addons' => 'r,c',
                'gateways' => 'r,u',
                'currencies' => 'r,c,u,d',
                'notifications' => 'r,u',
            ],

            'Manager' => [
                'dashboard' => 'r',
                'users' => 'r,c,u,d',
                'banners' => 'r,c,u,d',
                'business' => 'r,c,u,d',
                'business-categories' => 'r,c,u,d',
                'plans' => 'r,c,u,d',
                'subscription-reports' => 'r',
            ],
        ];

        foreach ($rolesStructure as $key => $modules) {
            // Create a new role
            $role = Role::firstOrCreate([
                'name' => str($key)->remove(' ')->lower(),
                'guard_name' => 'web'
            ]);
            $permissions = [];

            $this->command->info('Creating Role '. strtoupper($key));

            // Reading role permission modules
            foreach ($modules as $module => $value) {

                foreach (explode(',', $value) as $perm) {

                    $permissionValue = $this->permissionMap()->get($perm);

                    $permissions[] = Permission::firstOrCreate([
                        'name' => $module . '-' . $permissionValue,
                        'guard_name' => 'web'
                    ])->id;

                    $this->command->info('Creating Permission to '.$permissionValue.' for '. $module);
                }
            }

            // Attach all permissions to the role
            $role->permissions()->sync($permissions);

            $this->command->info("Creating '{$key}' user");
            // Create default user for each role
            $user = User::create([
                'role' => str($key)->remove(' ')->lower(),
                'name' => ucwords(str_replace('_', ' ', $key)),
                'password' => bcrypt(str($key)->remove(' ')->lower()),
                'email' => str($key)->remove(' ')->lower().'@acnoo.com',
                'image' => 'assets/images/profile/'.str($key)->remove(' ')->lower().'.svg',
            ]);

            $user->assignRole($role);
        }
    }

    private function permissionMap() {
        return collect([
            'c' => 'create',
            'r' => 'read',
            'u' => 'update',
            'd' => 'delete',
        ]);
    }
}
