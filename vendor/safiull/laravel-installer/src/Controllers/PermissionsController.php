<?php

namespace Laravel\LaravelInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\LaravelInstaller\Helpers\PermissionsChecker;
use Laravel\LaravelInstaller\Service\EnvatoService;

class PermissionsController extends Controller
{

    /**
     * @var PermissionsChecker
     */
    protected $permissions;
    protected $envUrl;

    /**
     * @param PermissionsChecker $checker
     */
    public function __construct(PermissionsChecker $checker)
    {
        $this->permissions = $checker;
        $this->envUrl = config('installer.api_url');
    }

    /**
     * Display the permissions check page.
     *
     * @return \Illuminate\View\View
     */
    public function permissions()
    {
        $permissions = $this->permissions->check(
            config('installer.permissions')
        );

        return view('vendor.installer.permissions', compact('permissions'));
    }

    public function verify()
    {
        $permissions = $this->permissions->check(
            config('installer.permissions')
        );
        return view('vendor.installer.verify', compact('permissions'));
    }

    public function codeVerifyProcess(Request $request)
    {
        $rules = ['purchase_code' => 'required'];
        $messages = [
            'purchase_code.required' => __('Purchase code field is required.'),
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return redirect()->back()->with(['errors' => $errors]);
        } else {
            $check = $this->checkEnvatoPurchaseCode($request);
            if ($check['success'] == false) {
                return redirect()->back()->with(['message' => $check['message']]);
            } else {
                if($request->_tokens && $request->_tokens == 'purchase_code') {
                    file_put_contents(storage_path('.license'), json_encode([ 'license' => $request->purchase_code ]));
                    return redirect('/')->with('message',__('Code verified successfully'));
                } else {
                    return redirect()->route('LaravelInstaller::environment')->with('message', $check['message']);
                }
            }
        }
    }

    // check envato purchase code
    public function checkEnvatoPurchaseCode($request)
    {
        //SETUP THE API DATA
        $response = ['success' => false, 'message' => __('Invalid request')];
        try {
            $purchase_code = $request->purchase_code;
            if ($purchase_code == env('PURCHASE_CODE')) {
                $response = ['success' => true, 'message' => 'Code verified successfully'];
            } else {
                $service = new EnvatoService();
                $result = $service->checkEnvatoPurchaseCode($purchase_code);
                if ($result['success'] == true) {
                    $response = ['success' => true, 'message' => $result['message']];
                    $this->verifyMessages($purchase_code);
                } else {
                    $response = ['success' => false, 'message' => $result['message']];
                }
            }
        } catch (\Exception $e) {
            $response = ['success' => false, 'message' => $e->getMessage()];
        }

        return $response;
    }

    public function verifyMessages($envPharseKey)
    {
        Cookie::queue('addenvparkey', $envPharseKey);
    }


    public function verifier()
    {
        return view('vendor.installer.verify-code');
    }

}
