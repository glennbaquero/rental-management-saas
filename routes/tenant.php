<?php

declare(strict_types=1);

use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationSettingsController;
use App\Http\Controllers\Organization\RoleController;
use App\Http\Controllers\Organization\UserManagementController;
use App\Http\Controllers\Property\AmenityController;
use App\Http\Controllers\Property\BuildingController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Property\PropertyImageController;
use App\Http\Controllers\Property\UnitController;
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

        // Properties
        Route::prefix('properties')->name('properties.')->middleware('permission:properties.view')->group(function () {

            Route::get('/', [PropertyController::class, 'index'])->name('index');

            Route::get('/create', [PropertyController::class, 'create'])
                ->name('create')
                ->middleware('permission:properties.create');

            Route::post('/', [PropertyController::class, 'store'])
                ->name('store')
                ->middleware('permission:properties.create');

            Route::get('/{property}', [PropertyController::class, 'show'])->name('show');

            Route::get('/{property}/edit', [PropertyController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:properties.edit');

            Route::patch('/{property}', [PropertyController::class, 'update'])
                ->name('update')
                ->middleware('permission:properties.edit');

            Route::delete('/{property}', [PropertyController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:properties.delete');

            // Buildings
            Route::post('/{property}/buildings', [BuildingController::class, 'store'])
                ->name('buildings.store')
                ->middleware('permission:properties.edit');

            Route::patch('/{property}/buildings/{building}', [BuildingController::class, 'update'])
                ->name('buildings.update')
                ->middleware('permission:properties.edit');

            Route::delete('/{property}/buildings/{building}', [BuildingController::class, 'destroy'])
                ->name('buildings.destroy')
                ->middleware('permission:properties.edit');

            // Units
            Route::post('/{property}/units', [UnitController::class, 'store'])
                ->name('units.store')
                ->middleware('permission:properties.edit');

            Route::patch('/{property}/units/{unit}', [UnitController::class, 'update'])
                ->name('units.update')
                ->middleware('permission:properties.edit');

            Route::delete('/{property}/units/{unit}', [UnitController::class, 'destroy'])
                ->name('units.destroy')
                ->middleware('permission:properties.edit');

            // Amenities on property
            Route::post('/{property}/amenities/sync', [AmenityController::class, 'syncProperty'])
                ->name('amenities.sync')
                ->middleware('permission:properties.edit');

            // Unit amenities
            Route::post('/{property}/units/{unit}/amenities/sync', [AmenityController::class, 'syncUnit'])
                ->name('units.amenities.sync')
                ->middleware('permission:properties.edit');

            // Gallery
            Route::post('/{property}/images', [PropertyImageController::class, 'store'])
                ->name('images.store')
                ->middleware('permission:properties.edit');

            Route::delete('/{property}/images/{image}', [PropertyImageController::class, 'destroy'])
                ->name('images.destroy')
                ->middleware('permission:properties.edit');

            Route::patch('/{property}/images/reorder', [PropertyImageController::class, 'reorder'])
                ->name('images.reorder')
                ->middleware('permission:properties.edit');
        });

        // Amenities management
        Route::prefix('amenities')->name('amenities.')->middleware('permission:properties.edit')->group(function () {
            Route::post('/', [AmenityController::class, 'store'])->name('store');
            Route::delete('/{amenity}', [AmenityController::class, 'destroy'])->name('destroy');
        });
    });

});
