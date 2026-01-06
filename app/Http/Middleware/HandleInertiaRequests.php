<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
{
    // Buscamos si el usuario logueado tiene una barbería (tenant)
    $tenant = $request->user() ? $request->user()->tenant : null;

    return [
        ...parent::share($request),
        'auth' => [
            'user' => $request->user(),
            // Pasamos la configuración del Tenant
            'brand' => [
                // Si existe el tenant, usamos su color. Si no, default.
                'color' => $tenant ? $tenant->primary_color : '#d97706',
                'name'  => $tenant ? $tenant->name : 'Mi Barbería',
            ],
        ],
    ];
}
}
