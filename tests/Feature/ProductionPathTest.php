<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Supabase\SupabaseAuthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Production-path (Path A) contract tests.
 *
 * The main suite runs with SUPABASE_FALLBACK_ELOQUENT=true (local identities
 * on app_users). These tests flip config('supabase.fallback_eloquent') to
 * false — the production posture — and pin the behaviour that posture
 * requires: registration closed, identity mutators locked, password
 * verification delegated to GoTrue, and no Postgres RLS context on
 * non-pgsql drivers. CI re-runs this class with the env var unset so the
 * production contract is proven by the environment, not just an override.
 */
class ProductionPathTest extends TestCase
{
    use RefreshDatabase;

    /**
     * In production mode the email accessor reads auth.users through
     * AuthUser::emailFor (request-cached). sqlite has no auth schema, so the
     * tests pre-seed that request cache with the locally known address —
     * the GoTrue HTTP layer under test stays fully faked/real.
     */
    protected function seedEmailCache(User $user): void
    {
        $cache = new \ReflectionProperty(User::class, 'emailCache');
        $cache->setAccessible(true);
        $cache->setValue(null, [$user->id => $user->getRawOriginal('email')]);
    }

    protected function productionMode(): void
    {
        Config::set('supabase.fallback_eloquent', false);
        Config::set('supabase.url', 'https://vendor.example.supabase.co');
        Config::set('supabase.anon_key', 'test-anon-key');
        Config::set('supabase.service_role_key', 'test-service-role-key');
    }

    public function test_self_service_registration_is_closed_in_production_mode(): void
    {
        $this->productionMode();

        $this->postJson('/register', [
            'name' => 'Prod User',
            'email' => 'prod@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Self-service registration is disabled. Contact support to activate your pharmacy.']);

        $this->assertDatabaseMissing('app_users', ['email' => 'prod@example.com']);
    }

    public function test_identity_mutators_are_locked_in_production_mode(): void
    {
        $this->productionMode();

        $this->expectException(\RuntimeException::class);
        User::factory()->create(['email' => 'locked@example.com']);
    }

    public function test_change_password_verifies_through_gotrue_in_production_mode(): void
    {
        // Seed the identity locally (factories require fallback), then flip
        // to the production posture.
        $user = User::factory()->create([
            'email' => 'gotrue@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $this->productionMode();
        $this->seedEmailCache($user);

        Http::fake([
            '*/auth/v1/*' => Http::response(['access_token' => 'at', 'user' => ['id' => $user->id]], 200),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/change-password', [
                'current_password' => 'password',
                'password' => 'newpassword1',
                'password_confirmation' => 'newpassword1',
            ])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'password.changed',
        ]);
    }

    public function test_change_password_rejects_bad_credentials_via_gotrue(): void
    {
        $user = User::factory()->create([
            'email' => 'gotrue-bad@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
        $this->productionMode();
        $this->seedEmailCache($user);

        Http::fake([
            '*/auth/v1/*' => Http::response(['msg' => 'Invalid login credentials'], 400),
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'newpassword1',
                'password_confirmation' => 'newpassword1',
            ])
            ->assertStatus(422);
    }

    public function test_supabase_user_provider_requires_a_session_in_production_mode(): void
    {
        // Factories write identities locally; flip to production afterwards.
        $user = User::factory()->create();
        $this->productionMode();

        $provider = new \App\Auth\SupabaseUserProvider(
            app(SupabaseAuthClient::class),
            User::class,
        );

        // No retrieveByCredentials() ran (no session recorded) — validation
        // must fail closed without touching the local hash.
        $this->assertFalse($provider->validateCredentials($user, [
            'email' => $user->getRawOriginal('email'),
            'password' => 'password',
        ]));
    }

    public function test_set_supabase_context_is_a_noop_on_non_pgsql_drivers(): void
    {
        $this->productionMode();

        $middleware = new \App\Http\Middleware\SetSupabaseContext;
        $result = $middleware->handle(request(), fn () => response('next-called'));

        $this->assertSame('next-called', $result->getContent());
    }

    public function test_auth_user_email_lookup_reads_app_users_in_fallback_mode(): void
    {
        $user = User::factory()->create(['email' => 'Lookup@Example.com']);
        Config::set('supabase.fallback_eloquent', true);

        $this->assertNotEmpty(\App\Models\AuthUser::idsForEmail('lookup@example.com'));
        $this->assertSame($user->id, \App\Models\AuthUser::idsForEmail('lookup@example.com')[0]);
    }
}
