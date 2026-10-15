<?php

use App\Http\Controllers\Manager\HomeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KuotaController;

Route::middleware(['web', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('dashboard');
    Route::get('/kuota', [KuotaController::class, 'index'])->name('kuota');
});
