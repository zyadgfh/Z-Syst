<?php

namespace Tests\Unit\Models;

use App\Models\Business;
use App\Models\BusinessCategory;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class EncryptedFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $category = BusinessCategory::factory()->create();
        $this->business = Business::factory()->create(['business_category_id' => $category->id]);
    }

    public function test_user_phone_is_encrypted_on_save(): void
    {
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'phone' => '0501234567',
        ]);

        // Raw DB value should be encrypted
        $rawPhone = \DB::table('users')->where('id', $user->id)->value('phone');
        $this->assertNotEquals('0501234567', $rawPhone);

        // Model accessor should return decrypted value
        $this->assertEquals('0501234567', $user->fresh()->phone);
    }

    public function test_party_phone_is_encrypted_on_save(): void
    {
        $party = Party::create([
            'business_id' => $this->business->id,
            'name' => 'Test Supplier',
            'type' => 'supplier',
            'phone' => '0559876543',
            'email' => 'supplier@test.com',
        ]);

        // Raw DB values should be encrypted
        $rawPhone = \DB::table('parties')->where('id', $party->id)->value('phone');
        $this->assertNotEquals('0559876543', $rawPhone);

        $rawEmail = \DB::table('parties')->where('id', $party->id)->value('email');
        $this->assertNotEquals('supplier@test.com', $rawEmail);

        // Model accessors should return decrypted values
        $fresh = $party->fresh();
        $this->assertEquals('0559876543', $fresh->phone);
        $this->assertEquals('supplier@test.com', $fresh->email);
    }

    public function test_encryption_prevents_double_encrypt(): void
    {
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'phone' => '0501112233',
        ]);

        // Update with same value — should not double encrypt
        $user->phone = '0501112233';
        $user->save();

        $this->assertEquals('0501112233', $user->fresh()->phone);
    }

    public function test_null_phone_not_encrypted(): void
    {
        $user = User::factory()->create([
            'business_id' => $this->business->id,
            'phone' => null,
        ]);

        $this->assertNull($user->fresh()->phone);
    }

    public function test_party_search_by_encrypted_phone(): void
    {
        // Create multiple parties
        Party::create([
            'business_id' => $this->business->id,
            'name' => 'Party A',
            'type' => 'customer',
            'phone' => '0501111111',
        ]);
        Party::create([
            'business_id' => $this->business->id,
            'name' => 'Party B',
            'type' => 'customer',
            'phone' => '0502222222',
        ]);

        // Search by loading all and filtering (encrypted fields can't use WHERE)
        $allParties = Party::where('business_id', $this->business->id)->get();
        $found = $allParties->firstWhere('phone', '0501111111');

        $this->assertNotNull($found);
        $this->assertEquals('Party A', $found->name);
    }
}
