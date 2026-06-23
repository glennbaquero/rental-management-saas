<?php

declare(strict_types=1);

use App\Http\Controllers\Billing\BillingDashboardController;
use App\Http\Controllers\Billing\BillingSettingsController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\LateFeeController;
use App\Http\Controllers\Billing\PaymentController;
use App\Http\Controllers\Maintenance\MaintenanceAssignmentController;
use App\Http\Controllers\Maintenance\MaintenanceAttachmentController;
use App\Http\Controllers\Maintenance\MaintenanceCommentController;
use App\Http\Controllers\Maintenance\MaintenanceCostController;
use App\Http\Controllers\Maintenance\MaintenanceDashboardController;
use App\Http\Controllers\Maintenance\MaintenanceRatingController;
use App\Http\Controllers\Maintenance\MaintenanceTicketController;
use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationSettingsController;
use App\Http\Controllers\Organization\RoleController;
use App\Http\Controllers\Organization\UserManagementController;
use App\Http\Controllers\Property\AmenityController;
use App\Http\Controllers\Property\BuildingController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Property\PropertyImageController;
use App\Http\Controllers\Property\UnitController;
use App\Http\Controllers\Lease\LeaseController;
use App\Http\Controllers\Lease\LeaseDepositController;
use App\Http\Controllers\Lease\LeaseDocumentController;
use App\Http\Controllers\Lease\LeaseRenewalController;
use App\Http\Controllers\Lease\LeaseTerminationController;
use App\Http\Controllers\Tenant\EmergencyContactController;
use App\Http\Controllers\Tenant\RentalTenantController;
use App\Http\Controllers\Tenant\TenantFileController;
use App\Http\Controllers\Tenant\TenantIdDocumentController;
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

        // Billing
        Route::prefix('billing')->name('billing.')->middleware('permission:billing.view')->group(function () {

            Route::get('/dashboard', [BillingDashboardController::class, 'index'])->name('dashboard');

            Route::get('/settings', [BillingSettingsController::class, 'edit'])
                ->name('settings')
                ->middleware('permission:billing.manage_settings');

            Route::patch('/settings', [BillingSettingsController::class, 'update'])
                ->name('settings.update')
                ->middleware('permission:billing.manage_settings');

            Route::prefix('invoices')->name('invoices.')->group(function () {
                Route::get('/', [InvoiceController::class, 'index'])->name('index');

                Route::get('/create', [InvoiceController::class, 'create'])
                    ->name('create')
                    ->middleware('permission:billing.create_invoice');

                Route::post('/', [InvoiceController::class, 'store'])
                    ->name('store')
                    ->middleware('permission:billing.create_invoice');

                Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');

                Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])
                    ->name('destroy')
                    ->middleware('permission:billing.manage_invoice');

                Route::post('/{invoice}/send', [InvoiceController::class, 'send'])
                    ->name('send')
                    ->middleware('permission:billing.manage_invoice');

                Route::post('/{invoice}/record-payment', [PaymentController::class, 'store'])
                    ->name('record-payment')
                    ->middleware('permission:billing.record_payment');
            });

            Route::prefix('payments')->name('payments.')->group(function () {
                Route::get('/', [PaymentController::class, 'index'])->name('index');

                Route::patch('/{payment}/verify', [PaymentController::class, 'verify'])
                    ->name('verify')
                    ->middleware('permission:billing.verify_payment');

                Route::patch('/{payment}/reject', [PaymentController::class, 'reject'])
                    ->name('reject')
                    ->middleware('permission:billing.verify_payment');

                Route::post('/{payment}/proof', [PaymentController::class, 'uploadProof'])
                    ->name('proof')
                    ->middleware('permission:billing.record_payment');
            });

            Route::get('/late-fees', [LateFeeController::class, 'index'])->name('late-fees.index');
        });

        // Leases
        Route::prefix('leases')->name('leases.')->middleware('permission:leases.view')->group(function () {

            Route::get('/', [LeaseController::class, 'index'])->name('index');

            Route::get('/create', [LeaseController::class, 'create'])
                ->name('create')
                ->middleware('permission:leases.create');

            Route::post('/', [LeaseController::class, 'store'])
                ->name('store')
                ->middleware('permission:leases.create');

            Route::get('/{lease}', [LeaseController::class, 'show'])->name('show');

            Route::get('/{lease}/edit', [LeaseController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:leases.edit');

            Route::patch('/{lease}', [LeaseController::class, 'update'])
                ->name('update')
                ->middleware('permission:leases.edit');

            Route::delete('/{lease}', [LeaseController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:leases.delete');

            Route::post('/{lease}/renew', [LeaseRenewalController::class, 'store'])
                ->name('renewals.store')
                ->middleware('permission:leases.renew');

            Route::patch('/{lease}/renewals/{renewal}', [LeaseRenewalController::class, 'update'])
                ->name('renewals.update')
                ->middleware('permission:leases.renew');

            Route::post('/{lease}/terminate', [LeaseTerminationController::class, 'store'])
                ->name('terminate')
                ->middleware('permission:leases.terminate');

            Route::post('/{lease}/deposits', [LeaseDepositController::class, 'store'])
                ->name('deposits.store')
                ->middleware('permission:leases.edit');

            Route::patch('/{lease}/deposits/{deposit}', [LeaseDepositController::class, 'update'])
                ->name('deposits.update')
                ->middleware('permission:leases.edit');

            Route::post('/{lease}/documents', [LeaseDocumentController::class, 'store'])
                ->name('documents.store')
                ->middleware('permission:leases.edit');

            Route::delete('/{lease}/documents/{document}', [LeaseDocumentController::class, 'destroy'])
                ->name('documents.destroy')
                ->middleware('permission:leases.edit');
        });

        // Maintenance
        Route::prefix('maintenance')->name('maintenance.')->middleware('permission:maintenance.view')->group(function () {

            Route::get('/dashboard', [MaintenanceDashboardController::class, 'index'])->name('dashboard');

            Route::get('/', [MaintenanceTicketController::class, 'index'])->name('index');

            Route::get('/create', [MaintenanceTicketController::class, 'create'])
                ->name('create')
                ->middleware('permission:maintenance.create');

            Route::post('/', [MaintenanceTicketController::class, 'store'])
                ->name('store')
                ->middleware('permission:maintenance.create');

            Route::get('/{ticket}', [MaintenanceTicketController::class, 'show'])->name('show');

            Route::get('/{ticket}/edit', [MaintenanceTicketController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:maintenance.edit');

            Route::put('/{ticket}', [MaintenanceTicketController::class, 'update'])
                ->name('update')
                ->middleware('permission:maintenance.edit');

            Route::delete('/{ticket}', [MaintenanceTicketController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:maintenance.edit');

            // Assignments
            Route::post('/{ticket}/assign', [MaintenanceAssignmentController::class, 'store'])
                ->name('assign')
                ->middleware('permission:maintenance.manage');

            Route::put('/{ticket}/assignments/{assignment}', [MaintenanceAssignmentController::class, 'update'])
                ->name('assignments.update')
                ->middleware('permission:maintenance.manage');

            // Comments
            Route::post('/{ticket}/comments', [MaintenanceCommentController::class, 'store'])->name('comments.store');

            Route::delete('/{ticket}/comments/{comment}', [MaintenanceCommentController::class, 'destroy'])->name('comments.destroy');

            // Attachments
            Route::post('/{ticket}/attachments', [MaintenanceAttachmentController::class, 'store'])->name('attachments.store');

            Route::delete('/{ticket}/attachments/{attachment}', [MaintenanceAttachmentController::class, 'destroy'])->name('attachments.destroy');

            // Costs
            Route::post('/{ticket}/costs', [MaintenanceCostController::class, 'store'])
                ->name('costs.store')
                ->middleware('permission:maintenance.manage');

            Route::put('/{ticket}/costs/{cost}', [MaintenanceCostController::class, 'update'])
                ->name('costs.update')
                ->middleware('permission:maintenance.manage');

            Route::delete('/{ticket}/costs/{cost}', [MaintenanceCostController::class, 'destroy'])
                ->name('costs.destroy')
                ->middleware('permission:maintenance.manage');

            // Rating
            Route::post('/{ticket}/rate', [MaintenanceRatingController::class, 'store'])->name('rate');
        });

        // Rental Tenants
        Route::prefix('tenants')->name('tenants.')->middleware('permission:tenants.view')->group(function () {

            Route::get('/', [RentalTenantController::class, 'index'])->name('index');

            Route::get('/create', [RentalTenantController::class, 'create'])
                ->name('create')
                ->middleware('permission:tenants.create');

            Route::post('/', [RentalTenantController::class, 'store'])
                ->name('store')
                ->middleware('permission:tenants.create');

            Route::get('/{tenant}', [RentalTenantController::class, 'show'])->name('show');

            Route::get('/{tenant}/edit', [RentalTenantController::class, 'edit'])
                ->name('edit')
                ->middleware('permission:tenants.edit');

            Route::patch('/{tenant}', [RentalTenantController::class, 'update'])
                ->name('update')
                ->middleware('permission:tenants.edit');

            Route::delete('/{tenant}', [RentalTenantController::class, 'destroy'])
                ->name('destroy')
                ->middleware('permission:tenants.delete');

            // ID Documents
            Route::post('/{tenant}/documents', [TenantIdDocumentController::class, 'store'])
                ->name('documents.store')
                ->middleware('permission:tenants.edit');

            Route::patch('/{tenant}/documents/{document}', [TenantIdDocumentController::class, 'update'])
                ->name('documents.update')
                ->middleware('permission:tenants.edit');

            Route::delete('/{tenant}/documents/{document}', [TenantIdDocumentController::class, 'destroy'])
                ->name('documents.destroy')
                ->middleware('permission:tenants.edit');

            // Emergency Contacts
            Route::post('/{tenant}/emergency-contacts', [EmergencyContactController::class, 'store'])
                ->name('emergency-contacts.store')
                ->middleware('permission:tenants.edit');

            Route::patch('/{tenant}/emergency-contacts/{contact}', [EmergencyContactController::class, 'update'])
                ->name('emergency-contacts.update')
                ->middleware('permission:tenants.edit');

            Route::delete('/{tenant}/emergency-contacts/{contact}', [EmergencyContactController::class, 'destroy'])
                ->name('emergency-contacts.destroy')
                ->middleware('permission:tenants.edit');

            // Tenant Files
            Route::post('/{tenant}/files', [TenantFileController::class, 'store'])
                ->name('files.store')
                ->middleware('permission:tenants.edit');

            Route::delete('/{tenant}/files/{file}', [TenantFileController::class, 'destroy'])
                ->name('files.destroy')
                ->middleware('permission:tenants.edit');
        });
    });

});
