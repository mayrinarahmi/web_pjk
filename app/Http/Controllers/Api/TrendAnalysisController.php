<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrendAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TrendAnalysisController extends Controller
{
    protected $trendService;

    public function __construct(TrendAnalysisService $trendService)
    {
        $this->trendService = $trendService;
    }

    /**
     * Get overview data
     */
    public function overview(Request $request)
    {
        try {
            $years = $request->input('years', 3);
            $view = $request->input('view', 'yearly');
            $month = $request->input('month', null);
            
            $data = $this->trendService->getOverviewData($years, $view, $month);
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in overview: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get category specific data - FIXED FOR LEVEL 5 & 6
     */
    public function category($id, Request $request)
    {
        try {
            $years = $request->input('years', 3);
            $view = $request->input('view', 'yearly');
            $month = $request->input('month', null);
            
            $kodeRekening = \App\Models\KodeRekening::findOrFail($id);
            
            // FIX: Proper parameter handling for level 5 & 6
            if ($kodeRekening->level < 6) {
                // Level 1-5: Show children
                $level = $kodeRekening->level + 1;
                $parentKode = $kodeRekening->id;
                $specificId = null;
            } else {
                // Level 6: Show the item itself
                $level = 6;
                $parentKode = null;  // FIX: Set null to avoid conflict
                $specificId = $kodeRekening->id;
            }
            
            // Get data based on view type
            if ($view === 'monthly') {
                $currentYear = date('Y');
                $data = $this->trendService->getMonthlyChartData(
                    $level,
                    $parentKode,
                    $currentYear,
                    10,
                    $specificId,  // Pass specificId
                    $month,       // Pass month filter
                    $years        // Pass years range so period buttons work
                );
            } else {
                $data = $this->trendService->getYearlyChartData(
                    $level, 
                    $parentKode, 
                    $years, 
                    10, 
                    $specificId   // Pass specificId
                );
            }
            
            // Add category info
            $data['categoryInfo'] = [
                'id' => $kodeRekening->id,
                'kode' => $kodeRekening->kode,
                'nama' => $kodeRekening->nama,
                'level' => $kodeRekening->level
            ];
            
            // Add month info if monthly view with specific month
            if ($view === 'monthly' && $month !== null) {
                $data['month'] = $month;
                $data['monthName'] = $this->getMonthName($month);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'cached' => false
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in category: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search kode rekening
     */
    public function search(Request $request)
    {
        try {
            $query = $request->input('q', '');
            
            if (strlen($query) < 2) {
                return response()->json([
                    'success' => true,
                    'data' => []
                ]);
            }
            
            $results = $this->trendService->searchKodeRekening($query, 20);
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in search: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian'
            ], 500);
        }
    }

    /**
     * Get comparison data
     */
    public function comparison(Request $request)
    {
        try {
            $level = $request->input('level', 1);
            $parentKode = $request->input('parent', null);
            $period1Start = $request->input('period1_start');
            $period1End = $request->input('period1_end');
            $period2Start = $request->input('period2_start');
            $period2End = $request->input('period2_end');
            
            $data = $this->trendService->getComparisonData(
                $level, 
                $parentKode, 
                $period1Start, 
                $period1End, 
                $period2Start, 
                $period2End
            );
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in comparison: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data perbandingan'
            ], 500);
        }
    }

    /**
     * Get top performers
     */
    public function topPerformers(Request $request)
    {
        try {
            $tahun = $request->input('year', date('Y'));
            $limit = $request->input('limit', 10);
            $level = $request->input('level', null);
            
            $data = $this->trendService->getTopPerformers($tahun, $limit, $level);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in topPerformers: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data top performers'
            ], 500);
        }
    }

    /**
     * Get growth analysis
     */
    public function growth(Request $request)
    {
        try {
            $level = $request->input('level', 1);
            $parentKode = $request->input('parent', null);
            $startYear = $request->input('start_year', date('Y') - 3);
            $endYear = $request->input('end_year', date('Y'));
            
            $data = $this->trendService->getGrowthAnalysis($level, $parentKode, $startYear, $endYear);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in growth: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pertumbuhan'
            ], 500);
        }
    }

    /**
     * Get seasonal analysis
     */
    public function seasonal($kodeRekeningId, Request $request)
    {
        try {
            $years = $request->input('years', 3);
            
            $data = $this->trendService->getSeasonalAnalysis($kodeRekeningId, $years);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in seasonal: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data seasonal'
            ], 500);
        }
    }

    /**
     * Get forecast data
     */
    public function forecast($kodeRekeningId, Request $request)
    {
        try {
            $months = $request->input('months', 12);
            
            $data = $this->trendService->getForecastData($kodeRekeningId, $months);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in forecast: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data forecast'
            ], 500);
        }
    }

    /**
     * Get achievement rate
     */
    public function achievement($kodeRekeningId, Request $request)
    {
        try {
            $tahun = $request->input('year', date('Y'));
            $target = $request->input('target', null);
            
            $data = $this->trendService->getAchievementRate($kodeRekeningId, $tahun, $target);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in achievement: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data achievement'
            ], 500);
        }
    }

    /**
     * Get detailed breakdown
     */
    public function breakdown($parentId, Request $request)
    {
        try {
            $tahun = $request->input('year', date('Y'));
            $bulan = $request->input('month', null);
            
            $data = $this->trendService->getDetailedBreakdown($parentId, $tahun, $bulan);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in breakdown: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data breakdown'
            ], 500);
        }
    }

    /**
     * Export data
     */
    public function export(Request $request)
    {
        try {
            $level = $request->input('level', 1);
            $parentKode = $request->input('parent', null);
            $tahun = $request->input('year', date('Y'));
            $format = $request->input('format', 'csv');
            
            $data = $this->trendService->exportData($level, $parentKode, $tahun, $format);
            
            if ($format === 'csv') {
                $filename = 'trend_analysis_' . $tahun . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                ];
                
                $callback = function() use ($data) {
                    $file = fopen('php://output', 'w');
                    foreach ($data as $row) {
                        fputcsv($file, $row);
                    }
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
            }
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in export: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal export data'
            ], 500);
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(Request $request)
    {
        try {
            $pattern = $request->input('pattern', null);
            
            $this->trendService->clearCache($pattern);
            
            return response()->json([
                'success' => true,
                'message' => 'Cache berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in clearCache: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus cache'
            ], 500);
        }
    }

    /**
     * Get data quality metrics
     */
    public function dataQuality(Request $request)
    {
        try {
            $tahun = $request->input('year', date('Y'));
            
            $integrity = $this->trendService->validateDataIntegrity($tahun);
            $quality = $this->trendService->getDataQualityMetrics($tahun);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'integrity' => $integrity,
                    'quality' => $quality
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in dataQuality: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data quality metrics'
            ], 500);
        }
    }

    /**
     * Get insights
     */
    public function insights($kodeRekeningId, Request $request)
    {
        try {
            $tahun = $request->input('year', date('Y'));
            
            $data = $this->trendService->getInsights($kodeRekeningId, $tahun);
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in insights: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil insights'
            ], 500);
        }
    }

    /**
     * Helper method to get month name
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
            4 => 'April', 5 => 'Mei', 6 => 'Juni',
            7 => 'Juli', 8 => 'Agustus', 9 => 'September',
            10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        
        return $months[$month] ?? '';
    }

    /**
     * ✨ NEW: Get monthly detail for drill-down
     * 
     * @param string $id - Category ID
     * @param int $year - Year
     * @param Request $request
     * @return JsonResponse
     */
    public function monthlyDetail($id, $year, Request $request)
    {
        try {
            // Validate year parameter
            if (!is_numeric($year) || $year < 2020 || $year > date('Y')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year parameter. Year must be between 2020 and ' . date('Y')
                ], 400);
            }
            
            // Get category
            $kodeRekening = \App\Models\KodeRekening::find($id);
            if (!$kodeRekening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found with ID: ' . $id
                ], 404);
            }
            
            Log::info('Monthly detail request', [
                'categoryId' => $id,
                'categoryName' => $kodeRekening->nama,
                'year' => $year,
                'level' => $kodeRekening->level
            ]);
            
            // Determine parameters for chart data based on level
            if ($kodeRekening->level < 6) {
                // Level 1-5: Show children or aggregated data
                $level = $kodeRekening->level + 1;
                $parentKode = $kodeRekening->id;
                $specificId = null;
            } else {
                // Level 6: Show the item itself
                $level = 6;
                $parentKode = null;
                $specificId = $kodeRekening->id;
            }
            
            // Get chart data (monthly breakdown for the year)
            // This will show Jan-Dec data for the selected year
            $chartData = $this->trendService->getMonthlyChartData(
                $level,
                $parentKode,
                $year,
                10,
                $specificId,
                null  // null = show all 12 months, not a specific month comparison
            );
            
            // Get summary statistics for cards
            $summary = $this->trendService->getMonthlySummary($id, $year);
            
            // Get growth table data (month-over-month)
            $tableData = $this->trendService->getMonthlyGrowthTable($id, $year);
            
            // Check if we have data
            if (empty($chartData['categories']) && empty($chartData['series'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'No data available for the selected year'
                ], 404);
            }
            
            // Build response
            $response = [
                'success' => true,
                'data' => [
                    // Chart data
                    'categories' => $chartData['categories'] ?? [],
                    'series' => $chartData['series'] ?? [],
                    
                    // Summary for cards
                    'summary' => $summary,
                    
                    // Table data
                    'tableData' => $tableData,
                    
                    // Category info
                    'categoryInfo' => [
                        'id' => $kodeRekening->id,
                        'kode' => $kodeRekening->kode,
                        'nama' => $kodeRekening->nama,
                        'level' => $kodeRekening->level
                    ],
                    
                    // Year info
                    'year' => (int)$year
                ]
            ];
            
            Log::info('Monthly detail response prepared', [
                'categoryId' => $id,
                'year' => $year,
                'seriesCount' => count($chartData['series'] ?? []),
                'hasSummary' => !is_null($summary),
                'tableRows' => count($tableData)
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Error getting monthly detail', [
                'categoryId' => $id,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading monthly detail: ' . $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }
}