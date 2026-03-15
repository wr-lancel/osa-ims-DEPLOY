<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Step 1: Force password change
        if ($user->must_change_password) {
            return redirect()->route('onboarding.change-password');
        }

        // Step 2: Force profile completion for students
        if ($user->student_number) {
            $student = $user->student;
            if ($student && !$student->profile_completed) {
                return redirect()->route('onboarding.complete-profile');
            }
        }

        return $next($request);
    }
}
