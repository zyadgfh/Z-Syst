<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure fresh sqlite test DB file exists
        $testingDb = database_path('testing.sqlite');
        if (file_exists($testingDb)) {
            @unlink($testingDb);
        }
        if (! file_exists(dirname($testingDb))) {
            mkdir(dirname($testingDb), 0755, true);
        }
        // create empty sqlite file
        @file_put_contents($testingDb, '');

        // Create minimal tables required for auth tests to avoid running all migrations
        if (!\Schema::hasTable('companies')) {
            \Schema::create('companies', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->integer('max_branches')->nullable();
                $table->boolean('is_unlimited_branches')->default(false);
                $table->integer('default_branch_limit')->nullable();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('users')) {
            \Schema::create('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('email')->nullable()->unique();
                $table->string('name')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password')->nullable();
                $table->string('status')->default('inactive');
                $table->string('role')->default('user');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!\Schema::hasTable('personal_access_tokens')) {
            \Schema::create('personal_access_tokens', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('tokenable_type');
                $table->unsignedBigInteger('tokenable_id');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_user_can_login_via_legacy_route_and_receive_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'auth-test@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => 'auth-test@example.com',
            'password' => 'secret123',
            'device_name' => 'test-client',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'data' => ['token', 'abilities', 'user']]);
        $response->assertJsonPath('data.user.id', $user->id);
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_user_can_login_via_auth_route_and_receive_token(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'auth-route-test@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'auth-route-test@example.com',
            'password' => 'secret123',
            'device_name' => 'test-client',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['success', 'message', 'data' => ['token', 'abilities', 'user']]);
        $response->assertJsonPath('data.user.email', 'auth-route-test@example.com');
        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_user_can_logout_from_all_devices(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'logout-all@example.com',
            'password' => bcrypt('secret123'),
            'status' => 'active',
        ]);

        $user->createToken('device-one');
        $user->createToken('device-two');

        $token = $user->createToken('current-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout-all-devices');

        $response->assertOk();
        $response->assertJson(['success' => true, 'message' => 'Logged out from all devices successfully']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
