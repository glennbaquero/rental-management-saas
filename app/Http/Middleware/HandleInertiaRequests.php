<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            // Lazy closure: evaluated after tenancy initializes so $request->user()
            // resolves against the tenant DB, not the central DB.
            'auth' => fn () => [
                'user' => $request->user() ? array_merge(
                    $request->user()->toArray(),
                    [
                        'role' => $request->user()->role ? [
                            'id'           => $request->user()->role->id,
                            'name'         => $request->user()->role->name,
                            'display_name' => $request->user()->role->display_name,
                            'permissions'  => $request->user()->role->permissions ?? [],
                        ] : null,
                    ]
                ) : null,
            ],
            'toast' => $request->session()->get('toast'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
