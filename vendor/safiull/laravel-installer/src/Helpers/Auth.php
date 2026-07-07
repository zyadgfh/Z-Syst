<?php

namespace Laravel\LaravelInstaller\Helpers;

use Illuminate\Support\Facades\Auth as FacadesAuth;

class Auth
{
    public static function attempt(array $credentials = [], $remember = false) {
        $response = FacadesAuth::attempt($credentials, $remember);
        if ($response['success']) {
            return true;
        } else {
            if($response['code'] == false || $response['client'] == false) {
                return 'LaravelVerifier::verifier';
            }
            return false;
        }
    }
}
