<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TrendAnalysisController;
use App\Http\Controllers\PublicDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// ====================================
// TREND ANALYSIS API ROUTES (AUTHENTICATED)
// ====================================
// ====================================
// TREND ANALYSIS API ROUTES
// ====================================
Route::prefix('trend')->group(function () {
    // Overview - all categories aggregated
    Route::get('/overview', [TrendAnalysisController::class, 'overview']);
    
    // Monthly detail for drill-down (MUST be before /category/{id})
    Route::get('/category/{id}/monthly/{year}', [TrendAnalysisController::class, 'monthlyDetail']);
    
    // Category specific data
    Route::get('/category/{id}', [TrendAnalysisController::class, 'category']);
    
    // Search categories
    Route::get('/search', [TrendAnalysisController::class, 'search']);
    
    // Seasonal analysis
    Route::get('/seasonal', [TrendAnalysisController::class, 'seasonal']);
    
    // Month comparison
    Route::get('/month-comparison', [TrendAnalysisController::class, 'monthComparison']);
    
    // Clear cache
    Route::post('/clear-cache', [TrendAnalysisController::class, 'clearCache']);
});

// ====================================
// PUBLIC DASHBOARD API ROUTES (NO AUTH REQUIRED)
// ====================================
Route::prefix('public')->group(function () {
    
    // Summary with 4 category breakdown (Total, PAD, Transfer, Lain-lain)
    Route::get('/summary', [PublicDashboardController::class, 'getSummary']);
    
    // SKPD realisasi data
    Route::get('/skpd-realisasi', [PublicDashboardController::class, 'getSkpdRealisasi']);
    
    // Top categories
    Route::get('/top-categories', [PublicDashboardController::class, 'getTopCategories']);
    
    // Monthly trend chart data
    Route::get('/monthly-trend', [PublicDashboardController::class, 'getMonthlyTrend']);
    
    // Yearly comparison chart data
    Route::get('/yearly-comparison', [PublicDashboardController::class, 'getYearlyComparison']);
    
    // ✨ NEW: Kode Rekening Tree for cascading filter
    // Returns Level 2 (4.1, 4.2, 4.3) by default
    // If parent_id provided, returns children of that parent
    Route::get('/kode-rekening-tree', [PublicDashboardController::class, 'getKodeRekeningTree']);
    
    // ✨ NEW: Category Trend (supports ANY LEVEL 2-6)
    // Uses TrendAnalysisService internally
    // Params: years, view (yearly/monthly), month
    Route::get('/category-trend/{id}', [PublicDashboardController::class, 'getCategoryTrend']);
    
    // ✨ NEW: Search Kode Rekening (public version)
    // Min 2 characters, returns max 20 results
    Route::get('/search', [PublicDashboardController::class, 'searchKodeRekening']);
    
    // Clear cache (optional - can be restricted with auth if needed)
    Route::post('/clear-cache', [PublicDashboardController::class, 'clearCache']);
});


/**
 * ========================================
 * API ENDPOINT DOCUMENTATION
 * ========================================
 * 
 * AUTHENTICATED ENDPOINTS (Trend Analysis)
 * ========================================
 * 
 * 1. GET /api/trend/overview?years=3&view=yearly&month=9
 *    Returns: Overview data for all categories
 * 
 * 2. GET /api/trend/category/{id}?years=3&view=yearly&month=9
 *    Returns: Specific category data (supports Level 2-6)
 * 
 * 3. GET /api/trend/category/{id}/monthly/{year}
 *    Returns: Monthly breakdown for drill-down
 * 
 * 4. GET /api/trend/search?q=pajak
 *    Returns: Search results (min 2 chars)
 * 
 * 5. GET /api/trend/comparison
 *    Returns: Period comparison data
 * 
 * 6. GET /api/trend/top-performers?year=2025&limit=10&level=2
 *    Returns: Top performing categories
 * 
 * 7. GET /api/trend/growth?level=2&start_year=2023&end_year=2025
 *    Returns: Growth analysis
 * 
 * 8. GET /api/trend/seasonal/{id}?years=3
 *    Returns: Seasonal pattern analysis
 * 
 * 9. GET /api/trend/forecast/{id}?months=12
 *    Returns: Forecast data
 * 
 * 10. GET /api/trend/achievement/{id}?year=2025&target=1000000000
 *     Returns: Achievement rate vs target
 * 
 * 11. GET /api/trend/breakdown/{parentId}?year=2025&month=9
 *     Returns: Detailed breakdown by sub-categories
 * 
 * 12. GET /api/trend/export?level=2&year=2025&format=csv
 *     Returns: Export data in CSV format
 * 
 * 13. POST /api/trend/clear-cache
 *     Returns: Cache cleared status
 * 
 * 14. GET /api/trend/data-quality?year=2025
 *     Returns: Data quality metrics
 * 
 * 15. GET /api/trend/insights/{id}?year=2025
 *     Returns: AI insights and recommendations
 * 
 * 
 * PUBLIC ENDPOINTS (Dashboard)
 * ========================================
 * 
 * 1. GET /api/public/summary?tahun=2025
 *    Returns: 4 category breakdown
 *    Response: {
 *      total: { realisasi, target, kurang, persentase },
 *      pad: { realisasi, target, kurang, persentase },
 *      transfer: { realisasi, target, kurang, persentase },
 *      lain_lain: { realisasi, target, kurang, persentase },
 *      update_terakhir: "20 November 2025",
 *      tahun: 2025
 *    }
 * 
 * 2. GET /api/public/skpd-realisasi?tahun=2025
 *    Returns: SKPD realisasi table data
 * 
 * 3. GET /api/public/top-categories?tahun=2025&limit=5
 *    Returns: Top N categories with percentage
 * 
 * 4. GET /api/public/monthly-trend?tahun=2025
 *    Returns: Monthly trend chart data (PAD, Transfer, Lain-lain)
 * 
 * 5. GET /api/public/yearly-comparison?tahun=2025&years=3
 *    Returns: Year comparison chart data
 * 
 * 6. GET /api/public/kode-rekening-tree?parent_id={id}
 *    Returns: Children kode rekening for cascading dropdown
 *    - If parent_id is null: Returns Level 2 (4.1, 4.2, 4.3)
 *    - If parent_id provided: Returns direct children
 *    Response: [
 *      { id, kode, nama, level, parent_id }
 *    ]
 * 
 * 7. GET /api/public/category-trend/{id}?years=3&view=yearly&month=9
 *    Returns: Trend data for specific category (ANY LEVEL 2-6)
 *    Uses TrendAnalysisService internally
 *    Params:
 *      - years: Number of years (default: 3)
 *      - view: yearly|monthly (default: yearly)
 *      - month: Month number 1-12 (for monthly comparison)
 *    Response: {
 *      categories: ["2023", "2024", "2025"],
 *      series: [{ name: "Category Name", data: [100, 200, 300] }],
 *      categoryInfo: { id, kode, nama, level }
 *    }
 * 
 * 8. GET /api/public/search?q=pajak
 *    Returns: Search results for kode rekening (min 2 chars)
 *    Response: [
 *      { id, kode, nama, level }
 *    ]
 * 
 * 9. POST /api/public/clear-cache
 *    Returns: { success: true, message: "Cache cleared" }
 * 
 * 
 * NOTES:
 * ========================================
 * 
 * - Authenticated routes require Bearer token or session auth
 * - Public routes are accessible without authentication
 * - Both use TrendAnalysisService for data processing
 * - All responses are cached (1 hour default)
 * - Date format: ISO 8601 (YYYY-MM-DD)
 * - Currency format: Indonesian Rupiah (IDR)
 * - Pagination: Use limit parameter (default: 10, max: 100)
 */
