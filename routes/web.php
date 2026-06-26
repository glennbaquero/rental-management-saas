<?php

use App\Http\Controllers\Auth\TenantRegistrationController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use App\Http\Controllers\Stripe\WebhookMonitorController;
use Illuminate\Support\Facades\Route;

// Stripe webhook — lives on central domain, no CSRF, no tenant middleware
Route::post('stripe/webhook', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');

Route::middleware(\App\Http\Middleware\InitializeTenancyBySubdomain::class)->group(function () {

    Route::redirect('/', '/login')->name('home');

    Route::middleware('guest')->group(function () {
        Route::get('/register', [TenantRegistrationController::class, 'create'])->name('register');
        Route::post('/register', [TenantRegistrationController::class, 'store']);
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Stripe webhook monitor
        Route::get('stripe/webhooks', [WebhookMonitorController::class, 'index'])->name('stripe.webhooks.index');
        Route::post('stripe/webhooks/{event}/retry', [WebhookMonitorController::class, 'retry'])->name('stripe.webhooks.retry');
    });

});

require __DIR__.'/settings.php';
