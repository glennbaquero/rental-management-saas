<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users')->get()->map(fn (Role $role) => [
            'id'           => $role->id,
            'name'         => $role->name,
            'display_name' => $role->display_name,
            'permissions'  => $role->permissions ?? [],
            'users_count'  => $role->users_count,
        ]);

        $permissionCategories = [
            'organization' => ['view_settings', 'manage_settings', 'manage_users', 'manage_billing'],
            'properties'   => ['view', 'create', 'edit', 'delete'],
            'tenants'      => ['view', 'create', 'edit', 'delete'],
            'leases'       => ['view', 'create', 'edit', 'delete'],
            'invoices'     => ['view', 'create', 'edit', 'delete'],
            'payments'     => ['view', 'create', 'edit', 'delete'],
            'maintenance'  => ['view', 'create', 'edit', 'manage'],
            'reports'      => ['view', 'view_financial'],
        ];

        return Inertia::render('organization/Roles', [
            'roles'                => $roles,
            'permissionCategories' => $permissionCategories,
        ]);
    }
}
