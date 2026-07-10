<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

// ── Admin ─────────────────────────────────────────────────────────────────────
Route::get('/admin/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])
    ->name('admin.dashboard');

// ── Teacher portal ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:teacher'])
    ->prefix('teacher')
    ->name('teacher.')
    ->group(function () {
        Route::get('/dashboard',              [TeacherDashboardController::class, 'index'])   ->name('dashboard');
        Route::get('/schedule',               [TeacherDashboardController::class, 'schedule'])->name('schedule');
        Route::get('/students',               [TeacherDashboardController::class, 'students'])->name('students');
        Route::get('/attendance/{session}',   [TeacherDashboardController::class, 'attendance'])->name('attendance');
        Route::post('/attendance/{session}',  [TeacherDashboardController::class, 'saveAttendance'])->name('attendance.save');
        Route::get('/calendar',              [TeacherDashboardController::class, 'calendar'])      ->name('calendar');
        Route::get('/notifications',         [TeacherDashboardController::class, 'notifications']) ->name('notifications');
    });

// ── Student portal ────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {
        Route::get('/dashboard',       [StudentDashboardController::class, 'index'])         ->name('dashboard');
        Route::get('/classes',         [StudentDashboardController::class, 'classes'])       ->name('classes');
        Route::get('/calendar',        [StudentDashboardController::class, 'calendar'])      ->name('calendar');
        Route::get('/attendance',      [StudentDashboardController::class, 'attendance'])    ->name('attendance');
        Route::get('/invoices',        [StudentDashboardController::class, 'invoices'])      ->name('invoices');
        Route::get('/payments',        [StudentDashboardController::class, 'payments'])      ->name('payments');
        Route::get('/teachers',        [StudentDashboardController::class, 'teachers'])      ->name('teachers');
        Route::get('/notifications',   [StudentDashboardController::class, 'notifications'])->name('notifications');
    });
