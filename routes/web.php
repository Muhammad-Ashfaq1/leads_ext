<?php

use App\Http\Controllers\ExtractorPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', ExtractorPageController::class)->name('extractor.index');
