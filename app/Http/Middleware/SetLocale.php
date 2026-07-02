<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const ALLOWED_LOCALES = ['fa', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', 'fa');

        if (! in_array($locale, self::ALLOWED_LOCALES, true)) {
            $locale = 'fa';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
