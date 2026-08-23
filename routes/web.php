<?php

use App\Http\Controllers\Api\ExtractorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExtractorPageController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TenantsController;
use App\Http\Controllers\UsersController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

// Guest / Authentication Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/demo-login/{account}', [AuthController::class, 'demoLogin'])->name('login.demo');
});

// Authenticated SaaS App Routes
Route::middleware(['auth', TenantMiddleware::class])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Lead Extractor App
    Route::get('/extractor', ExtractorPageController::class)->name('extractor.index');

    // Extracted Leads Management
    Route::get('/leads', [LeadsController::class, 'index'])->name('leads.index');
    Route::get('/leads/export/excel', [LeadsController::class, 'exportExcel'])->name('leads.export.excel');

    // Extraction History & Job Export
    Route::get('/jobs', [JobsController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}/export', [ExtractorController::class, 'export'])->name('extractor.job.export');

    // Team Members
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');

    // Settings & Profile
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::put('/settings/tenant', [SettingsController::class, 'updateTenantSettings'])->name('settings.tenant');

    // Super Admin Only
    Route::middleware(RoleMiddleware::class.':super_admin')->group(function (): void {
        Route::get('/tenants', [TenantsController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantsController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantsController::class, 'update'])->name('tenants.update');
    });
});
