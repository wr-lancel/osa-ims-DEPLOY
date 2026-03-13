<?php

namespace App\Http\Middleware;

use App\Services\PublicationAuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicationAccess
{
    public function __construct(
        private PublicationAuthorizationService $publicationAuth
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (!$this->publicationAuth->canManagePublications($user)) {
            abort(403, 'You do not have access to the publications module.');
        }

        return $next($request);
    }
}
