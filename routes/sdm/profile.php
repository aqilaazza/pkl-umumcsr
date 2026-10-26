<?php

use App\Http\Controllers\Sdm\ProfileController;
use App\Http\Controllers\Sdm\ResetPasswordController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'changePassword'])->name('profile.password');

    Route::get('/reset-password', [ResetPasswordController::class, 'index'])->name('reset_password');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('reset_password.store');
});
