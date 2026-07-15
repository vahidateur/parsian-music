<?php

use App\Http\Controllers\Admin\AttendanceReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ClassAttendanceController;
use App\Http\Controllers\Admin\ClassSessionController;
use App\Http\Controllers\Admin\InstrumentController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\RoomController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentEnrollmentController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherPanelController;
use App\Http\Controllers\Admin\TeacherReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Public teacher profile — Phase 1: mock data only. {teacher} ready for Route Model Binding in Phase 2.
Route::get('/teachers/{teacher}', function () {
    return view('teachers.show');
})->name('teachers.show');

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    return match ($request->user()->role) {
        \App\Enums\RoleEnum::SUPER_ADMIN => redirect()->intended('/admin/dashboard'),
        \App\Enums\RoleEnum::ADMIN       => redirect()->intended('/admin/dashboard'),
        \App\Enums\RoleEnum::TEACHER     => redirect()->intended('/teacher/dashboard'),
        \App\Enums\RoleEnum::STUDENT     => redirect()->intended('/student/dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/password',   [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/dashboard.php';

Route::middleware('auth')->get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware(['auth', 'role:admin'])->prefix('admin/students')->name('admin.students.')->group(function () {
    Route::get('/', [StudentController::class, 'index'])->name('index');
    Route::get('/create', [StudentController::class, 'create'])->name('create');
    Route::post('/', [StudentController::class, 'store'])->name('store');
    Route::get('/{student}', [StudentController::class, 'show'])->name('show');
    Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit');
    Route::put('/{student}', [StudentController::class, 'update'])->name('update');
    Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/leads')->name('admin.leads.')->group(function () {
    Route::get('/', [LeadController::class, 'index'])->name('index');
    Route::get('/kanban', [LeadController::class, 'kanban'])->name('kanban');
    Route::get('/create', [LeadController::class, 'create'])->name('create');
    Route::post('/', [LeadController::class, 'store'])->name('store');
    Route::get('/{lead}', [LeadController::class, 'show'])->name('show');
    Route::get('/{lead}/edit', [LeadController::class, 'edit'])->name('edit');
    Route::put('/{lead}', [LeadController::class, 'update'])->name('update');
    Route::delete('/{lead}', [LeadController::class, 'destroy'])->name('destroy');
    Route::patch('/{lead}/assign', [LeadController::class, 'assign'])->name('assign');
    Route::patch('/{lead}/follow-up', [LeadController::class, 'scheduleFollowUp'])->name('followUp');
    Route::patch('/{lead}/status', [LeadController::class, 'updateStatus'])->name('updateStatus');
    Route::post('/{lead}/convert', [LeadController::class, 'convert'])->name('convert');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/rooms')->name('admin.rooms.')->group(function () {
    Route::get('/', [RoomController::class, 'index'])->name('index');
    Route::get('/create', [RoomController::class, 'create'])->name('create');
    Route::post('/', [RoomController::class, 'store'])->name('store');
    Route::get('/{room}/edit', [RoomController::class, 'edit'])->name('edit');
    Route::put('/{room}', [RoomController::class, 'update'])->name('update');
    Route::delete('/{room}', [RoomController::class, 'destroy'])->name('destroy');
    Route::patch('/{room}/toggle', [RoomController::class, 'toggle'])->name('toggle');
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
    Route::get('/create', [ClassSessionController::class, 'create'])->name('create');
    Route::post('/', [ClassSessionController::class, 'store'])->name('store');
    Route::delete('/{session}', [ClassSessionController::class, 'destroy'])->name('destroy');
    Route::post('/generate', [ClassSessionController::class, 'generate'])->name('generate');
    Route::get('/{session}/attendance', [ClassAttendanceController::class, 'show'])->name('attendance.show');
    Route::post('/{session}/attendance', [ClassAttendanceController::class, 'store'])->name('attendance.store');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/calendar')->name('admin.calendar.')->group(function () {
    Route::get('/', [ClassSessionController::class, 'calendar'])->name('index');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/enrollments')->name('admin.enrollments.')->group(function () {
    Route::get('/', [StudentEnrollmentController::class, 'index'])->name('index');
    Route::get('/create', [StudentEnrollmentController::class, 'create'])->name('create');
    Route::post('/', [StudentEnrollmentController::class, 'store'])->name('store');
    Route::get('/{enrollment}/edit', [StudentEnrollmentController::class, 'edit'])->name('edit');
    Route::put('/{enrollment}', [StudentEnrollmentController::class, 'update'])->name('update');
    Route::delete('/{enrollment}', [StudentEnrollmentController::class, 'destroy'])->name('destroy');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/reports')->name('admin.reports.')->group(function () {
    Route::get('/attendance', [AttendanceReportController::class, 'index'])->name('attendance');
    Route::get('/teachers', [TeacherReportController::class, 'index'])->name('teachers');
});

Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
    Route::get('/',                                         [SettingsController::class, 'index'])           ->name('index');
    Route::post('/institute',                               [SettingsController::class, 'updateInstitute']) ->name('institute.update');
    Route::put('/{section}',                               [SettingsController::class, 'update'])          ->name('update');
    Route::get('/{section}',                               [SettingsController::class, 'show'])            ->name('show');
});

// User management — super_admin only (admins cannot manage other admins or super_admins)
Route::middleware(['auth', 'role:super_admin,admin'])->prefix('admin/users')->name('admin.users.')->group(function () {
    Route::get('/',                         [UserController::class, 'index'])->name('index');
    Route::get('/create',                   [UserController::class, 'create'])->name('create');
    Route::post('/',                        [UserController::class, 'store'])->name('store');
    Route::get('/{user}/edit',              [UserController::class, 'edit'])->name('edit');
    Route::put('/{user}',                   [UserController::class, 'update'])->name('update');
    Route::delete('/{user}',                [UserController::class, 'destroy'])->name('destroy');
    Route::patch('/{user}/toggle',          [UserController::class, 'toggle'])->name('toggle');
    Route::post('/{user}/reset-password',   [UserController::class, 'resetPassword'])->name('resetPassword');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin/instruments')->name('admin.instruments.')->group(function () {
    Route::get('/', [InstrumentController::class, 'index'])->name('index');
    Route::get('/create', [InstrumentController::class, 'create'])->name('create');
    Route::post('/', [InstrumentController::class, 'store'])->name('store');
    Route::get('/{instrument}/edit', [InstrumentController::class, 'edit'])->name('edit');
    Route::put('/{instrument}', [InstrumentController::class, 'update'])->name('update');
    Route::delete('/{instrument}', [InstrumentController::class, 'destroy'])->name('destroy');
    Route::patch('/{instrument}/toggle', [InstrumentController::class, 'toggle'])->name('toggle');
});
