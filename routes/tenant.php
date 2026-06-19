<?php

declare(strict_types=1);

use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationSettingsController;
use App\Http\Controllers\Organization\RoleController;
use App\Http\Controllers\Organization\UserManagementController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Public invitation acceptance routes (no auth required)
    Route::get('/accept-invitation/{token}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/accept-invitation/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');

    // Authenticated tenant routes
    Route::middleware(['auth', 'verified'])->group(function () {

        // Organization routes
        Route::prefix('organization')->name('organization.')->group(function () {

            // Settings
            Route::get('/settings', [OrganizationSettingsController::class, 'edit'])
                ->name('settings')
                ->middleware('permission:organization.view_settings');

            Route::patch('/settings', [OrganizationSettingsController::class, 'update'])
                ->name('settings.update')
                ->middleware('permission:organization.manage_settings');

            Route::post('/settings/logo', [OrganizationSettingsController::class, 'uploadLogo'])
                ->name('settings.logo')
                ->middleware('permission:organization.manage_settings');

            // Users
            Route::get('/users', [UserManagementController::class, 'index'])
                ->name('users')
                ->middleware('permission:organization.manage_users');

            Route::post('/users/invite', [UserManagementController::class, 'invite'])
                ->name('users.invite')
                ->middleware('permission:organization.manage_users');

            Route::patch('/users/{user}', [UserManagementController::class, 'update'])
                ->name('users.update')
                ->middleware('permission:organization.manage_users');

            Route::post('/users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])
                ->name('users.reset-password')
                ->middleware('permission:organization.manage_users');

            Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])
                ->name('users.toggle-status')
                ->middleware('permission:organization.manage_users');

            Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
                ->name('users.destroy')
                ->middleware('permission:organization.manage_users');

            // Roles & Permissions
            Route::get('/roles', [RoleController::class, 'index'])
                ->name('roles')
                ->middleware('permission:organization.view_settings');
        });
    });

});
