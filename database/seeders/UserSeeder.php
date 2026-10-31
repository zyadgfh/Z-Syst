<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds public.app_users profiles only.
 *
 * Path B: passwords and e-mail addresses live in Supabase auth.users. A seeder
 * cannot create them without the Admin API (service_role), and app_users.id is
 * a foreign key to auth.users.id — so this seeder is a no-op unless the caller
 * supplies existing auth user ids.
 *
 * Create the identities first:
 *   Supabase dashboard -> Authentication -> Users -> Add user
 *   or POST /auth/v1/admin/users with the service_role key.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
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
