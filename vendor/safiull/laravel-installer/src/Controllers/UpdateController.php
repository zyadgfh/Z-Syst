<?php

namespace Laravel\LaravelInstaller\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\LaravelInstaller\Helpers\InstalledFileManager;
use Laravel\LaravelInstaller\Service\EnvatoService;
use Laravel\LaravelInstaller\Helpers\DatabaseManager;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use ZipArchive;

class UpdateController extends Controller
{
    use \Laravel\LaravelInstaller\Helpers\MigrationsHelper;

    private $service;
    private $current_path;
    private $root_path;
    private $apiUrl = '';
    private $configUrlPath = '';

    public function __construct()
    {
        set_time_limit(300);
        $this->service = new EnvatoService();
        $this->configUrlPath = $this->apiUrl = config('installer.download_path') ? config('installer.download_path') : '/../../../../..';
        $this->current_path = realpath(__DIR__);
        $this->root_path = realpath($this->current_path . $this->configUrlPath);
        $this->apiUrl = config('installer.api_url') ? config('installer.api_url') : 'https://itech.tradexpro.org';
    }

    /**
     * Display the updater welcome page.
     *
     * @return \Illuminate\View\View
     */
    public function welcome()
    {
        return view('vendor.installer.update.welcome');
    }

    /**
     * Display the updater overview page.
     *
     * @return \Illuminate\View\View
     */
    public function overview()
    {
        $migrations = $this->getMigrations();
        $dbMigrations = $this->getExecutedMigrations();
        $data['numberOfUpdatesPending'] = count($migrations) - count($dbMigrations);
        $data['purchase_code'] = '';
        $data['version_list'] = [];
        $file_path = storage_path('.license');

        if (file_exists($file_path)) {
            $file_contents = file_get_contents($file_path);
            $code = json_decode($file_contents);
            $data['purchase_code'] = $code->license;
            $getVersion = $this->service->getProductVersion($code->license);
            
            if ($getVersion['success']) {
                $data['version_list'] = $getVersion['data'];
            }
        } 
        

        return view('vendor.installer.update.overview', $data);
    }

    /**
     * Migrate and seed the database.
     *
     * @return \Illuminate\View\View
     */
    public function database()
    {
        $databaseManager = new DatabaseManager;
        $response = $databaseManager->migrateAndSeed();

        return redirect()->route('LaravelUpdater::final')
                         ->with(['message' => $response]);
    }

    /**
     * Update installed file and display finished view.
     *
     * @param InstalledFileManager $fileManager
     * @return \Illuminate\View\View
     */
    public function finish()
    {
        $data['message'] = __('Product updated successfully');
        return view('vendor.installer.update.finished', $data);
    }

    public function downloadUpdatedCode(Request $request, Redirector $redirect)
    {
        $rules = [
            'code' => 'required',
            'version' => 'required'
        ];
        $validator = Validator::make($request->all(), $rules, []);
        if ($validator->fails()) {
            return $redirect->route('LaravelUpdater::overview')->withInput()->withErrors($validator->errors());
        }
        
        $response = $this->service->downloadUpdate($request->code,$request->version);
        
        if($response['success']) {
            Session::put(['reference_code' => $response['data']['file_reference']]);
            Session::put(['ref_file_name' => $response['data']['file_name']]);
            return $redirect->route('LaravelUpdater::downloadFile',['id' => $response['data']['file_reference']])->with('message','success');
        } else {
            return $redirect->route('LaravelUpdater::overview')->withInput()->withErrors($response['message']);
        }
    }

    public function downloadFile(Request $request)
    {
        $sourceUrl = $this->apiUrl.'/api/file/download/'.$request->id;
        $destinationPath = $this->root_path.'/';
        $response = Http::get($sourceUrl);
        
        if ($response->successful()) {
            $fileName = Session::get('ref_file_name');
            $fileContent = $response->body();
           
            // Save the downloaded file to the root directory of your project
            file_put_contents($destinationPath .'/'.$fileName , $fileContent);
            return redirect()->route('LaravelUpdater::downloaded')->with('message',__('File downloaded and saved successfully'));
        } else {
            return redirect()->route('LaravelUpdater::overview')->with('message',__('Failed to download the file try again plz'));
        }
    }

    public function downloaded()
    {
        $data['message'] = __('Download successfully');
        $data['file_name'] = Session::get('ref_file_name');
        return view('vendor.installer.update.downloaded', $data);
    }

    public function updateProcess(Request $request)
    {
        try {
            if($request->fileName) {
                $fileName = $request->fileName;

                $zip = new ZipArchive;
            $destination = $this->root_path .'/'. $fileName;
            $res = $zip->open($destination);
            
            $nPath = str_replace(".zip", "", $fileName);
            if ($res === TRUE) {
                $zip->extractTo($this->root_path . "/".$nPath);
                $zip->close();
                unlink($destination);
            }
            
            $sourceDirectory = $this->root_path.'/'.$nPath; // Replace with the actual path to your source directory
            
            $extractedFiles = File::allFiles($sourceDirectory);

            foreach ($extractedFiles as $file) {
                $newLocation = $this->root_path.'/'.$file->getRelativePathname();

                // Get the last directory name
                $lastDirectoryName = basename(dirname($file->getRelativePathname()));

                // Construct the full path for the last directory
                $lastDirectoryPath = $this->root_path.'/'.dirname($file->getRelativePathname());

                if (!File::isDirectory($lastDirectoryPath)) {
                    File::makeDirectory($lastDirectoryPath, 0755, true);
                }
                
                File::copy($file->getRealPath(), $newLocation);
                
            }
            if (is_dir($sourceDirectory)) {
                removeDirectory($sourceDirectory);
            }
            return redirect()->route('LaravelUpdater::final')->with('message',__('Product updated successfully'));
    
            } else {
                return redirect()->route('LaravelUpdater::overview')->with('message',__('No file found to continue update process'));
            }
            
        } catch (\Exception $e) {
            dd($e->getMessage());
            return redirect()->route('LaravelUpdater::overview')->with('message',$e->getMessage());
        }
    }
}
