<?php

use Illuminate\Support\Facades\Route;
use Modules\Landing\App\Http\Controllers\Admin as Admin;


Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::resource('features', Admin\AcnooFeatureController::class);
    Route::post('features/filter', [Admin\AcnooFeatureController::class, 'acnooFilter'])->name('features.filter');
    Route::post('features/status/{id}', [Admin\AcnooFeatureController::class,'status'])->name('features.status');
    Route::post('features/delete-all', [Admin\AcnooFeatureController::class, 'deleteAll'])->name('features.delete-all');
    Route::get('features-excel', [ADMIN\AcnooFeatureController::class, 'exportExcel'])->name('features.excel');
    Route::get('features-csv', [ADMIN\AcnooFeatureController::class, 'exportCsv'])->name('features.csv');


    Route::resource('blogs', Admin\AcnooBlogController::class);
    Route::post('blogs/filter', [Admin\AcnooBlogController::class, 'acnooFilter'])->name('blogs.filter');
    Route::post('blogs/status/{id}', [Admin\AcnooBlogController::class,'status'])->name('blogs.status');
    Route::post('blogs/delete-all', [Admin\AcnooBlogController::class, 'deleteAll'])->name('blogs.delete-all');
    Route::get('blogs/comments/{id}', [ADMIN\AcnooBlogController::class, 'filterComment'])->name('blogs.filter.comment');
    Route::get('blogs-excel', [ADMIN\AcnooBlogController::class, 'exportExcel'])->name('blogs.excel');
    Route::get('blogs-csv', [ADMIN\AcnooBlogController::class, 'exportCsv'])->name('blogs.csv');


    //Comment Controller
    Route::resource('comments', Admin\AcnooCommentController::class);
    Route::post('comments/filter/{id}', [Admin\AcnooCommentController::class, 'acnooFilter'])->name('comments.filter');
    Route::post('comments/delete-all', [ADMIN\AcnooCommentController::class, 'deleteAll'])->name('comments.delete-all');

    // Testimonial
    Route::resource('testimonials', Admin\AcnooTestimonialController::class);
    Route::post('testimonials/filter', [Admin\AcnooTestimonialController::class, 'acnooFilter'])->name('testimonials.filter');
    Route::post('testimonials/status/{id}', [Admin\AcnooTestimonialController::class,'status'])->name('testimonials.status');
    Route::post('testimonials/delete-all', [Admin\AcnooTestimonialController::class, 'deleteAll'])->name('testimonials.delete-all');
    Route::get('testimonials-excel', [ADMIN\AcnooTestimonialController::class, 'exportExcel'])->name('testimonials.excel');
    Route::get('testimonials-csv', [ADMIN\AcnooTestimonialController::class, 'exportCsv'])->name('testimonials.csv');


    //Interfaces
    Route::resource('interfaces', Admin\AcnooInterfaceController::class);
    Route::post('interfaces/filter', [Admin\AcnooInterfaceController::class, 'acnooFilter'])->name('interfaces.filter');
    Route::post('interfaces/status/{id}', [Admin\AcnooInterfaceController::class,'status'])->name('interfaces.status');
    Route::post('interfaces/delete-all', [Admin\AcnooInterfaceController::class, 'deleteAll'])->name('interfaces.delete-all');
    Route::get('interfaces-excel', [ADMIN\AcnooInterfaceController::class, 'exportExcel'])->name('interfaces.excel');
    Route::get('interfaces-csv', [ADMIN\AcnooInterfaceController::class, 'exportCsv'])->name('interfaces.csv');

    //Messages
    Route::resource('messages', Admin\AcnooMessageController::class);
    Route::post('messages/filter', [Admin\AcnooMessageController::class, 'acnooFilter'])->name('messages.filter');
    Route::post('messages/delete-all', [Admin\AcnooMessageController::class, 'deleteAll'])->name('messages.delete-all');
    Route::get('messages-excel', [ADMIN\AcnooMessageController::class, 'exportExcel'])->name('messages.excel');
    Route::get('messages-csv', [ADMIN\AcnooMessageController::class, 'exportCsv'])->name('messages.csv');


    // Term And Condition Controller
    Route::resource('term-conditions', ADMIN\AcnooTermConditionController::class)->only('index', 'store');

    // Privacy Policy Controller
    Route::resource('privacy-policy', ADMIN\AcnooPrivacyPloicyController::class)->only('index', 'store');

    // Website settings
    Route::resource('website-settings',Admin\AcnooWebSettingController::class);

});

