<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SystemSettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;

class SystemSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings-read')->only('index');
        $this->middleware('permission:settings-update')->only('store');
    }

    public function index()
    {
        return view('admin.settings.system');
    }

    public function store(SystemSettingRequest $request)
    {
        $validated = $request->validated();

        if ($request->hasFile('service_account_credentials')) {
            $file = $request->file('service_account_credentials');
            $content = File::get($file->getRealPath());
            $json = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'Invalid JSON file content.'], 422);
            }

            $requiredFields = [
                'type',
                'project_id',
                'private_key_id',
                'private_key',
                'client_email',
                'client_id',
                'auth_uri',
                'token_uri',
                'auth_provider_x509_cert_url',
                'client_x509_cert_url',
            ];

            foreach ($requiredFields as $field) {
                if (!isset($json[$field])) {
                    return response()->json(['message' => "Missing required JSON field: $field"], 422);
                }
            }

            if ($json['type'] !== 'service_account') {
                return response()->json(['message' => 'Invalid service account type.'], 422);
            }

            if (!filter_var($json['client_email'], FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'Invalid client email address.'], 422);
            }

            $name = 'service-account-' . time() . '-' . bin2hex(random_bytes(8)) . '.json';
            $path = storage_path('app/private/firebase/');

            if (!File::exists($path)) {
                File::makeDirectory($path, 0600, true);
            }

            $file->move($path, $name);
            File::chmod($path . $name, 0600);
        }

        $storeKeys = [
            'APP_NAME', 'APP_ENV', 'APP_DEBUG', 'APP_URL', 'QUEUE_MAIL',
            'MAIL_DRIVER_TYPE','MAIL_DRIVER','MAIL_HOST','MAIL_PORT','MAIL_USERNAME',
            'MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME','CACHE_DRIVER',
            'QUEUE_CONNECTION','SESSION_DRIVER','SESSION_LIFETIME','FILESYSTEM_DISK',
            'AWS_ACCESS_KEY_ID','AWS_DEFAULT_REGION','AWS_BUCKET',
            'WAS_ACCESS_KEY_ID','WAS_DEFAULT_REGION','WAS_BUCKET','WAS_ENDPOINT',
            'CACHE_LIFETIME','TIMEZONE'
        ];

        foreach ($storeKeys as $k) {
            $val = $validated[$k] ?? env($k);
            if (is_null($val)) {
                continue;
            }
            Setting::updateOrCreate(
                ['key' => $k],
                ['value' => (string) $val, 'type' => 'string']
            );
        }

        // Sensitive keys: encrypt before storing
        $sensitive = [
            'MAIL_PASSWORD', 'AWS_SECRET_ACCESS_KEY', 'WAS_SECRET_ACCESS_KEY', 'APILAYER_API_KEY'
        ];

        foreach ($sensitive as $k) {
            if (isset($validated[$k]) && $validated[$k] !== null) {
                Setting::updateOrCreate(
                    ['key' => $k],
                    ['value' => Crypt::encryptString($validated[$k]), 'type' => 'encrypted']
                );
            }
        }

        // If service account file saved above, persist filename
        if (isset($name)) {
            Setting::updateOrCreate(
                ['key' => 'FIREBASE_SERVICE_ACCOUNT'],
                ['value' => $name, 'type' => 'string']
            );
        }

        // Ensure APP_ENV is set to production in .env if different (minimal write)
        if (env('APP_ENV') !== 'production') {
            try {
                $this->writeEnv(['APP_ENV' => 'production']);
            } catch (\Throwable $e) {
                // Log but don't fail the request
                logger()->warning('Failed to update .env APP_ENV: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'System Updated']);
    }

    protected function writeEnv(array $values)
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            return false;
        }

        $content = File::get($path);

        foreach ($values as $key => $value) {
            $escapedValue = str_replace("\n", '\\n', (string) $value);
            if (preg_match("/^{$key}=.*$/m", $content)) {
                $content = preg_replace("/^{$key}=.*$/m", "$key={$escapedValue}", $content);
            } else {
                $content .= PHP_EOL . "$key={$escapedValue}";
            }
        }

        File::put($path, $content);
        return true;
    }
}
