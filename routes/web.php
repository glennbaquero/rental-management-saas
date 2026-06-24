<?php

use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\InitializeTenancyBySubdomain::class)->group(function () {

    Route::redirect('/', '/login')->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('/register', [TenantRegistrationController::class, 'create'])->name('register');
        Route::post('/register', [TenantRegistrationController::class, 'store']);
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

});

require __DIR__.'/settings.php';
