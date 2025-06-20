<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Module API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/core')->name('api.core.')->group(function () {
    Route::get('/', function () {
        return view('layouts.app');
    })->name('home');
});