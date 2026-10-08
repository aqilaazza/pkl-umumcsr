<?php

use App\Http\Controllers\Sdm\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
});
