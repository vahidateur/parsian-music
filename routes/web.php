<?php

use App\Enums\RoleEnum;
use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherPanelController;
use App\Http\Controllers\Admin\TeacherReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    return match ($request->user()->role) {
        \App\Enums\RoleEnum::ADMIN => redirect()->intended('/admin/dashboard'),
        \App\Enums\RoleEnum::TEACHER => redirect()->intended('/teacher/dashboard'),
        \App\Enums\RoleEnum::STUDENT => redirect()->intended('/student/dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';

Route::middleware(['auth', 'role:admin'])->prefix('admin/students')->name('admin.students.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('/create', [StudentController::class, 'create'])->name('create');
    Route::post('/', [StudentController::class, 'store'])->name('store');
    Route::get('/{student}', [StudentController::class, 'show'])->name('show');
    Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{student}', [StudentController::class, 'update'])->name('update');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/teachers')->name('admin.teachers.')->group(function () {
    Route::get('/', [TeacherController::class, 'index'])->name('index');
    Route::get('/create', [TeacherController::class, 'create'])->name('create');
    Route::post('/', [TeacherController::class, 'store'])->name('store');
    Route::get('/{teacher}', [TeacherController::class, 'show'])->name('show');
    Route::get('/{teacher}/edit', [TeacherController::class, 'edit'])->name('edit');
    Route::put('/{teacher}', [TeacherController::class, 'update'])->name('update');
    Route::delete('/{teacher}', [TeacherController::class, 'destroy'])->name('destroy');
    Route::get('/{teacher}/instruments', [TeacherController::class, 'instruments'])->name('instruments');
    Route::post('/{teacher}/instruments', [TeacherController::class, 'attachInstrument'])->name('attachInstrument');
    Route::delete('/{teacher}/instruments', [TeacherController::class, 'detachInstrument'])->name('detachInstrument');
    Route::get('/{teacher}/panel', [TeacherPanelController::class, 'index'])->name('panel');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/sessions')->name('admin.sessions.')->group(function () {
    Route::get('/', [ClassSessionController::class, 'index'])->name('index');
    Route::post('/generate', [ClassSessionController::class, 'generate'])->name('generate');
    Route::get('/{session}/attendance', [ClassAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/{session}/attendance', [ClassAttendanceController::class, 'store'])->name('attendance.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/calendar')->name('admin.calendar.')->group(function () {
    Route::get('/', [ClassSessionController::class, 'calendar'])->name('index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('/attendance', [AttendanceReportController::class, 'index'])->name('attendance');
    Route::get('/teachers', [TeacherReportController::class, 'index'])->name('teachers');
});
