<?php

namespace App\Http\Middleware;

use App\Services\ModuleAuthorizationService;
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
        $user = $request->user();
        
        if ($user) {
            $user->load('roles');
        }

        // Resolve ModuleAuthorizationService from container
        $moduleAuth = app(ModuleAuthorizationService::class);
        $accessibleModules = $user 
            ? $moduleAuth->getAccessibleModules($user)
            : [];

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'user_id' => $user->user_id,
                    'email' => $user->email,
                    'status' => $user->status,
                    'display_name' => $user->display_name,
                    'roles' => $user->roles->pluck('role_name')->toArray(),
                ] : null,
                'accessible_modules' => $accessibleModules,
            ],
        ];
    }
}
