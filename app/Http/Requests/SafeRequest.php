<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SafeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'sometimes|required|email|max:255|filter:email',
            'name' => 'sometimes|required|string|max:255|regex:/^[a-zA-Z\s\-\u0600-\u06FF]+$/u',
            'phone' => 'sometimes|required|string|max:20|regex:/^[0-9\+\-\s]+$/',
            'password' => 'sometimes|required|string|min:8|max:255',
        ];
    }

    public function sanitize(): array
    {
        $input = $this->all();

        // Sanitize email
        if (isset($input['email'])) {
            $input['email'] = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
            $input['email'] = strtolower(trim($input['email']));
        }

        // Sanitize name
        if (isset($input['name'])) {
            $input['name'] = htmlspecialchars(strip_tags($input['name']), ENT_QUOTES, 'UTF-8');
            $input['name'] = trim($input['name']);
        }

        // Sanitize phone
        if (isset($input['phone'])) {
            $input['phone'] = preg_replace('/[^0-9\+\-\s]/', '', $input['phone']);
            $input['phone'] = trim($input['phone']);
        }

        // Sanitize password (don't log it)
        if (isset($input['password'])) {
            // Keep password as-is, just ensure it's a string
            $input['password'] = (string) $input['password'];
        }

        // Sanitize all string inputs
        foreach ($input as $key => $value) {
            if (is_string($value) && !in_array($key, ['password', 'password_confirmation'])) {
                $input[$key] = htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
            }
        }

        $this->replace($input);

        return $input;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    protected function prepareForValidation()
    {
        $this->sanitize();
    }
}