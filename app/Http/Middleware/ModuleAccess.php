<?php

namespace App\Http\Middleware;

use App\Services\ModuleAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuleAccess
{
    public function __construct(
        private ModuleAuthorizationService $moduleAuth
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$modules
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check if user has access to any of the required modules
        if (!$this->moduleAuth->hasAnyAccess($user, $modules)) {
            abort(403, 'You do not have access to this module.');
        }

        return $next($request);
    }
}

