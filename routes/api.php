<?php

use App\Http\Controllers\Api\ExtractorController;
use Illuminate\Support\Facades\Route;

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
