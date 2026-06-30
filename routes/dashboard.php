<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

Route::get('/teacher/dashboard', function () {
    return 'Teacher Dashboard';
})->middleware(['auth', 'role:teacher']);

Route::get('/student/dashboard', function () {
    return 'Student Dashboard';
})->middleware(['auth', 'role:student']);
