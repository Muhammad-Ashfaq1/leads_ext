<?php

use App\Http\Controllers\Api\ExtractorController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\ExtractorPageController;
use App\Http\Controllers\JobsController;
use App\Http\Controllers\LeadPreviewController;
use App\Http\Controllers\LeadsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TenantsController;
use App\Http\Controllers\UsersController;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\TenantMiddleware;
use Illuminate\Support\Facades\Route;

// Public Spec Website Demo Preview Route
Route::get('/preview/{uuid}', [LeadPreviewController::class, 'preview'])->name('leads.preview');

// Guest / Authentication Routes
Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
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

    // Email Templates & Outreach
    Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::post('/email-templates', [EmailTemplateController::class, 'store'])->name('email-templates.store');
    Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::delete('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'destroy'])->name('email-templates.destroy');
    Route::post('/email-templates/{emailTemplate}/default', [EmailTemplateController::class, 'setDefault'])->name('email-templates.default');

    // Extraction History & Job Export
    Route::get('/jobs', [JobsController::class, 'index'])->name('jobs.index');
    Route::get('/jobs/{job}/export', [ExtractorController::class, 'export'])->name('extractor.job.export');

    // Team Members
    Route::get('/users', [UsersController::class, 'index'])->name('users.index');
    Route::post('/users', [UsersController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');

    // Profile Settings
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Organization & Extractor Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

    // Super Admin Only
    Route::middleware(RoleMiddleware::class.':super_admin')->group(function (): void {
        Route::get('/tenants', [TenantsController::class, 'index'])->name('tenants.index');
        Route::post('/tenants', [TenantsController::class, 'store'])->name('tenants.store');
        Route::put('/tenants/{tenant}', [TenantsController::class, 'update'])->name('tenants.update');
    });
});
