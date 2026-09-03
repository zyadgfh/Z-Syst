<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClerkWebhookController extends Controller
{
    /**
     * Handle incoming Clerk webhook events.
     *
     * Events handled:
     * - user.created  → Create a local user record
     * - user.updated  → Sync local user data
     * - user.deleted  → Remove local user record
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $eventType = $payload['type'] ?? null;

        if (!$eventType) {
            return response()->json(['error' => 'Missing event type'], 400);
        }

        Log::info("Clerk webhook received: {$eventType}", [
            'data_id' => $payload['data']['id'] ?? null,
        ]);

        try {
            match ($eventType) {
                'user.created' => $this->handleUserCreated($payload),
                'user.updated' => $this->handleUserUpdated($payload),
                'user.deleted' => $this->handleUserDeleted($payload),
                default => Log::info("Clerk webhook event ignored: {$eventType}"),
            };

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error("Clerk webhook error: {$e->getMessage()}", [
                'event_type' => $eventType,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Handle user.created event — create a local user synced from Clerk.
     */
    protected function handleUserCreated(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $clerkId = $data['id'] ?? null;

        if (!$clerkId) {
            Log::warning('Clerk user.created missing user ID');
            return;
        }

        // Extract email from Clerk's email_addresses array
        $email = $this->extractPrimaryEmail($data);
        $name = trim(
            ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')
        ) ?: ($email ? explode('@', $email)[0] : 'User');

        // Avoid duplicates
        $existing = User::where('clerk_id', $clerkId)->first();
        if ($existing) {
            Log::info("Clerk user already exists locally: {$clerkId}");
            return;
        }

        User::create([
            'clerk_id' => $clerkId,
            'name' => $name,
            'email' => $email,
            'role' => 'superadmin', // First Clerk user gets superadmin
            'status' => 'active',
            'email_verified_at' => isset($data['email_addresses'])
                ? now()
                : null,
        ]);

        Log::info("Clerk user created locally: {$clerkId} ({$email})");
    }

    /**
     * Handle user.updated event — sync local user data from Clerk.
     */
    protected function handleUserUpdated(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $clerkId = $data['id'] ?? null;

        if (!$clerkId) {
            Log::warning('Clerk user.updated missing user ID');
            return;
        }

        $user = User::where('clerk_id', $clerkId)->first();
        if (!$user) {
            Log::info("Clerk user.updated — no local user found for: {$clerkId}");
            return;
        }

        $email = $this->extractPrimaryEmail($data);
        $name = trim(
            ($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')
        ) ?: $user->name;

        $user->update([
            'name' => $name,
            'email' => $email ?? $user->email,
        ]);

        Log::info("Clerk user updated locally: {$clerkId}");
    }

    /**
     * Handle user.deleted event — soft-delete or remove local user.
     */
    protected function handleUserDeleted(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $clerkId = $data['id'] ?? null;

        if (!$clerkId) {
            Log::warning('Clerk user.deleted missing user ID');
            return;
        }

        $user = User::where('clerk_id', $clerkId)->first();
        if (!$user) {
            Log::info("Clerk user.deleted — no local user found for: {$clerkId}");
            return;
        }

        // Update status instead of hard delete to preserve referential integrity
        $user->update([
            'status' => 'clerk_deactivated',
            'clerk_id' => null,
        ]);

        Log::info("Clerk user deactivated locally: {$clerkId}");
    }

    /**
     * Extract the primary email from Clerk user data.
     */
    protected function extractPrimaryEmail(array $data): ?string
    {
        $emailAddresses = $data['email_addresses'] ?? [];
        $primaryEmailId = $data['primary_email_address_id'] ?? null;

        foreach ($emailAddresses as $email) {
            if (($email['id'] ?? null) === $primaryEmailId) {
                return $email['email_address'] ?? null;
            }
        }

        // Fallback to first email
        return $emailAddresses[0]['email_address'] ?? null;
    }
}
