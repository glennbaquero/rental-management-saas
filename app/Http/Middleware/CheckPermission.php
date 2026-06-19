<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role) {
            abort(403, 'Unauthorized.');
        }

        if ($user->isOwner()) {
            return $next($request);
        }

        $userPermissions = $user->role->permissions ?? [];

        foreach ($permissions as $permission) {
            if (in_array($permission, $userPermissions, true)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have permission to perform this action.');
    }
}
