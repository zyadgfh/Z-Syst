<?php

use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'install', 'as' => 'LaravelInstaller::', 'namespace' => 'Laravel\LaravelInstaller\Controllers', 'middleware' => ['web', 'install']], function () {
    Route::get('/', [
        'as' => 'welcome',
        'uses' => 'WelcomeController@welcome',
    ]);

    Route::get('environment', [
        'as' => 'environment',
        'uses' => 'EnvironmentController@environmentMenu',
    ]);

    Route::get('environment/wizard', [
        'as' => 'environmentWizard',
        'uses' => 'EnvironmentController@environmentWizard',
    ]);

    Route::post('environment/saveWizard', [
        'as' => 'environmentSaveWizard',
        'uses' => 'EnvironmentController@saveWizard',
    ]);

    Route::get('environment/classic', [
        'as' => 'environmentClassic',
        'uses' => 'EnvironmentController@environmentClassic',
    ]);

    Route::post('environment/saveClassic', [
        'as' => 'environmentSaveClassic',
        'uses' => 'EnvironmentController@saveClassic',
    ]);

    Route::get('requirements', [
        'as' => 'requirements',
        'uses' => 'RequirementsController@requirements',
    ]);

    Route::get('permissions', [
        'as' => 'permissions',
        'uses' => 'PermissionsController@permissions',
    ]);

    Route::get('verify-purchase-code', [
        'as' => 'verify',
        'uses' => 'PermissionsController@verify'
    ]);

    Route::post('purchase-code/verify/process', [
        'as' => 'codeVerifyProcess',
        'uses' => 'PermissionsController@codeVerifyProcess'
    ]);

    Route::get('database', [
        'as' => 'database',
        'uses' => 'DatabaseController@database',
    ]);

    Route::get('final', [
        'as' => 'final',
        'uses' => 'FinalController@finish',
    ]);

});

Route::group(['prefix' => 'update', 'as' => 'LaravelUpdater::', 'namespace' => 'Laravel\LaravelInstaller\Controllers', 'middleware' => 'web'], function () {
    Route::group(['middleware' => 'update'], function () {
        Route::get('/', [
            'as' => 'welcome',
            'uses' => 'UpdateController@welcome',
        ]);

        Route::get('overview', [
            'as' => 'overview',
            'uses' => 'UpdateController@overview',
        ]);

        Route::get('database', [
            'as' => 'database',
            'uses' => 'UpdateController@database',
        ]);

        Route::post('download-updated-code', [
            'as' => 'downloadUpdatedCode',
            'uses' => 'UpdateController@downloadUpdatedCode',
        ]);

        Route::get('downloadfile', [
            'as' => 'downloadFile',
            'uses' => 'UpdateController@downloadFile',
        ]);
        Route::get('downloaded', [
            'as' => 'downloaded',
            'uses' => 'UpdateController@downloaded',
        ]);
        Route::get('update-process', [
            'as' => 'updateProcess',
            'uses' => 'UpdateController@updateProcess',
        ]);
    });

    // This needs to be out of the middleware because right after the migration has been
    // run, the middleware sends a 404.
    Route::get('final', [
        'as' => 'final',
        'uses' => 'UpdateController@finish',
    ]);
});


Route::group(['prefix' => 'envato', 'as' => 'LaravelVerifier::', 'namespace' => 'Laravel\LaravelInstaller\Controllers', 'middleware' => 'web'], function () {

    Route::get('code-verification', [
        'as' => 'verifier',
        'uses' => 'PermissionsController@verifier'
    ]);

});

Route::group(['prefix' => 'envato', 'as' => 'LaravelInstaller::', 'namespace' => 'Laravel\LaravelInstaller\Controllers', 'middleware' => 'web'], function () {

    Route::post('purchase-code/verify/process', [
        'as' => 'codeVerifyProcess',
        'uses' => 'PermissionsController@codeVerifyProcess'
    ]);
});