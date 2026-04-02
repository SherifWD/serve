<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (file_exists(public_path('dashboard/index.html'))) {
        $baseUrl = rtrim(request()->getBaseUrl(), '/');

        return redirect(($baseUrl !== '' ? $baseUrl : '').'/dashboard/');
    }

    return view('welcome');
});
