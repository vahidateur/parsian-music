<?php

namespace App\Http\Middleware;

use App\Enums\RoleEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(Response::HTTP_FORBIDDEN);
        }

        if ($user->role->value !== $role) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
