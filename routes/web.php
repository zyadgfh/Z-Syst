<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('landing');
});

Route::get('/features', function () {
    return view('features');
});

Route::get('/docs', function () {
    return view('docs.index', [
        'pages' => [
            'PharmaSync Feature List' => '/docs/pharmasync-feature-list',
        ],
    ]);
});

Route::get('/docs/pharmasync-feature-list', function () {
    $path = base_path('docs/pharmasync-feature-list.md');

    if (! File::exists($path)) {
        abort(404);
    }

    $markdown = File::get($path);
    $html = Str::markdown($markdown);

    return view('docs', ['content' => $html]);
});

Route::get('/api/info', function () {
    return response()->json(['message' => 'Z-Syst API', 'version' => '1.0.0']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Dashboard']);
    });
});
