<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\Auth\ZSystForgotPasswordController;
use App\Services\FirebasePushService;
use App\Services\Integration\MessagingService;
use App\Services\SmsService;
use App\Models\DoctorAttentionAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DisabledDeliveryTest extends TestCase
{
    public function test_sms_gateway_is_disabled_even_when_configured(): void
    {
        config(['sms.driver' => 'twilio', 'sms.enabled' => true]);
        Http::fake();

        $this->assertFalse(app(SmsService::class)->send('+201234567890', 'test'));
        Http::assertNothingSent();
    }

    public function test_sms_integration_reports_disabled_instead_of_fake_success(): void
    {
        $result = app(MessagingService::class)->sendSms('+201234567890', 'test', 1);
        $this->assertFalse($result['success']);
        $this->assertSame('disabled', $result['status']);
    }

    public function test_old_email_reset_endpoints_are_disabled(): void
    {
        $controller = app(ZSystForgotPasswordController::class);
        foreach (['sendResetCode', 'verifyResetCode', 'resetPassword'] as $method) {
            $this->assertSame(503, $controller->$method(new Request())->getStatusCode());
        }
    }

    public function test_attention_push_without_recipients_does_not_send(): void
    {
        Http::fake();
        $this->assertSame(0, app(FirebasePushService::class)->sendDoctorAttentionAlert(new DoctorAttentionAlert(), []));
        Http::assertNothingSent();
    }
}
