<?php

use App\Http\Controllers\Manager\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
});
