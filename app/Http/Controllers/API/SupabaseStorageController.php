<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupabaseStorageController extends Controller
{
    protected SupabaseStorageService $storageService;

    public function __construct(SupabaseStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    /**
     * Upload file to Supabase Storage.
     */
    public function upload(Request $request)
    {
        $validator = validator($request->all(), [
            'file' => 'required|file|max:10240', // Max 10MB
            'path' => 'required|string',
            'upsert' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $options = [
                'upsert' => $request->get('upsert', false),
            ];

            $url = $this->storageService->upload(
                $request->file('file'),
                $request->path,
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'url' => $url,
                'path' => $request->path,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload multiple files.
     */
    public function uploadMultiple(Request $request)
    {
        $validator = validator($request->all(), [
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|max:10240',
            'prefix' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $prefix = $request->get('prefix', 'uploads/' . Auth::id());
            $uploadedFiles = $this->storageService->uploadMultiple(
                $request->file('files'),
                $prefix
            );

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'files' => $uploadedFiles,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete file from Supabase Storage.
     */
    public function delete(Request $request)
    {
        $validator = validator($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $this->storageService->delete($request->path);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Delete failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List files in a folder.
     */
    public function listFiles(Request $request)
    {
        $validator = validator($request->all(), [
            'prefix' => 'nullable|string',
            'limit' => 'nullable|integer|max:100',
            'offset' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $files = $this->storageService->listFiles(
                $request->get('prefix', ''),
                $request->get('limit', 100),
                $request->get('offset', 0)
            );

            return response()->json([
                'success' => true,
                'files' => $files,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to list files',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download file from Supabase Storage.
     */
    public function download(Request $request)
    {
        $validator = validator($request->all(), [
            'path' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $fileContent = $this->storageService->download($request->path);

            return response($fileContent)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', 'attachment; filename="' . basename($request->path) . '"');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Download failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create signed URL for temporary access.
     */
    public function createSignedUrl(Request $request)
    {
        $validator = validator($request->all(), [
            'path' => 'required|string',
            'expires_in' => 'nullable|integer|min:60|max:86400',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $expiresInSeconds = $request->get('expires_in', 3600);
            $signedUrl = $this->storageService->createSignedUrl(
                $request->path,
                $expiresInSeconds
            );

            return response()->json([
                'success' => true,
                'signed_url' => $signedUrl,
                'expires_in' => $expiresInSeconds,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create signed URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}