<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'student_number' => 'required|string|exists:students,student_number|unique:users,student_number',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'student_number.exists' => 'No student record found for this student number.',
            'student_number.unique' => 'An account already exists for this student number.',
        ]);

        $user = User::create([
            'email' => $request->email,
            'password' => $request->password,
            'student_number' => $request->student_number,
            'status' => 'active',
        ]);

        $studentRole = \App\Models\Role::where('role_name', 'student')->first();
        if ($studentRole) {
            $user->roles()->attach($studentRole->role_id);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
