<?php

namespace Tests\Feature;

use App\Mail\BusinessMail;
use App\Models\Party;
use App\Models\User;
use App\Notifications\SendNotification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(NotificationService::class);
    }

    public function test_notify_users_sends_database_notifications(): void
    {
        Notification::fake();

        $users = User::factory()->count(2)->create();

        $sent = $this->service->notifyUsers($users, 'Test Subject', 'Test message', ['url' => '/admin']);

        $this->assertEquals(2, $sent);
        Notification::assertSentTo($users[0], SendNotification::class);
        Notification::assertSentTo($users[1], SendNotification::class);
    }

    public function test_notify_users_skips_null_entries(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $sent = $this->service->notifyUsers([$user, null], 'Subject', 'Message');

        $this->assertEquals(1, $sent);
    }

    public function test_email_party_sends_business_mail(): void
    {
        Mail::fake();

        $result = $this->service->emailParty('supplier@example.com', 'PO sent', ['Line one']);

        $this->assertTrue($result);

        Mail::assertSent(BusinessMail::class, function (BusinessMail $mail) {
            return $mail->subjectLine === 'PO sent'
                && $mail->hasTo('supplier@example.com');
        });
    }

    public function test_email_party_returns_false_without_address(): void
    {
        Mail::fake();

        $this->assertFalse($this->service->emailParty(null, 'No address'));

        Mail::assertNothingSent();
    }

    public function test_email_users_deduplicates_recipients(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $sent = $this->service->emailUsers([$user, $user], 'Subject', ['Body']);

        $this->assertEquals(1, $sent);

        Mail::assertSent(BusinessMail::class, function (BusinessMail $mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_email_users_returns_zero_without_emails(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => null]);

        $this->assertEquals(0, $this->service->emailUsers([$user], 'Subject', ['Body']));

        Mail::assertNothingSent();
    }

    public function test_sms_counts_unique_numbers(): void
    {
        $count = $this->service->sms(['+201000000001', '+201000000001', null, ''], 'Hello');

        $this->assertEquals(1, $count);
    }

    public function test_sms_returns_zero_without_numbers(): void
    {
        $this->assertEquals(0, $this->service->sms([], 'Hello'));
    }

    public function test_notify_supplier_sends_email_and_sms(): void
    {
        Mail::fake();

        $supplier = Party::factory()->create([
            'type' => 'supplier',
            'email' => 'supplier@example.com',
            'phone' => '+201000000002',
        ]);

        $result = $this->service->notifySupplier($supplier, 'Invoice approved', ['Details here']);

        $this->assertTrue($result['email']);
        $this->assertEquals(1, $result['sms']);

        Mail::assertSent(BusinessMail::class, function (BusinessMail $mail) {
            return $mail->subjectLine === 'Invoice approved'
                && $mail->hasTo('supplier@example.com');
        });
    }

    public function test_notify_supplier_without_supplier_returns_zero_results(): void
    {
        Mail::fake();

        $result = $this->service->notifySupplier(null, 'Orphan document');

        $this->assertFalse($result['email']);
        $this->assertEquals(0, $result['sms']);

        Mail::assertNothingSent();
    }
}
