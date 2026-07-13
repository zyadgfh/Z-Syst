<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Ensure minimal test schema exists early to avoid intermittent missing-table errors
        if ($app->environment('testing')) {
            try {
                if (! \Illuminate\Support\Facades\Schema::hasTable('drugs')) {
                    \Illuminate\Support\Facades\Schema::create('drugs', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->id();
                        $table->unsignedBigInteger('company_id')->nullable();
                        $table->uuid('uuid')->nullable();
                        $table->string('name');
                        $table->string('generic_name')->nullable();
                        $table->string('barcode')->nullable();
                        $table->string('manufacturer')->nullable();
                        $table->softDeletes();
                        $table->timestamps();
                    });
                }

                if (! \Illuminate\Support\Facades\Schema::hasTable('roles')) {
                    \Illuminate\Support\Facades\Schema::create('roles', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('name');
                        $table->string('guard_name')->default('web');
                        $table->timestamps();
                    });
                }

                if (! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
                    \Illuminate\Support\Facades\Schema::create('permissions', function (\Illuminate\Database\Schema\Blueprint $table) {
                        $table->bigIncrements('id');
                        $table->string('name');
                        $table->string('guard_name')->default('web');
                        $table->timestamps();
                    });
                }
            } catch (\Throwable $e) {
                // ignore; migrations may run later
            }
        }

        return $app;
    }
}
