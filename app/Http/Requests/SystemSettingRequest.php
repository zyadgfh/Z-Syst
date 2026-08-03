<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SystemSettingRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'APP_NAME' => 'required|string|max:255',
            'APP_DEBUG' => 'required|in:true,false',
            'APP_URL' => 'required|url|max:255',
            'QUEUE_MAIL' => 'nullable|in:true,false',
            'MAIL_DRIVER_TYPE' => 'nullable|in:MAIL_MAILER,MAIL_DRIVER',
            'MAIL_DRIVER' => 'nullable|in:sendmail,smtp',
            'MAIL_HOST' => 'nullable|string|max:255',
            'MAIL_PORT' => 'nullable|integer|min:1|max:65535',
            'MAIL_USERNAME' => 'nullable|email|max:255',
            'MAIL_PASSWORD' => 'nullable|string|max:255',
            'MAIL_ENCRYPTION' => 'nullable|in:tls,ssl',
            'MAIL_FROM_ADDRESS' => 'nullable|email|max:255',
            'MAIL_FROM_NAME' => 'nullable|string|max:255',
            'CACHE_DRIVER' => 'required|in:array,file,memcached,redis',
            'QUEUE_CONNECTION' => 'required|string|max:255',
            'SESSION_DRIVER' => 'required|string|max:255',
            'SESSION_LIFETIME' => 'required|integer|min:1|max:525600',
            'FILESYSTEM_DISK' => 'required|in:public,s3,wasabi',
            'AWS_ACCESS_KEY_ID' => 'nullable|string|max:255',
            'AWS_SECRET_ACCESS_KEY' => 'nullable|string|max:255',
            'AWS_DEFAULT_REGION' => 'nullable|string|max:255',
            'AWS_BUCKET' => 'nullable|string|max:255',
            'WAS_ACCESS_KEY_ID' => 'nullable|string|max:255',
            'WAS_SECRET_ACCESS_KEY' => 'nullable|string|max:255',
            'WAS_DEFAULT_REGION' => 'nullable|string|max:255',
            'WAS_BUCKET' => 'nullable|string|max:255',
            'WAS_ENDPOINT' => 'nullable|url|max:255',
            'CACHE_LIFETIME' => 'nullable|integer|min:0|max:31536000',
            'TIMEZONE' => 'nullable|timezone',
            'APILAYER_API_KEY' => 'nullable|string|max:255',
            'service_account_credentials' => 'nullable|file|mimes:json,txt|max:2048',
        ];
    }
}
