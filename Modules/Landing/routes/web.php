<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Modules\Landing\App\Http\Controllers\Web;

Route::get('/', [Web\WebController::class, 'index'])->name('home');

// Language Switcher
Route::post('/locale/switch', function () {
    $locale = request()->input('locale', 'en');
    if (!in_array($locale, ['en', 'ar'])) {
        abort(400, 'Unsupported locale.');
    }
    session(['lang' => $locale]);
    app()->setLocale($locale);
    return redirect()->back();
})->name('locale.switch');
Route::get('/about-us', [Web\AboutController::class, 'index'])->name('about.index');
Route::get('/terms-conditions', [Web\TermServiceController::class, 'index'])->name('term.index');
Route::get('/privacy-policy', [Web\PolicyController::class, 'index'])->name('policy.index');
Route::get('/contact-us', [Web\ContactController::class, 'index'])->name('contact.index');
Route::post('/contact/store', [Web\ContactController::class, 'store'])->name('contact.store');
Route::resource('blogs', Web\BlogController::class)->only('index', 'show', 'store');

// Public Product Catalog
Route::get('/catalog', [Web\CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{id}', [Web\CatalogController::class, 'show'])->name('catalog.show');
Route::get('/plans', [Web\PlanController::class, 'index'])->name('plan.index');
Route::get('/filter-blogs-by-tag', [Web\BlogController::class, 'filterBlogsByTag'])->name('frontend.tag.filter');

Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');

    return back()->with('success', __('Cache has been cleared.'));
});

Route::get('/publish', function () {
    Artisan::call('cache:clear');
    Artisan::call('module:migrate Landing');
    Artisan::call('module:seed Landing');
    Artisan::call('module:publish Landing');

    return 'success';
});

Route::get('/reset', function () {
    Artisan::call('cache:clear');
    Artisan::call('migrate:fresh --seed');

    return back()->with('success', __('Restart.'));
});
