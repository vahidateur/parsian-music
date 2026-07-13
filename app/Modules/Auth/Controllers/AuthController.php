<?php

namespace App\Modules\Auth\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Models\User;
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
        $phone = $request->input('phone');
        $password = $request->input('password');

        // پیدا کن کاربر توسط شماره تلفن
        $user = User::where('phone', $phone)->first();

        // اگر کاربر قفل شده
        if ($user && $user->isLocked()) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'حساب شما به دلیل چند تلاش ناموفق برای 30 دقیقه قفل شده است.']);
        }

        // تلاش برای لاگین
        $credentials = ['phone' => $phone, 'password' => $password, 'is_active' => true];

        if (!Auth::attempt($credentials)) {
            // لاگین ناموفق
            if ($user) {
                $user->incrementLoginAttempts();
            }

            // ثبت لاگین ناموفق
            LoginLog::create([
                'user_id' => $user?->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'login_at' => now(),
                'success' => false,
            ]);

            // پیام بر اساس تعداد تلاش
            $message = 'شماره تلفن یا رمز عبور اشتباه است.';
            if ($user) {
                $remaining = 3 - $user->login_attempts;
                if ($remaining > 0 && $remaining <= 2) {
                    $message .= " ({$remaining} تلاش باقی‌مانده)";
                }
            }

            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => $message]);
        }

        // لاگین موفق
        $request->session()->regenerate();

        $user = $request->user();
        $user->resetLoginAttempts();

        // ثبت لاگین موفق
        LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'login_at' => now(),
            'success' => true,
        ]);

        return match ($user->role) {
            RoleEnum::SUPER_ADMIN => redirect()->intended('/admin/dashboard'),
            RoleEnum::ADMIN       => redirect()->intended('/admin/dashboard'),
            RoleEnum::TEACHER     => redirect()->intended('/teacher/dashboard'),
            RoleEnum::STUDENT     => redirect()->intended('/student/dashboard'),
        };
    }
}
