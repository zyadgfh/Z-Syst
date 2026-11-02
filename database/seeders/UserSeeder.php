<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds user profiles.
 *
 * Path B (default): passwords and emails live in Supabase auth.users, so this
 * seeder is a no-op unless SUPABASE_SEED_AUTH_USER_IDS supplies existing
 * auth.users ids (app_users.id is a foreign key to auth.users.id).
 *
 * Local dev / CI fallback (SUPABASE_FALLBACK_ELOQUENT=true): seeds classic
 * accounts with emails + bcrypt hashes stored locally (see the
 * add_local_auth_columns_to_app_users migration).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (config('supabase.fallback_eloquent')) {
            $locals = [
                ['email' => 'superadmin@z-syst.com', 'password' => 'SuperAdmin123!', 'role' => 'superadmin', 'name' => 'Super Admin'],
                ['email' => 'admin@test.com', 'password' => 'Admin123!', 'role' => 'admin', 'name' => 'Admin'],
                ['email' => 'staff@test.com', 'password' => 'Staff123!', 'role' => 'staff', 'name' => 'Staff'],
                ['email' => 'staff-b@test.com', 'password' => 'StaffB123!', 'role' => 'staff', 'name' => 'Staff B'],
            ];
            foreach ($locals as $u) {
                // app_users.id is a uuid (Path B: references auth.users.id);
                // in fallback mode we generate one locally.
                User::query()->updateOrCreate(
                    ['email' => $u['email']],
                    [
                        'id' => (string) \Illuminate\Support\Str::uuid(),
                        'name' => $u['name'],
                        'role' => $u['role'],
                        'password' => $u['password'],
                        'status' => 'active',
                        'lang' => 'en',
                    ]
                );
            }
            $this->command?->info('Local fallback users seeded.');

            return;
        }

        $ids = (array) config('supabase.seed_auth_user_ids', []);

        if ($ids === []) {
            $this->command?->warn(
                'UserSeeder skipped: no app_users row can exist without a matching auth.users id. '.
                'Create users in Supabase Authentication first.'
            );

            return;
        }

        foreach ($ids as $index => $id) {
            User::query()->updateOrCreate(
                ['id' => $id],
                [
                    'name' => 'Seeded User '.($index + 1),
                    'role' => $index === 0 ? 'superadmin' : 'staff',
                    'status' => 'active',
                ]
            );
        }

        $this->command?->info('User profiles seeded.');
    }
}
