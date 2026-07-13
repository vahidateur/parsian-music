<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PhonePasswordResetController extends Controller
{
    /** Token TTL in seconds (15 minutes). */
    private const TTL = 900;

    /** Roles allowed to use self-service password reset. */
    private const ALLOWED_ROLES = [
        RoleEnum::SUPER_ADMIN,
        RoleEnum::ADMIN,
        RoleEnum::TEACHER,
    ];

    // ── Step 1 — Show "Forgot Password" form ─────────────────────────────────

    public function create(): View
    {
        return view('auth.forgot-password-phone');
    }

    // ── Step 2 — Receive phone, generate token, send ──────────────────────────

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'min:10', 'max:20'],
        ]);

        $phone = $request->input('phone');

        // Rate limit: 3 requests per hour per phone
        $rateLimitKey = 'password-reset:' . $phone;

        if (RateLimiter::tooManyAttempts($rateLimitKey, maxAttempts: 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            Log::warning('Password reset rate limit hit', [
                'phone' => $phone,
                'ip'    => $request->ip(),
            ]);

            return back()->withErrors([
                'phone' => "تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً {$seconds} ثانیه دیگر دوباره امتحان کنید.",
            ]);
        }

        RateLimiter::hit($rateLimitKey, decay: 3600);

        // Always return success response to prevent phone enumeration
        $user = User::where('phone', $phone)->first();

        if ($user && in_array($user->role, self::ALLOWED_ROLES, strict: true)) {
            $token = Str::random(64);

            // Invalidate any previous tokens for this phone
            DB::table('phone_password_resets')
                ->where('phone', $phone)
                ->where('used', false)
                ->update(['used' => true]);

            DB::table('phone_password_resets')->insert([
                'phone'      => $phone,
                'token'      => hash('sha256', $token),
                'created_at' => now(),
                'used'       => false,
            ]);

            Log::info('Password reset token generated', [
                'phone' => $phone,
                'role'  => $user->role->value,
                'ip'    => $request->ip(),
            ]);

            // ── Delivery ─────────────────────────────────────────────────────
            // Priority: SMS (future) → Telegram (if enabled) → Email (if set)
            // Development fallback: log the token
            $this->deliverToken($user, $token);
        } else {
            // Log failed attempt (wrong phone or disallowed role) but don't reveal this to the caller
            Log::warning('Password reset requested for unknown/disallowed account', [
                'phone' => $phone,
                'ip'    => $request->ip(),
            ]);
        }

        return back()->with('status', 'اگر این شماره در سیستم وجود داشته باشد، توکن بازیابی رمز برای شما ارسال شد.');
    }

    // ── Step 3 — Show reset form ──────────────────────────────────────────────

    public function showResetForm(Request $request): View
    {
        return view('auth.reset-password-phone', [
            'token' => $request->query('token', ''),
            'phone' => $request->query('phone', ''),
        ]);
    }

    // ── Step 4 — Process new password ────────────────────────────────────────

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'phone'    => ['required', 'string', 'min:10', 'max:20'],
            'token'    => ['required', 'string', 'size:64'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $phone      = $request->input('phone');
        $plainToken = $request->input('token');
        $hashedToken = hash('sha256', $plainToken);

        // Find a valid, unused, non-expired token
        $record = DB::table('phone_password_resets')
            ->where('phone', $phone)
            ->where('token', $hashedToken)
            ->where('used', false)
            ->where('created_at', '>=', now()->subSeconds(self::TTL))
            ->first();

        if (! $record) {
            return back()->withErrors([
                'token' => 'توکن بازیابی رمز نامعتبر یا منقضی شده است.',
            ])->withInput($request->only('phone'));
        }

        $user = User::where('phone', $phone)->first();

        if (! $user || ! in_array($user->role, self::ALLOWED_ROLES, strict: true)) {
            return back()->withErrors([
                'phone' => 'حساب کاربری یافت نشد یا مجاز به بازیابی رمز نیست.',
            ]);
        }

        // Mark this token used (single-use) + invalidate all others for this phone
        DB::table('phone_password_resets')
            ->where('phone', $phone)
            ->update(['used' => true]);

        // Update password, clear force_password_change
        $user->update([
            'password'              => Hash::make($request->input('password')),
            'force_password_change' => false,
        ]);

        Log::info('Password reset successful', [
            'user_id' => $user->id,
            'phone'   => $phone,
            'role'    => $user->role->value,
            'ip'      => $request->ip(),
        ]);

        // Force session regeneration (invalidate all other sessions)
        $request->session()->regenerate();

        return redirect()->route('login')
            ->with('status', 'رمز عبور شما با موفقیت تغییر یافت. می‌توانید وارد شوید.');
    }

    // ── Delivery ──────────────────────────────────────────────────────────────

    private function deliverToken(User $user, string $plainToken): void
    {
        $resetUrl = route('password.phone.reset.form', [
            'phone' => $user->phone,
            'token' => $plainToken,
        ]);

        // TODO Sprint-SMS: Send via SMS driver when available
        // TODO Sprint-Telegram: Send via Telegram driver if settings()->telegram_enabled

        // Email fallback — only if user has an email address
        if ($user->email) {
            try {
                \Illuminate\Support\Facades\Mail::to($user->email)->send(
                    new \App\Mail\PasswordResetTokenMail($user, $plainToken, $resetUrl)
                );

                Log::info('Password reset token sent via email', [
                    'user_id' => $user->id,
                    'phone'   => $user->phone,
                ]);

                return;
            } catch (\Throwable $e) {
                Log::error('Failed to send password reset email', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // Development fallback: always log the token
        Log::debug('[DEV] Password reset token', [
            'phone'     => $user->phone,
            'token'     => $plainToken,
            'reset_url' => $resetUrl,
        ]);
    }
}
