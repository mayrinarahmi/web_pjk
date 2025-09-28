<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TrendAnalysisController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// User info endpoint (optional)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ====================================
// TREND ANALYSIS API ROUTES
// ====================================
// Tidak perlu auth middleware karena sudah dicek di controller/view
Route::prefix('trend')->group(function () {
    Route::get('/overview', [TrendAnalysisController::class, 'overview']);
    Route::get('/category/{id}', [TrendAnalysisController::class, 'category']);
    Route::get('/search', [TrendAnalysisController::class, 'search']);
    Route::get('/seasonal', [TrendAnalysisController::class, 'seasonal']);
    Route::get('/month-comparison', [TrendAnalysisController::class, 'monthComparison']);
    Route::post('/clear-cache', [TrendAnalysisController::class, 'clearCache']);
});