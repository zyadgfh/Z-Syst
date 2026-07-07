<?php

namespace Laravel\LaravelInstaller\Helpers;

use Illuminate\Auth\SessionGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\LaravelInstaller\Service\EnvatoService;

class UserTechGuard extends SessionGuard
{
    public function attempt(array $credentials = [], $remember = false) {

    $res = parent::attempt($credentials, $remember);
    
    if ($res) {        
        try {
            $check = DB::table('users')->where(['email' => $credentials['email']])->first();
            
            if($check && $check->role == 1) {
                $service = new EnvatoService();
                $file_path = storage_path('.license');

                if (file_exists($file_path)) {
                    $file_contents = file_get_contents($file_path);
                    $code = json_decode($file_contents);
                    $purchase_code = $code->license;
                    $checkClient = $service->checkExistClient($purchase_code);
                    
                    if ($checkClient['success']) {
                        return ['success' => true,'code' => true, 'client' => true, 'credentials' => true];
                    } else {
                        $this->logout();
                        return ['success' => false,'code' => true, 'client' => false, 'credentials' => true]; 
                    }
                } else {
                    $this->logout();
                    return ['success' => false,'code' => false, 'client' => true, 'credentials' => true];
                }
            } else {
                return ['success' => true,'code' => true, 'client' => true, 'credentials' => true];
            }  
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            $this->logout();
            return ['success' => false,'code' => true, 'client' => true, 'credentials' => false];
        }
    }
    $this->logout();
    return ['success' => false,'code' => true, 'client' => true, 'credentials' => false];    
  }
}