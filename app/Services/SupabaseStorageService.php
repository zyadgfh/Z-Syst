<?php

namespace App\Services;

use App\Exceptions\Supabase\SupabaseStorageException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Supabase\Storage\StorageClient;

class SupabaseStorageService
{
    protected ?StorageClient $storageClient;
    protected string $bucket;
    protected ?string $cdnUrl;

    public function __construct()
    {
        $this->bucket = config('supabase.storage.bucket', 'z-syst-uploads');
        $this->cdnUrl = config('supabase.storage.cdn_url');

        // Only initialize if Supabase is properly configured
        if (config('supabase.url') && config('supabase.service_role_key') && class_exists('Supabase\Storage\StorageClient')) {
            $this->storageClient = new StorageClient(
                config('supabase.url'),
                config('supabase.service_role_key')
            );
        }
    }

    /**
     * Upload file to Supabase Storage.
     */
    public function upload($file, string $path, array $options = []): string
    {
        try {
            $fileContent = file_get_contents($file);
            $fileSize = filesize($file);
            $mimeType = mime_content_type($file);

            $response = $this->storageClient->from($this->bucket)->upload(
                $path,
                $fileContent,
                [
                    'contentType' => $mimeType,
                    'cacheControl' => $options['cache_control'] ?? 'public, max-age=31536000',
                    'upsert' => $options['upsert'] ?? false,
                ]
            );

            Log::info('File uploaded to Supabase', [
                'path' => $path,
                'size' => $fileSize,
                'bucket' => $this->bucket,
            ]);

            return $this->getPublicUrl($path);
        } catch (\Exception $e) {
            Log::error('Supabase upload error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to upload file',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple(array $files, string $prefix = ''): array
    {
        $uploadedFiles = [];

        foreach ($files as $index => $file) {
            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $path = $prefix . '/' . uniqid() . '_' . $index . '.' . $extension;

            try {
                $url = $this->upload($file, $path);
                $uploadedFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => $url,
                    'size' => $file->getSize(),
                ];
            } catch (\Exception $e) {
                Log::error('Failed to upload file in batch', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                $uploadedFiles[] = [
                    'original_name' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $uploadedFiles;
    }

    /**
     * Get public URL for a file.
     */
    public function getPublicUrl(string $path): string
    {
        try {
            $baseUrl = config('supabase.url');
            $publicUrl = "{$baseUrl}/storage/v1/object/public/{$this->bucket}/{$path}";

            if ($this->cdnUrl) {
                return "{$this->cdnUrl}/{$path}";
            }

            return $publicUrl;
        } catch (\Exception $e) {
            Log::error('Failed to get public URL', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to get public URL',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Delete file from Supabase Storage.
     */
    public function delete(string $path): bool
    {
        try {
            $this->storageClient->from($this->bucket)->remove([$path]);

            Log::info('File deleted from Supabase', [
                'path' => $path,
                'bucket' => $this->bucket,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Supabase delete error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to delete file',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Delete multiple files.
     */
    public function deleteMultiple(array $paths): bool
    {
        try {
            $this->storageClient->from($this->bucket)->remove($paths);

            Log::info('Multiple files deleted from Supabase', [
                'count' => count($paths),
                'bucket' => $this->bucket,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Supabase multiple delete error', [
                'count' => count($paths),
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to delete files',
                ['count' => count($paths), 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * List files in a folder.
     */
    public function listFiles(string $prefix = '', int $limit = 100, int $offset = 0): array
    {
        try {
            $response = $this->storageClient->from($this->bucket)->list($prefix, [
                'limit' => $limit,
                'offset' => $offset,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Supabase list files error', [
                'prefix' => $prefix,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to list files',
                ['prefix' => $prefix, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Download file from Supabase Storage.
     */
    public function download(string $path): string
    {
        try {
            $response = $this->storageClient->from($this->bucket)->download($path);

            return $response;
        } catch (\Exception $e) {
            Log::error('Supabase download error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to download file',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Get file metadata.
     */
    public function getMetadata(string $path): array
    {
        try {
            $response = $this->storageClient->from($this->bucket)->getMetadata($path);

            return $response;
        } catch (\Exception $e) {
            Log::error('Supabase get metadata error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to get metadata',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Create signed URL for temporary access.
     */
    public function createSignedUrl(string $path, int $expiresInSeconds = 3600): string
    {
        try {
            $response = $this->storageClient->from($this->bucket)->createSignedUrl($path, $expiresInSeconds);

            return $response['signedUrl'] ?? '';
        } catch (\Exception $e) {
            Log::error('Supabase signed URL error', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to create signed URL',
                ['path' => $path, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Move file within Supabase Storage.
     */
    public function move(string $fromPath, string $toPath): bool
    {
        try {
            // Download the file
            $fileContent = $this->download($fromPath);

            // Upload to new location
            $this->storageClient->from($this->bucket)->upload($toPath, $fileContent);

            // Delete old file
            $this->delete($fromPath);

            Log::info('File moved in Supabase', [
                'from' => $fromPath,
                'to' => $toPath,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Supabase move error', [
                'from' => $fromPath,
                'to' => $toPath,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to move file',
                ['from' => $fromPath, 'to' => $toPath, 'bucket' => $this->bucket],
                $e
            );
        }
    }

    /**
     * Copy file within Supabase Storage.
     */
    public function copy(string $fromPath, string $toPath): bool
    {
        try {
            // Download the file
            $fileContent = $this->download($fromPath);

            // Upload to new location
            $this->storageClient->from($this->bucket)->upload($toPath, $fileContent);

            Log::info('File copied in Supabase', [
                'from' => $fromPath,
                'to' => $toPath,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Supabase copy error', [
                'from' => $fromPath,
                'to' => $toPath,
                'error' => $e->getMessage(),
            ]);
            throw new SupabaseStorageException(
                'Failed to copy file',
                ['from' => $fromPath, 'to' => $toPath, 'bucket' => $this->bucket],
                $e
            );
        }
    }
}