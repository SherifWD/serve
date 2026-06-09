<?php

use App\Http\Controllers\MarketingInquiryPublicController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'marketing.home')->name('marketing.home');
Route::view('/contact', 'marketing.contact')->name('marketing.contact');
Route::post('/contact', [MarketingInquiryPublicController::class, 'store'])->name('marketing.contact.store');

Route::get('/app', function () {
    $baseUrl = rtrim(request()->getBaseUrl(), '/');

    if (! file_exists(public_path('dashboard/index.html')) && app()->environment('local')) {
        return redirect('http://127.0.0.1:5173/');
    }

    return redirect(($baseUrl !== '' ? $baseUrl : '').'/dashboard/');
})->name('marketing.app');
