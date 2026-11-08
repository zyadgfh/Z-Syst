<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * POS offline sync: the PWA queues completed sales in localStorage while
 * offline and replays them here. Idempotency: each queued sale carries a
 * client_uuid; replays of an already-synced sale return the original result
 * instead of double-selling (critical for drawer/cash truth).
 */
class OfflineSyncService
{
    /**
     * @param array $sales [{client_uuid, products[], paidAmount, dueAmount, isPaid, paymentType, saleDate}]
     * @return array {accepted: [], duplicates: [], failed: []}
     */
    public function sync(array $sales, $user, ?Device $device): array
    {
        $result = ['accepted' => [], 'duplicates' => [], 'failed' => []];

        foreach (array_slice($sales, 0, 100) as $entry) {
            $uuid = (string) ($entry['client_uuid'] ?? '');
            if (!$uuid || !collect($entry['products'] ?? [])->count()) {
                $result['failed'][] = ['client_uuid' => $uuid, 'reason' => 'invalid payload'];
                continue;
            }

            $existing = Sale::where('client_uuid', $uuid)->first();
            if ($existing) {
                $result['duplicates'][] = ['client_uuid' => $uuid, 'sale_id' => $existing->id];
                continue;
            }

            try {
                $payload = [
                    'products' => collect($entry['products'])->map(fn ($p) => $p + ['lossProfit' => 0])->all(),
                    'saleDate' => $entry['saleDate'] ?? now()->toDateString(),
                    'paidAmount' => (float) ($entry['paidAmount'] ?? 0),
                    'dueAmount' => (float) ($entry['dueAmount'] ?? 0),
                    'isPaid' => (bool) ($entry['isPaid'] ?? true),
                    'paymentType' => $entry['paymentType'] ?? 'cash',
                    'meta' => ['offline_synced_at' => now()->toIso8601String(), 'device_uuid' => $device?->uuid],
                ];

                $apiRequest = Request::create('/api/v1/sales', 'POST', $payload);
                $apiRequest->setUserResolver(fn () => $user);
                if ($user->session) {
                    $apiRequest->setLaravelSession($user->session);
                }
                $apiRequest->headers->set('Accept', 'application/json');
                $apiRequest->headers->set('X-Requested-With', 'XMLHttpRequest');

                // The sale controller resolves the tenant via auth() — run the
                // internal call under the syncing user's identity, then restore.
                $previousUser = auth()->user();
                auth()->setUser($user);
                try {
                    $response = app(\App\Http\Controllers\Api\ZSystSaleController::class)->store($apiRequest);
                } finally {
                    if ($previousUser) {
                        auth()->setUser($previousUser);
                    }
                }
                if ($response->getStatusCode() >= 300) {
                    $result['failed'][] = ['client_uuid' => $uuid, 'reason' => mb_substr($response->getContent(), 0, 200)];
                    continue;
                }

                $body = json_decode($response->getContent(), true);
                $sale = Sale::find($body['data']['id'] ?? null);
                $sale?->update(['client_uuid' => $uuid]);
                $result['accepted'][] = ['client_uuid' => $uuid, 'sale_id' => $sale?->id];
            } catch (\Throwable $e) {
                $result['failed'][] = ['client_uuid' => $uuid, 'reason' => mb_substr($e->getMessage(), 0, 200)];
            }
        }

        return $result;
    }
}
