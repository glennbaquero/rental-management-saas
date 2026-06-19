<?php

namespace App\Http\Middleware;

use Closure;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

/**
 * Initializes tenancy by full domain, but passes through silently on
 * central domains. Safe to apply globally (Fortify, web.php, etc.).
 */
class InitializeTenancyBySubdomain extends InitializeTenancyByDomain
{
    public function handle($request, Closure $next)
    {
        $host = $request->getHost();

        if (in_array($host, config('tenancy.central_domains'), true)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
