<?php

namespace App\Services;

use App\Models\InventoryAlert;
use App\Models\PushToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebasePushService
{
    /**
     * Send a push notification via Firebase Cloud Messaging (HTTP v1 API).
     *
     * Uses the Google OAuth2 access token approach. The service account JSON
     * credentials are expected in config('services.firebase').
     */
    public function sendInventoryAlert(InventoryAlert $alert): int
    {
        $businessId = $alert->business_id;
        if (!$businessId) return 0;

        $tokens = PushToken::forBusiness($businessId)->pluck('token')->toArray();
        if (empty($tokens)) return 0;

        $title = match ($alert->severity) {
            'critical' => '🔴 تنبيه عاجل — مخزون',
            'warning'  => '⚠️ تنبيه مخزون',
            default    => 'ℹ️ إشعار مخزون',
        };

        $body = $alert->message;

        $payload = [
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
            'data' => [
                'type'     => 'inventory_alert',
                'alert_id' => (string) $alert->id,
                'severity' => $alert->severity,
                'url'      => url("/admin/inventory-alerts"),
            ],
            'webpush' => [
                'fcm_options' => [
                    'link' => url("/admin/inventory-alerts"),
                ],
                'headers' => [
                    'TTL'       => '3600',
                    'Urgency'   => $alert->severity === 'critical' ? 'high' : 'normal',
                ],
            ],
        ];

        return $this->sendToMultipleTokens($tokens, $payload);
    }

    /**
     * Send to multiple FCM tokens using the HTTP v1 API.
     * Falls back to legacy API if no service account is configured.
     */
    protected function sendToMultipleTokens(array $tokens, array $payload): int
    {
        $projectId = config('services.firebase.project_id', env('VITE_FIREBASE_PROJECT_ID'));
        $serverKey = config('services.firebase.server_key', env('FIREBASE_SERVER_KEY'));
        $serviceAccount = config('services.firebase.credentials');

        $sent = 0;

        // If a service account JSON is configured, use HTTP v1 API
        if ($serviceAccount && file_exists($serviceAccount)) {
            $accessToken = $this->getAccessToken($serviceAccount);
            if (!$accessToken) {
                Log::warning('Firebase: Could not obtain access token');
                return 0;
            }

            foreach ($tokens as $token) {
                try {
                    $response = Http::withToken($accessToken)
                        ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                            'message' => array_merge($payload, [
                                'token' => $token,
                            ]),
                        ]);

                    if ($response->successful()) {
                        $sent++;
                    } else {
                        // Token might be invalid — deactivate it
                        if (in_array($response->status(), [404, 400])) {
                            PushToken::deactivate($token);
                        }
                        Log::warning("FCM push failed for token: {$response->status()}");
                    }
                } catch (\Exception $e) {
                    Log::error("FCM push error: {$e->getMessage()}");
                }
            }
        }
        // Fallback: use legacy FCM server key API
        elseif ($serverKey) {
            foreach ($tokens as $token) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => "key={$serverKey}",
                        'Content-Type'  => 'application/json',
                    ])->post('https://fcm.googleapis.com/fcm/send', [
                        'to'   => $token,
                        'notification' => $payload['notification'] ?? [],
                        'data'         => $payload['data'] ?? [],
                        'webpush'      => $payload['webpush'] ?? [],
                    ]);

                    if ($response->successful()) {
                        $sent++;
                    } else {
                        if (in_array($response->status(), [404, 400])) {
                            PushToken::deactivate($token);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("FCM legacy push error: {$e->getMessage()}");
                }
            }
        } else {
            Log::info('Firebase: No server key or service account configured — push notifications disabled');
        }

        return $sent;
    }

    /**
     * Obtain an OAuth2 access token from a service account JSON file.
     */
    protected function getAccessToken(string $serviceAccountPath): ?string
    {
        try {
            $credentials = json_decode(file_get_contents($serviceAccountPath), true);
            if (!$credentials) return null;

            $now = time();
            $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $jwtClaimSet = base64_encode(json_encode([
                'iss'   => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));

            $dataToSign = "{$jwtHeader}.{$jwtClaimSet}";
            $signature = '';
            openssl_sign($dataToSign, $signature, $credentials['private_key'], 'SHA256');
            $jwt = "{$dataToSign}." . base64_encode($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Firebase access token error: {$e->getMessage()}");
            return null;
        }
    }
}
