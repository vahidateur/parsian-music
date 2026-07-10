<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * Accepts one or more comma-separated roles: middleware('role:admin,super_admin')
     *
     * Rules:
     *  1. Unauthenticated → 403
     *  2. Inactive account → logout + redirect to login with message
     *  3. Super Admin → bypass all role checks
     *  4. Role not in allowed list → 403
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // Deactivated account — force logout regardless of role
        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('error', 'حساب کاربری شما غیرفعال شده است. با مدیر سیستم تماس بگیرید.');
        }

        // Super Admin bypasses every role gate
        if ($user->role === RoleEnum::SUPER_ADMIN) {
            return $next($request);
        }

        // Flatten comma-separated syntax: role:admin,super_admin
        $allowed = collect($roles)
            ->flatMap(fn (string $r) => explode(',', $r))
            ->map(fn (string $r) => trim($r))
            ->filter()
            ->values();

        if (! $allowed->contains($user->role->value)) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
