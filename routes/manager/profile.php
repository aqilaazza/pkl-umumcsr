<?php

use App\Http\Controllers\Manager\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('profile', [ProfileController::class, 'update']);
});
