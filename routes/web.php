<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AttendanceSummaryController;
use App\Http\Controllers\SettingPositionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkdayTargetController;
use App\Http\Controllers\AttendanceTargetController;
use App\Http\Middleware\EnsureAdministrator;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.process');
});

/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('dashboard.workday', ['company' => request('company', 'servanda')]);
    })->name('dashboard');
    Route::get('/dashboard/hari-kerja', [AttendanceSummaryController::class, 'dashboardWorkday'])
        ->name('dashboard.workday');
    Route::get('/dashboard/attendance-area', [AttendanceSummaryController::class, 'dashboardAttendanceArea'])
        ->name('dashboard.attendance-area');
    Route::get('/dashboard/attendance-position', [AttendanceSummaryController::class, 'dashboardAttendancePosition'])
        ->name('dashboard.attendance-position');

    Route::get('/employee', [EmployeeController::class, 'index'])->name('employee.index');
    Route::get('/employee/export', [EmployeeController::class, 'export'])->name('employee.export');
    Route::get('/employee/work-realization', [EmployeeController::class, 'workRealization'])->name('employee.work-realization');
    Route::put('/profile', [UserController::class, 'updateOwnProfile'])->name('profile.update');
    Route::put('/profile/password', [UserController::class, 'updateOwnPassword'])->name('profile.password.update');
    Route::get('/site-area', [EmployeeController::class, 'siteArea'])->name('site-area.index');
    Route::get('/site-area/template-export', [EmployeeController::class, 'exportSiteAreaTemplate'])->name('site-area.template-export');
    Route::get('/site-area/export', [EmployeeController::class, 'exportSiteArea'])->name('site-area.export');
    Route::post('/site-area/import', [EmployeeController::class, 'importSiteArea'])->name('site-area.import');
    Route::post('/site-area/sync', [EmployeeController::class, 'syncSiteArea'])->name('site-area.sync');
    Route::post('/site-area', [EmployeeController::class, 'storeSiteArea'])->name('site-area.store');
    Route::put('/site-area/{id}', [EmployeeController::class, 'updateSiteArea'])->name('site-area.update');
    Route::delete('/site-area/{id}', [EmployeeController::class, 'destroySiteArea'])->name('site-area.destroy');
    Route::middleware(EnsureAdministrator::class)->group(function () {
        Route::get('/employee/template-export', [EmployeeController::class, 'exportTemplate'])
            ->name('employee.template-export');
        Route::post('/employee/import', [EmployeeController::class, 'import'])
            ->name('employee.import');
        Route::put('/employee/{company}/{employeeNo}', [EmployeeController::class, 'updateEmployee'])
            ->name('employee.update');
        Route::get('/settings/users', [UserController::class, 'index'])->name('settings.users.index');
        Route::post('/settings/users', [UserController::class, 'store'])->name('settings.users.store');
        Route::put('/settings/users/{id}', [UserController::class, 'update'])->name('settings.users.update');
        Route::post('/settings/users/{id}/reset-password', [UserController::class, 'resetPassword'])->name('settings.users.reset-password');
        Route::delete('/settings/users/{id}', [UserController::class, 'destroy'])->name('settings.users.destroy');
        Route::get('/settings/positions', [SettingPositionController::class, 'index'])->name('settings.positions.index');
        Route::post('/settings/positions', [SettingPositionController::class, 'store'])->name('settings.positions.store');
        Route::put('/settings/positions/{id}', [SettingPositionController::class, 'update'])->name('settings.positions.update');
        Route::delete('/settings/positions/{id}', [SettingPositionController::class, 'destroy'])->name('settings.positions.destroy');
        Route::get('/settings/workday-targets', [WorkdayTargetController::class, 'index'])->name('settings.workday-targets.index');
        Route::post('/settings/workday-targets', [WorkdayTargetController::class, 'store'])->name('settings.workday-targets.store');
        Route::get('/settings/attendance-targets', [AttendanceTargetController::class, 'index'])->name('settings.attendance-targets.index');
        Route::post('/settings/attendance-targets', [AttendanceTargetController::class, 'store'])->name('settings.attendance-targets.store');
    });

    Route::get('/attendance-summary', [AttendanceSummaryController::class, 'index'])
        ->name('attendance.summary');
    Route::post('/attendance-summary/sync', [AttendanceSummaryController::class, 'sync'])
        ->name('attendance.summary.sync');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
