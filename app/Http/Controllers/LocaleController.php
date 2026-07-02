<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    private const ALLOWED_LOCALES = ['fa', 'en'];

    public function switch(string $locale): RedirectResponse
    {
        if (! in_array($locale, self::ALLOWED_LOCALES, true)) {
            abort(404);
        }

        session(['locale' => $locale]);

        return redirect()->back();
    }
}
