<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController
{
    /**
     * Show the phone-based login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the user via phone + password.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('phone', 'password');
        $credentials['is_active'] = true;

        if (!Auth::attempt($credentials)) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => __('The provided credentials do not match an active account.')]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        return match ($user->role) {
            RoleEnum::ADMIN => redirect()->intended('/admin/dashboard'),
            RoleEnum::TEACHER => redirect()->intended('/teacher/dashboard'),
            RoleEnum::STUDENT => redirect()->intended('/student/dashboard'),
        };
    }
}
