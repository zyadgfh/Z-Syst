<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | Public disk
        |--------------------------------------------------------------------------
        |
        | `root` used to be '.', which resolved every path against the
        | *application root* — the code tree. A write through this disk was
        | therefore a write into the deployment directory: able to overwrite
        | tracked files, or to land content under public/ where nginx would
        | serve it. Nothing confined the disk except one caller's string checks
        | (BarcodeController::image() rejects '..' and requires a 'barcodes/'
        | prefix), which is a thin thing to depend on for a filesystem boundary.
        |
        | It also split one service across two locations. BarcodeService::
        | barcodeDirectory() creates storage/app/public/barcodes/{businessId} and
        | saves its PDFs there via storage_path(), but wrote the PNG/SVG labels
        | through this disk — so images landed at <app root>/barcodes/{id}
        | instead. On Render that is the container's ephemeral filesystem: the
        | mounted volume is /var/www/html/storage, so every deploy discarded the
        | images while keeping the PDFs.
        |
        | Both now resolve to the same place, on the persistent disk. This is
        | safe without a migration because BarcodeController::image() already
        | re-renders a label on the fly when the stored artifact is missing.
        |
        | The rest of the application was already written for the standard
        | layout and only this config disagreed with it. SupplierInvoiceService
        | stores invoice and payment-receipt attachments through this disk, and
        | SupplierInvoiceResource / SupplierInvoicePaymentResource hand the
        | client `asset('storage/'.$path)` — which nginx (root public/) resolves
        | through the public/storage symlink that docker/entrypoint.sh creates
        | with `artisan storage:link`, i.e. into storage/app/public. With root
        | '.' the bytes went to <app root>/supplier-invoices/… instead, so every
        | file_url the API returned was a guaranteed 404 *and* the file sat
        | outside the mounted volume, discarded on each deploy. The same is true
        | of the 11 Storage::url() sites for barcode labels and PDFs.
        |
        | `url` is not load-bearing for this disk: nothing generates a public
        | URL from it — callers build asset('storage/…') or stream through the
        | controller with an explicit Content-Type and Cache-Control.
        |
        */

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL'),
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
