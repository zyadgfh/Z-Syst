<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Tests\TestCase;

class UserSecurityTest extends TestCase
{
    public function test_business_id_cannot_be_mass_assigned_to_a_user(): void
    {
        $this->assertNotContains('business_id', (new User())->getFillable());
    }
}
