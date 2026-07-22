<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait HasUploader
{
    private function upload(Request $request, $input, $oldFile = null, $disk = null)
    {
        $file = $request->file($input);

        // Validate file type and size
        $allowedMimes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        $ext = strtolower($file->getClientOriginalExtension());

        if (!in_array($ext, $allowedMimes)) {
            throw new \InvalidArgumentException("File type '{$ext}' is not allowed.");
        }

        // Generate safe, unique filename (never trust user input)
        $filename = now()->timestamp . '-' . Str::random(16) . '.' . $ext;

        $path = 'uploads/' . date('y') . '/' . date('m') . '/';
        $filePath = $path . $filename;

        if ($oldFile) {
            if (Storage::disk($disk ?? config('filesystems.default'))->exists($oldFile)) {
                Storage::disk($disk ?? config('filesystems.default'))->delete($oldFile);
            }
        }

        Storage::disk($disk ?? config('filesystems.default'))->put($filePath, file_get_contents($file));
        return $filePath;
    }

    private function uploadWithFileName(Request $request, $input, $oldFile = null, $disk = null)
    {
        $file = $request->file($input);

        // Sanitize and generate safe filename — NEVER trust client-supplied filename
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower($file->getClientOriginalExtension());
        $safeName = Str::slug($originalName) . '-' . Str::random(8) . '.' . $ext;

        $path = 'files/';
        $filePath = $path . $safeName;

        if ($oldFile) {
            if (Storage::disk($disk ?? config('filesystems.default'))->exists($oldFile)) {
                Storage::disk($disk ?? config('filesystems.default'))->delete($oldFile);
            }
        }

        Storage::disk($disk ?? config('filesystems.default'))->put($filePath, file_get_contents($file));
        return $filePath;
    }

    private function multipleUpload(Request $request, $input, $oldFiles = [], $disk = null)
    {
        $uploadedFiles = [];

        foreach ($request->file($input) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $filename = now()->timestamp . '_' . Str::random(16) . '.' . $ext;

            $path = 'uploads/' . date('y') . '/' . date('m') . '/';
            $filePath = $path . $filename;

            foreach ($oldFiles as $oldFile) {
                if (Storage::disk($disk ?? config('filesystems.default'))->exists($oldFile)) {
                    Storage::disk($disk ?? config('filesystems.default'))->delete($oldFile);
                }
            }

            Storage::disk($disk ?? config('filesystems.default'))->put($filePath, file_get_contents($file));
            $uploadedFiles[] = $filePath;
        }

        return $uploadedFiles;
    }

}
