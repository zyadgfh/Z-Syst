<?php

namespace Tests\Unit;

use App\Models\Prescription;
use Tests\TestCase;

class PrescriptionModelTest extends TestCase
{
    public function test_prescription_is_usable_only_when_approved_and_not_expired(): void
    {
        $prescription = new Prescription([
            'review_status' => 'approved',
            'status' => 'pending',
            'expires_at' => now()->addDay()->toDateString(),
        ]);

        $this->assertTrue($prescription->canBeUsed());
    }

    public function test_prescription_is_not_usable_when_pending_or_expired(): void
    {
        $pendingPrescription = new Prescription([
            'review_status' => 'pending',
            'status' => 'pending',
            'expires_at' => now()->addDay()->toDateString(),
        ]);

        $expiredPrescription = new Prescription([
            'review_status' => 'approved',
            'status' => 'pending',
            'expires_at' => now()->subDay()->toDateString(),
        ]);

        $this->assertFalse($pendingPrescription->canBeUsed());
        $this->assertFalse($expiredPrescription->canBeUsed());
    }
}
