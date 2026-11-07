<?php

namespace App\Helpers;

use App\Exceptions\UploadException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HasUploader
{
    /**
     * Upload allowlist (H-02): extension AND guessed MIME must both pass.
     * SVG is excluded by default — served inline from a same-origin path it
     * is a stored-XSS vector. Override via integrations.uploads.allowed_*
     * if a deployment truly needs more (and serve them with
     * Content-Disposition: attachment / a separate origin).
     */
    private function zsValidateUpload($file): void
    {
        $allowedExt = (array) config('integrations.uploads.allowed_extensions', [
            'png', 'jpg', 'jpeg', 'webp', 'gif', 'pdf',
        ]);
        $allowedMime = (array) config('integrations.uploads.allowed_mimes', [
            'image/png', 'image/jpeg', 'image/webp', 'image/gif', 'application/pdf',
        ]);

        $ext = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        if (! in_array($ext, $allowedExt, true) || ! in_array($mime, $allowedMime, true)) {
            throw new \App\Exceptions\UploadException(
                'Upload type not allowed',
                ['extension' => $ext, 'mime' => $mime]
            );
        }
    }

    /**
     * Write upload bytes with optional cloud shipping (Supabase Storage).
     *
     * When integrations.uploads.cloud_enabled is on and Supabase is
     * configured, the object is stored in the cloud bucket FIRST and the
     * local disk write is skipped on success (the DB still records the same
     * relative path, e.g. uploads/26/11/…, so rendering is unchanged).
     * ANY cloud failure falls back to the local disk write, so uploads
     * never break in previews/offline environments.
     */
    private function zsStore(string $filePath, string $contents, ?string $mimeType = null): bool
    {
        if (! config('integrations.uploads.cloud_enabled')) {
            return false;
        }

        $result = app(\App\Services\SupabaseStorage::class)->upload(
            config('integrations.uploads.bucket', 'uploads'),
            $filePath,
            $contents,
            $mimeType
        );

        if ($result['ok']) {
            return true;
        }

        \Illuminate\Support\Facades\Log::warning('Upload cloud ship failed — local fallback used', [
            'path' => $filePath,
            'error' => $result['error'] ?? 'unknown',
        ]);

        return false;
    }

    /**
     * Remove an old upload honoring the cloud-first setting (best effort —
     * local cleanup always runs so the disk never accumulates orphans).
     */
    private function zsDelete(?string $oldFile): void
    {
        if (! $oldFile) {
            return;
        }

        if (config('integrations.uploads.cloud_enabled')) {
            app(\App\Services\SupabaseStorage::class)->delete(
                config('integrations.uploads.bucket', 'uploads'),
                $oldFile
            );
        }

        if (Storage::exists($oldFile)) {
            Storage::delete($oldFile);
        }
    }

    /**
     * Upload a single file with validation and error handling.
     *
     * @throws UploadException
     */
    private function upload(Request $request, $input, $oldFile = null, $disk = null)
    {
        // Check if file exists in request
        if (! $request->hasFile($input)) {
            throw new UploadException('No file uploaded', ['input' => $input]);
        }

        $file = $request->file($input);

        // Validate the file
        if (! $file->isValid()) {
            throw new UploadException(
                $file->getErrorMessage(),
                ['input' => $input, 'original_name' => $file->getClientOriginalName()]
            );
        }

        $this->zsValidateUpload($file);

        try {
            $ext = $file->getClientOriginalExtension();
            $filename = now()->timestamp.'-'.rand(1, 1000).'.'.$ext;

            $path = 'uploads/'.date('y').'/'.date('m').'/';
            $filePath = $path.$filename;

            $this->zsDelete($oldFile);

            // Store the file (cloud-first, local fallback)
            $contents = file_get_contents($file);
            if (! $this->zsStore($filePath, $contents, $file->getMimeType())) {
                Storage::disk($disk ?? config('filesystems.default'))
                    ->put($filePath, $contents);
            }

            return $filePath;

        } catch (\Throwable $e) {
            throw new UploadException(
                'Storage write failed',
                [
                    'input' => $input,
                    'path' => $filePath ?? null,
                    'error' => $e->getMessage(),
                ],
                $e
            );
        }
    }

    /**
     * Upload a file preserving its original name.
     *
     * @throws UploadException
     */
    private function uploadWithFileName(Request $request, $input, $oldFile = null, $disk = null)
    {
        if (! $request->hasFile($input)) {
            throw new UploadException('No file uploaded', ['input' => $input]);
        }

        $file = $request->file($input);

        if (! $file->isValid()) {
            throw new UploadException(
                $file->getErrorMessage(),
                ['input' => $input, 'original_name' => $file->getClientOriginalName()]
            );
        }

        $this->zsValidateUpload($file);

        try {
            $filename = $file->getClientOriginalName();
            $path = 'files/';
            $filePath = $path.$filename;

            $this->zsDelete($oldFile);

            $contents = file_get_contents($file);
            if (! $this->zsStore($filePath, $contents, $file->getMimeType())) {
                Storage::disk($disk ?? config('filesystems.default'))
                    ->put($filePath, $contents);
            }

            return $filePath;

        } catch (\Throwable $e) {
            throw new UploadException(
                'Storage write failed',
                [
                    'input' => $input,
                    'path' => $filePath ?? null,
                    'error' => $e->getMessage(),
                ],
                $e
            );
        }
    }

    /**
     * Upload multiple files with validation.
     *
     * @throws UploadException
     */
    private function multipleUpload(Request $request, $input, $oldFiles = [], $disk = null)
    {
        if (! $request->hasFile($input)) {
            throw new UploadException('No files uploaded', ['input' => $input]);
        }

        $uploadedFiles = [];

        try {
            foreach ($request->file($input) as $file) {
                if (! $file->isValid()) {
                    throw new UploadException(
                        $file->getErrorMessage(),
                        ['input' => $input, 'original_name' => $file->getClientOriginalName()]
                    );
                }

                $this->zsValidateUpload($file);

                $ext = $file->getClientOriginalExtension();
                $filename = now()->timestamp.'_'.uniqid().'.'.$ext;

                $path = 'uploads/'.date('y').'/'.date('m').'/';
                $filePath = $path.$filename;

                foreach ($oldFiles as $oldFile) {
                    $this->zsDelete($oldFile);
                }

                $contents = file_get_contents($file);
                if (! $this->zsStore($filePath, $contents, $file->getMimeType())) {
                    Storage::disk($disk ?? config('filesystems.default'))
                        ->put($filePath, $contents);
                }

                $uploadedFiles[] = $filePath;
            }

            return $uploadedFiles;

        } catch (\Throwable $e) {
            // Clean up any partially uploaded files
            foreach ($uploadedFiles as $uploadedFile) {
                if (Storage::exists($uploadedFile)) {
                    Storage::delete($uploadedFile);
                }
            }

            throw new UploadException(
                'Multiple upload failed',
                [
                    'input' => $input,
                    'uploaded_count' => count($uploadedFiles),
                    'error' => $e->getMessage(),
                ],
                $e
            );
        }
    }
}
