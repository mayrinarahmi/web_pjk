<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TrendAnalysisController;

// Tambahkan route ini
Route::prefix('trend')->group(function () {
    Route::get('/overview', [TrendAnalysisController::class, 'overview']);
    Route::get('/category/{categoryId}', [TrendAnalysisController::class, 'category']);
    Route::get('/search', [TrendAnalysisController::class, 'search']);
});