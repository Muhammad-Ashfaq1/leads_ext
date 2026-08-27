<?php

use App\Http\Controllers\Api\ExtractorController;
use App\Http\Controllers\LeadPreviewController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web'])->group(function (): void {
    Route::prefix('extractor')->name('extractor.')->group(function (): void {
        Route::post('/start', [ExtractorController::class, 'start'])
            ->middleware('throttle:20,1')
            ->name('start');

        Route::get('/{job}/stream', [ExtractorController::class, 'stream'])->name('stream');
        Route::get('/{job}/status', [ExtractorController::class, 'status'])->name('status');
        Route::get('/{job}/export', [ExtractorController::class, 'export'])->name('export');
        Route::post('/{job}/stop', [ExtractorController::class, 'stop'])->name('stop');
        Route::post('/{job}/focus', [ExtractorController::class, 'focus'])->name('focus');
        Route::post('/{job}/verify-complete', [ExtractorController::class, 'verifyComplete'])->name('verify-complete');
    });

    Route::prefix('email-templates')->group(function (): void {
        Route::get('/list', [\App\Http\Controllers\EmailTemplateController::class, 'listJson'])->name('email-templates.list');
    });

    Route::prefix('leads')->name('leads.')->group(function (): void {
        Route::post('/bulk-action', [ExtractorController::class, 'bulkAction'])->name('bulk-action');
        Route::post('/export-selected', [ExtractorController::class, 'exportSelected'])->name('export-selected');
        Route::post('/send-email', [ExtractorController::class, 'sendEmail'])->name('send-email');
        Route::post('/{id}/generate-demo', [LeadPreviewController::class, 'generate'])->name('generate-demo');
    });
});
