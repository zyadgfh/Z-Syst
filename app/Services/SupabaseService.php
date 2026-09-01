<?php

namespace App\Services;

use App\Exceptions\Supabase\SupabaseConnectionException;
use App\Exceptions\Supabase\SupabaseQueryException;
use App\Exceptions\Supabase\SupabaseStorageException;
use App\Logging\StructuredLogger;
use Illuminate\Support\Facades\Log;
use Supabase\SupabaseClient;

class SupabaseService
{
    protected ?SupabaseClient $client;
    protected ?string $url;
    protected ?string $key;
    protected ?string $serviceRoleKey;

    public function __construct()
    {
        $this->url = config('supabase.url');
        $this->key = config('supabase.key');
        $this->serviceRoleKey = config('supabase.service_role_key');

        // Only initialize if Supabase is properly configured
        if ($this->url && $this->serviceRoleKey && class_exists('Supabase\SupabaseClient')) {
            $this->client = new SupabaseClient(
                $this->url,
                $this->serviceRoleKey // Use service role key for admin operations
            );
        }
    }

    /**
     * Check if Supabase is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->client !== null && $this->url !== null && $this->serviceRoleKey !== null;
    }

    /**
     * Get the Supabase client instance.
     */
    public function getClient(): ?SupabaseClient
    {
        return $this->client ?? null;
    }

    /**
     * Get the Supabase URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the Supabase public key (for client-side operations).
     */
    public function getPublicKey(): string
    {
        return $this->key;
    }

    /**
     * Insert data into a Supabase table.
     */
    public function insert(string $table, array $data): array
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $response = $this->client->from($table)->insert($data)->execute();
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('insert', ['table' => $table, 'data' => $data], $e);
            throw new SupabaseQueryException(
                'Failed to insert data',
                ['table' => $table, 'data' => $data],
                $e
            );
        }
    }

    /**
     * Update data in a Supabase table.
     */
    public function update(string $table, string $id, array $data): array
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $response = $this->client->from($table)->update($data)->eq('id', $id)->execute();
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('update', ['table' => $table, 'id' => $id, 'data' => $data], $e);
            throw new SupabaseQueryException(
                'Failed to update data',
                ['table' => $table, 'id' => $id, 'data' => $data],
                $e
            );
        }
    }

    /**
     * Delete data from a Supabase table.
     */
    public function delete(string $table, string $id): bool
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $this->client->from($table)->delete()->eq('id', $id)->execute();
            return true;
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('delete', ['table' => $table, 'id' => $id], $e);
            throw new SupabaseQueryException(
                'Failed to delete data',
                ['table' => $table, 'id' => $id],
                $e
            );
        }
    }

    /**
     * Select data from a Supabase table.
     */
    public function select(string $table, array $filters = [], array $columns = ['*']): array
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $query = $this->client->from($table)->select($columns);

            foreach ($filters as $column => $value) {
                $query = $query->eq($column, $value);
            }

            $response = $query->execute();
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('select', ['table' => $table, 'filters' => $filters], $e);
            throw new SupabaseQueryException(
                'Failed to select data',
                ['table' => $table, 'filters' => $filters],
                $e
            );
        }
    }

    /**
     * Real-time subscription to a table.
     */
    public function subscribe(string $table, callable $callback): void
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $this->client->from($table)->on('*', function ($payload) use ($callback) {
                $callback($payload);
            })->subscribe();
        } catch (\Exception $e) {
            Log::error('Supabase subscription error', [
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to subscribe: ' . $e->getMessage());
        }
    }

    /**
     * Upload file to Supabase Storage.
     */
    public function uploadFile(string $bucket, string $path, $file): string
    {
        if (!$this->isConfigured()) {
            throw new SupabaseConnectionException('Supabase is not properly configured');
        }

        try {
            $response = $this->client->storage->from($bucket)->upload($path, $file);
            return $response['path'] ?? $path;
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('upload_file', ['bucket' => $bucket, 'path' => $path], $e);
            throw new SupabaseStorageException(
                'Failed to upload file',
                ['bucket' => $bucket, 'path' => $path],
                $e
            );
        }
    }

    /**
     * Get public URL for a file in Supabase Storage.
     */
    public function getPublicUrl(string $bucket, string $path): string
    {
        try {
            return $this->client->storage->from($bucket)->getPublicUrl($path);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('get_public_url', ['bucket' => $bucket, 'path' => $path], $e);
            throw new SupabaseStorageException(
                'Failed to get public URL',
                ['bucket' => $bucket, 'path' => $path],
                $e
            );
        }
    }

    /**
     * Delete file from Supabase Storage.
     */
    public function deleteFile(string $bucket, array $paths): bool
    {
        try {
            $this->client->storage->from($bucket)->remove($paths);
            return true;
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('delete_file', ['bucket' => $bucket, 'paths' => $paths], $e);
            throw new SupabaseStorageException(
                'Failed to delete file',
                ['bucket' => $bucket, 'paths' => $paths],
                $e
            );
        }
    }

    /**
     * Execute a raw SQL query on Supabase.
     */
    public function executeRaw(string $sql): array
    {
        try {
            $response = $this->client->rpc('exec_sql', ['sql' => $sql])->execute();
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('execute_raw', ['sql' => $sql], $e);
            throw new SupabaseQueryException(
                'Failed to execute SQL',
                ['sql' => $sql],
                $e
            );
        }
    }

    /**
     * Call a Supabase function.
     */
    public function callFunction(string $functionName, array $params = []): array
    {
        try {
            $response = $this->client->rpc($functionName, $params)->execute();
            return $this->parseResponse($response);
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('call_function', ['function' => $functionName, 'params' => $params], $e);
            throw new SupabaseQueryException(
                'Failed to call function',
                ['function' => $functionName, 'params' => $params],
                $e
            );
        }
    }

    /**
     * Parse Supabase response.
     */
    protected function parseResponse($response): array
    {
        if (is_array($response)) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'toArray')) {
            return $response->toArray();
        }

        return [];
    }

    /**
     * Check if Supabase connection is working.
     */
    public function healthCheck(): bool
    {
        try {
            $response = $this->client->from('_test_connection_')->select()->limit(1)->execute();
            return true;
        } catch (\Exception $e) {
            StructuredLogger::logSupabaseError('health_check', [], $e);
            throw new SupabaseConnectionException(
                'Supabase health check failed',
                [],
                $e
            );
        }
    }
}