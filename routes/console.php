<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('Stay focused!');
})->purpose('Display an inspiring quote');
