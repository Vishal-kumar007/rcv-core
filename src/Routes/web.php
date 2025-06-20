<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Module Web Routes
|--------------------------------------------------------------------------
*/


Route::prefix('core')->name('core.')->group(function () {
    Route::get('/', function () {
        return view('layouts.app');
    })->name('home');
});