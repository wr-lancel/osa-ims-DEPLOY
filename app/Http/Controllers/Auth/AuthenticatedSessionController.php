<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ModuleAuthorizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();
        $user->load('roles');

        // Determine redirect route based on user's roles and module access
        $moduleAuth = app(ModuleAuthorizationService::class);
        $hasAdminAccess = $moduleAuth->hasAccess($user, ModuleAuthorizationService::MODULE_DASHBOARD);

        // Always redirect to change-password first if required
        if ($user->must_change_password) {
            return redirect()->route('onboarding.change-password');
        }

        $redirectRoute = match (true) {
            $hasAdminAccess => 'admin.dashboard',
            $user->hasRole('student') => 'student.dashboard',
            default => 'admin.dashboard',
        };

        return redirect()->intended(route($redirectRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
