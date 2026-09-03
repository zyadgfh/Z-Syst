<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * SystemSettingController — fix file for LOW severity findings.
 *
 * FINDING L-01: per_page not validated — user can pass per_page=99999
 * FINDING L-02: Google service account JSON upload stored to storage/app/
 * FINDING L-03: updateEnv writes to .env directly
 *
 * These are system-level settings (not business-scoped), so business
 * isolation is not applicable here.
 */
class SystemSettingController extends Controller
{
    // ──────────────────────────────────────────────
    //  FIX L-01: Cap per_page to prevent abuse
    // ──────────────────────────────────────────────

    /**
     * Validate and cap the per_page parameter.
     */
    private function validatedPerPage(Request $request, int $max = 50): int
    {
        $per_page = (int) $request->input('per_page', 25);

        return max(1, min($per_page, $max));
    }

    /**
     * GET /admin/system-settings
     */
    public function index(Request $request)
    {
        $per_page = $this->validatedPerPage($request, 50);

        // ... existing query logic with capped per_page ...

        return response()->json([
            'success' => true,
            'data'    => [], // placeholder
        ]);
    }

    // ──────────────────────────────────────────────
    //  FIX L-02: Validate Google service account JSON
    // ──────────────────────────────────────────────

    /**
     * POST /admin/system-settings/google-service-account
     *
     * Validates that the uploaded file is valid JSON with expected
     * Google service account fields before writing to disk.
     */
    public function uploadGoogleServiceAccount(Request $request): JsonResponse
    {
        $request->validate([
            'service_account' => ['required', 'file', 'mimes:json,txt', 'max:1024'],
        ]);

        $file = $request->file('service_account');
        $content = file_get_contents($file->getRealPath());

        // Validate it is valid JSON
        $json = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Uploaded file is not valid JSON.',
            ], 422);
        }

        // Validate required Google service account fields
        $requiredFields = ['type', 'project_id', 'private_key', 'client_email'];
        $missing = array_diff($requiredFields, array_keys($json));
        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Google service account file. Missing fields: ' . implode(', ', $missing),
            ], 422);
        }

        // Write to a safe location (not public)
        $destination = storage_path('app/google-service-account.json');
        file_put_contents($destination, $content);

        Log::info('Google service account file uploaded.', [
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Google service account file uploaded successfully.',
        ]);
    }

    // ──────────────────────────────────────────────
    //  FIX L-03: Restrict .env key updates
    // ──────────────────────────────────────────────

    /**
     * Whitelist of keys that are safe to update via this endpoint.
     * Never allow keys like APP_KEY, DB_PASSWORD, etc. to be set
     * through this mechanism.
     */
    private const ALLOWED_ENV_KEYS = [
        'GOOGLE_SERVICE_ACCOUNT_PATH',
        'GOOGLE_DRIVE_FOLDER_ID',
        'GOOGLE_SHEET_ID',
        'MAIL_HOST',
        'MAIL_PORT',
        'MAIL_USERNAME',
        'MAIL_FROM_ADDRESS',
        'MAIL_FROM_NAME',
        'APP_LOCALE',
        'APP_TIMEZONE',
    ];

    /**
     * PUT /admin/system-settings/env
     *
     * Updates .env with strict key whitelisting and type validation.
     */
    public function updateEnv(Request $request): JsonResponse
    {
        $request->validate([
            'key'   => ['required', 'string', 'in:' . implode(',', self::ALLOWED_ENV_KEYS)],
            'value' => ['required', 'string', 'max:500'],
        ]);

        $key = $request->input('key');
        $value = $request->input('value');

        // Type validation for known numeric keys
        if (in_array($key, ['MAIL_PORT']) && !ctype_digit($value)) {
            return response()->json([
                'success' => false,
                'message' => "Value for {$key} must be a numeric port number.",
            ], 422);
        }

        // Sanitize: strip newlines and carriage returns to prevent env injection
        $value = str_replace(["\r", "\n"], '', $value);

        $envFile = base_path('.env');
        if (!file_exists($envFile)) {
            return response()->json([
                'success' => false,
                'message' => '.env file not found.',
            ], 500);
        }

        $envContent = file_get_contents($envFile);
        $pattern = '/^' . preg_quote($key, '/') . '=.*/m';

        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, $key . '=' . $value, $envContent);
        } else {
            $envContent .= "\n{$key}={$value}";
        }

        file_put_contents($envFile, $envContent);

        Log::info('.env key updated.', [
            'user_id' => auth()->id(),
            'key'     => $key,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Environment variable {$key} updated successfully.",
        ]);
    }
}
