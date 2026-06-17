<?php

use App\Http\Controllers\Auth\TenantRegistrationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [TenantRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [TenantRegistrationController::class, 'store']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
