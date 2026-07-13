<?php

use App\Http\Controllers\Auth\PhonePasswordResetController;
use App\Modules\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLoginForm'])
    ->name('login');

Route::post('/login', [AuthController::class, 'login']);

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// ── Phone-based password reset (guests only) ─────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password',  [PhonePasswordResetController::class, 'create'])
        ->name('password.phone.request');

    Route::post('/forgot-password', [PhonePasswordResetController::class, 'store'])
        ->name('password.phone.send');

    Route::get('/reset-password',   [PhonePasswordResetController::class, 'showResetForm'])
        ->name('password.phone.reset.form');

    Route::post('/reset-password',  [PhonePasswordResetController::class, 'reset'])
        ->name('password.phone.reset');
});
