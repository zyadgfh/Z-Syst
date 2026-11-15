<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\NotificationController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class NotificationOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $user, string $url): string
    {
        $id = (string) Str::uuid();
        $user->notifications()->create([
            'id' => $id,
            'type' => 'test',
            'data' => ['url' => $url],
        ]);
        return $id;
    }

    public function test_user_cannot_open_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $id = $this->notification($owner, '/admin');
        $this->actingAs($other);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(NotificationController::class)->mtView($id);
    }

    public function test_protocol_relative_redirect_is_blocked(): void
    {
        $user = User::factory()->create();
        $id = $this->notification($user, '//evil.example/path');
        $this->actingAs($user);

        $response = app(NotificationController::class)->mtView($id);
        $this->assertSame(url('/'), $response->getTargetUrl());
    }
}
