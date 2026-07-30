<?php

use Illuminate\Support\Facades\Route;
use Modules\Landing\App\Http\Controllers\Admin as Admin;


Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::resource('features', Admin\ZSystFeatureController::class);
    Route::post('features/filter', [Admin\ZSystFeatureController::class, 'zsystFilter'])->name('features.filter');
    Route::post('features/status/{id}', [Admin\ZSystFeatureController::class,'status'])->name('features.status');
    Route::post('features/delete-all', [Admin\ZSystFeatureController::class, 'deleteAll'])->name('features.delete-all');
    Route::get('features-excel', [ADMIN\ZSystFeatureController::class, 'exportExcel'])->name('features.excel');
    Route::get('features-csv', [ADMIN\ZSystFeatureController::class, 'exportCsv'])->name('features.csv');


    Route::resource('blogs', Admin\ZSystBlogController::class);
    Route::post('blogs/filter', [Admin\ZSystBlogController::class, 'zsystFilter'])->name('blogs.filter');
    Route::post('blogs/status/{id}', [Admin\ZSystBlogController::class,'status'])->name('blogs.status');
    Route::post('blogs/delete-all', [Admin\ZSystBlogController::class, 'deleteAll'])->name('blogs.delete-all');
    Route::get('blogs/comments/{id}', [ADMIN\ZSystBlogController::class, 'filterComment'])->name('blogs.filter.comment');
    Route::get('blogs-excel', [ADMIN\ZSystBlogController::class, 'exportExcel'])->name('blogs.excel');
    Route::get('blogs-csv', [ADMIN\ZSystBlogController::class, 'exportCsv'])->name('blogs.csv');


    //Comment Controller
    Route::resource('comments', Admin\ZSystCommentController::class);
    Route::post('comments/filter/{id}', [Admin\ZSystCommentController::class, 'zsystFilter'])->name('comments.filter');
    Route::post('comments/delete-all', [ADMIN\ZSystCommentController::class, 'deleteAll'])->name('comments.delete-all');

    // Testimonial
    Route::resource('testimonials', Admin\ZSystTestimonialController::class);
    Route::post('testimonials/filter', [Admin\ZSystTestimonialController::class, 'zsystFilter'])->name('testimonials.filter');
    Route::post('testimonials/status/{id}', [Admin\ZSystTestimonialController::class,'status'])->name('testimonials.status');
    Route::post('testimonials/delete-all', [Admin\ZSystTestimonialController::class, 'deleteAll'])->name('testimonials.delete-all');
    Route::get('testimonials-excel', [ADMIN\ZSystTestimonialController::class, 'exportExcel'])->name('testimonials.excel');
    Route::get('testimonials-csv', [ADMIN\ZSystTestimonialController::class, 'exportCsv'])->name('testimonials.csv');


    //Interfaces
    Route::resource('interfaces', Admin\ZSystInterfaceController::class);
    Route::post('interfaces/filter', [Admin\ZSystInterfaceController::class, 'zsystFilter'])->name('interfaces.filter');
    Route::post('interfaces/status/{id}', [Admin\ZSystInterfaceController::class,'status'])->name('interfaces.status');
    Route::post('interfaces/delete-all', [Admin\ZSystInterfaceController::class, 'deleteAll'])->name('interfaces.delete-all');
    Route::get('interfaces-excel', [ADMIN\ZSystInterfaceController::class, 'exportExcel'])->name('interfaces.excel');
    Route::get('interfaces-csv', [ADMIN\ZSystInterfaceController::class, 'exportCsv'])->name('interfaces.csv');

    //Messages
    Route::resource('messages', Admin\ZSystMessageController::class);
    Route::post('messages/filter', [Admin\ZSystMessageController::class, 'zsystFilter'])->name('messages.filter');
    Route::post('messages/delete-all', [Admin\ZSystMessageController::class, 'deleteAll'])->name('messages.delete-all');
    Route::get('messages-excel', [ADMIN\ZSystMessageController::class, 'exportExcel'])->name('messages.excel');
    Route::get('messages-csv', [ADMIN\ZSystMessageController::class, 'exportCsv'])->name('messages.csv');


    // Term And Condition Controller
    Route::resource('term-conditions', ADMIN\ZSystTermConditionController::class)->only('index', 'store');

    // Privacy Policy Controller
    Route::resource('privacy-policy', ADMIN\ZSystPrivacyPloicyController::class)->only('index', 'store');

    // Website settings
    Route::resource('website-settings',Admin\ZSystWebSettingController::class);

});

