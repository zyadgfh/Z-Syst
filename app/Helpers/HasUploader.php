<?php

namespace App\Helpers;

use App\Exceptions\UploadException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait HasUploader
{
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

        try {
            $ext = $file->getClientOriginalExtension();
            $filename = now()->timestamp.'-'.rand(1, 1000).'.'.$ext;

            $path = 'uploads/'.date('y').'/'.date('m').'/';
            $filePath = $path.$filename;

            // Delete old file if exists
            if ($oldFile) {
                if (Storage::exists($oldFile)) {
                    Storage::delete($oldFile);
                }
            }

            // Store the file
            Storage::disk($disk ?? config('filesystems.default'))
                ->put($filePath, file_get_contents($file));

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

        try {
            $filename = $file->getClientOriginalName();
            $path = 'files/';
            $filePath = $path.$filename;

            if ($oldFile) {
                if (Storage::exists($oldFile)) {
                    Storage::delete($oldFile);
                }
            }

            Storage::disk($disk ?? config('filesystems.default'))
                ->put($filePath, file_get_contents($file));

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

                $ext = $file->getClientOriginalExtension();
                $filename = now()->timestamp.'_'.uniqid().'.'.$ext;

                $path = 'uploads/'.date('y').'/'.date('m').'/';
                $filePath = $path.$filename;

                // Delete old files
                foreach ($oldFiles as $oldFile) {
                    if (Storage::exists($oldFile)) {
                        Storage::delete($oldFile);
                    }
                }

                Storage::disk($disk ?? config('filesystems.default'))
                    ->put($filePath, file_get_contents($file));

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
