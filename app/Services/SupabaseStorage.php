<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SupabaseStorage — minimal Storage REST client (service-role only).
 *
 * Used by:
 *  - BackupController: ship scheduled DB backups off-box (bucket: backups)
 *  - HasUploader migration path: cloud-side user uploads (bucket: uploads)
 *
 * Credentials come from config/supabase.php envs; the client disables
 * itself (returns false) when the URL/key are absent so local/preview
 * environments keep working with the filesystem fallback.
 */
class SupabaseStorage
{
    public function enabled(): bool
    {
        return (bool) (config('supabase.url') && config('supabase.service_role_key'));
    }

    /**
     * Upload bytes to a bucket preserving the object path.
     *
     * @param  string  $bucket  e.g. 'backups' or 'uploads'
     * @param  string  $path    object path inside the bucket (dirs allowed)
     * @param  string  $contents
     * @param  string|null  $mimeType
     * @return array{ok: bool, path: string, error?: string}
     */
    public function upload(string $bucket, string $path, string $contents, ?string $mimeType = null): array
    {
        if (! $this->enabled()) {
            return ['ok' => false, 'path' => $path, 'error' => 'supabase storage not configured'];
        }

        $url = rtrim(config('supabase.url'), '/') . '/storage/v1/object/' . rawurlencode($bucket) . '/' . implode('/', array_map('rawurlencode', explode('/', $path)));

        try {
            $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('supabase.service_role_key'),
                    'x-upsert' => 'true',
                ])
                ->withBody($contents, $mimeType ?? 'application/octet-stream')
                ->timeout(config('supabase.http.timeout', 10))
                ->post($url);

            if ($response->successful()) {
                return ['ok' => true, 'path' => $path];
            }

            Log::warning('SupabaseStorage upload failed', ['path' => $path, 'status' => $response->status()]);

            return ['ok' => false, 'path' => $path, 'error' => 'HTTP ' . $response->status()];
        } catch (ConnectionException $e) {
            Log::warning('SupabaseStorage unreachable', ['path' => $path, 'error' => $e->getMessage()]);

            return ['ok' => false, 'path' => $path, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a short-lived signed URL for a private object (share/read link).
     */
    public function signedUrl(string $bucket, string $path, int $expiresInSeconds = 600): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $url = rtrim(config('supabase.url'), '/') . '/storage/v1/object/sign/' . rawurlencode($bucket) . '/' . implode('/', array_map('rawurlencode', explode('/', $path)));

        $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('supabase.service_role_key'),
            ])
            ->timeout(config('supabase.http.timeout', 10))
            ->post($url, ['expiresIn' => $expiresInSeconds]);

        if (! $response->successful()) {
            return null;
        }

        $signed = $response->json('signedURL') ?? ($response->json('signedUrl') ?? null);

        return $signed ? rtrim(config('supabase.url'), '/') . '/storage/v1' . $signed : null;
    }
}
