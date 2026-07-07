<?php

namespace Laravel\LaravelInstaller\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Laravel\LaravelInstaller\Helpers\EnvironmentManager;
use Laravel\LaravelInstaller\Helpers\FinalInstallManager;
use Laravel\LaravelInstaller\Helpers\InstalledFileManager;
use Laravel\LaravelInstaller\Events\LaravelInstallerFinished;

class FinalController extends Controller
{
    function __construct()
    {
        set_time_limit(300);
    }

    /**
     * Update installed file and display finished view.
     *
     * @param \Laravel\LaravelInstaller\Helpers\InstalledFileManager $fileManager
     * @param \Laravel\LaravelInstaller\Helpers\FinalInstallManager $finalInstall
     * @param \Laravel\LaravelInstaller\Helpers\EnvironmentManager $environment
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function finish(InstalledFileManager $fileManager, FinalInstallManager $finalInstall, EnvironmentManager $environment)
    {
        $finalMessages = $finalInstall->runFinal();
        $finalStatusMessage = $fileManager->update();
        $finalEnvFile = $environment->getEnvContent();

        // Run root migrations
        Artisan::call('migrate', ['--force' => true]);

        // Check if the laravel-modules package is installed
        if (class_exists(\Nwidart\Modules\Facades\Module::class)) {
            // Retrieve all modules
            $modules = \Nwidart\Modules\Facades\Module::all();

            foreach ($modules as $module) {
                $moduleName = $module->getName();

                // Run migrations for the module
                Artisan::call('module:migrate', [
                    'module' => $moduleName,
                    '--force' => true,
                ]);

                // Run seeders for the module
                Artisan::call('module:seed', [
                    'module' => $moduleName,
                    '--force' => true,
                ]);

                // Publish module content
                Artisan::call('module:publish', [
                    'module' => $moduleName,
                ]);
            }
        }

        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');

        event(new LaravelInstallerFinished);

        return view('vendor.installer.finished', compact('finalMessages', 'finalStatusMessage', 'finalEnvFile'));
    }

}
