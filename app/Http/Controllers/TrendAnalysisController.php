<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TrendAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TrendAnalysisController extends Controller
{
    protected $trendService;
    
    public function __construct(TrendAnalysisService $trendService)
    {
        $this->trendService = $trendService;
    }
    
    /**
     * Get overview data with caching
     */
    public function overview(Request $request)
    {
        $years = $request->input('years', 3);
        $view = $request->input('view', 'yearly');
        $month = $request->input('month', date('n'));
        
        $endYear = date('Y');
        $startYear = $endYear - $years + 1;
        
        // Create cache key
        $cacheKey = "trend_overview_{$view}_{$years}_{$month}_{$startYear}_{$endYear}";
        
        // Try to get from cache
        $data = Cache::remember($cacheKey, 300, function () use ($view, $month, $startYear, $endYear) {
            if ($view === 'monthly') {
                // Use optimized monthly method
                $chartData = $this->trendService->getMonthlyChartDataOptimized($month, $startYear, $endYear);
                $summary = $this->trendService->getMonthlySummaryStatistics($month, $startYear, $endYear);
                $topPerformers = $this->trendService->getMonthlyTopPerformers($month, $startYear, $endYear, 5);
                
                return array_merge($chartData, [
                    'summary' => $summary,
                    'topPerformers' => $topPerformers
                ]);
            } else {
                // Yearly view (existing)
                $chartData = $this->trendService->getChartData($startYear, $endYear);
                $summary = $this->trendService->getSummaryStatistics($startYear, $endYear);
                
                return array_merge($chartData, ['summary' => $summary]);
            }
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'cached' => Cache::has($cacheKey)
        ]);
    }
    
    /**
     * Get category specific data
     */
    public function category($categoryId, Request $request)
    {
        $years = $request->input('years', 3);
        $view = $request->input('view', 'yearly');
        $month = $request->input('month', date('n'));
        
        $endYear = date('Y');
        $startYear = $endYear - $years + 1;
        
        // Find kode rekening
        $kodeRekening = \App\Models\KodeRekening::find($categoryId);
        if (!$kodeRekening) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ], 404);
        }
        
        // Cache key
        $cacheKey = "trend_category_{$categoryId}_{$view}_{$years}_{$month}";
        
        $data = Cache::remember($cacheKey, 300, function () use ($view, $month, $startYear, $endYear, $kodeRekening) {
            if ($view === 'monthly') {
                // Monthly comparison for specific category
                $level = $kodeRekening->level < 5 ? $kodeRekening->level + 1 : $kodeRekening->level;
                $parentKode = $kodeRekening->id;
                
                $chartData = $this->trendService->getMonthlyChartDataOptimized($month, $startYear, $endYear, $level, $parentKode);
                $summary = $this->trendService->getMonthlySummaryStatistics($month, $startYear, $endYear, $level, $parentKode);
                
                return array_merge($chartData, [
                    'summary' => $summary,
                    'categoryInfo' => [
                        'id' => $kodeRekening->id,
                        'kode' => $kodeRekening->kode,
                        'nama' => $kodeRekening->nama,
                        'level' => $kodeRekening->level
                    ]
                ]);
            } else {
                // Yearly view
                $level = $kodeRekening->level < 5 ? $kodeRekening->level + 1 : $kodeRekening->level;
                $parentKode = $kodeRekening->id;
                
                $chartData = $this->trendService->getChartData($startYear, $endYear, $level, $parentKode);
                $summary = $this->trendService->getSummaryStatistics($startYear, $endYear, $level, $parentKode);
                
                return array_merge($chartData, [
                    'summary' => $summary,
                    'categoryInfo' => [
                        'id' => $kodeRekening->id,
                        'kode' => $kodeRekening->kode,
                        'nama' => $kodeRekening->nama,
                        'level' => $kodeRekening->level
                    ]
                ]);
            }
        });
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Get seasonal analysis for a year
     */
    public function seasonal(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $categoryId = $request->input('category_id', null);
        
        $monthlyTrend = $this->trendService->getYearlyMonthlyTrend($year, $categoryId);
        $analysis = $this->trendService->getMonthlySummaryAnalysis($year);
        
        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'monthlyTrend' => $monthlyTrend,
                'analysis' => $analysis
            ]
        ]);
    }
    
    /**
     * Compare specific month across all available years
     */
    public function monthComparison(Request $request)
    {
        $month = $request->input('month', date('n'));
        $categoryIds = $request->input('categories', []);
        
        $data = $this->trendService->getMonthComparisonAcrossYears($month, $categoryIds);
        
        // Group by year for easier frontend processing
        $groupedByYear = $data->groupBy('tahun')->map(function ($yearData) {
            return [
                'tahun' => $yearData->first()->tahun,
                'nama_bulan' => $yearData->first()->nama_bulan,
                'categories' => $yearData->map(function ($item) {
                    return [
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'total' => $item->total_penerimaan,
                        'transaksi' => $item->jumlah_transaksi
                    ];
                }),
                'total' => $yearData->sum('total_penerimaan')
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'comparison' => $groupedByYear->values()
            ]
        ]);
    }
    
    /**
     * Search with monthly data support
     */
    public function search(Request $request)
    {
        $query = $request->input('q', '');
        $includeMonthly = $request->input('include_monthly', false);
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }
        
        $results = \App\Models\KodeRekening::where('is_active', true)
            ->where(function($q) use ($query) {
                $q->where('nama', 'like', '%' . $query . '%')
                  ->orWhere('kode', 'like', '%' . $query . '%');
            })
            ->where('level', '>=', 3)
            ->orderBy('kode', 'asc')
            ->limit(20)
            ->get(['id', 'kode', 'nama', 'level']);
        
        // If monthly data requested, add latest month info
        if ($includeMonthly && $results->isNotEmpty()) {
            $currentMonth = date('n');
            $currentYear = date('Y');
            
            $monthlyData = DB::table('v_penerimaan_monthly')
                ->whereIn('kode_rekening_id', $results->pluck('id'))
                ->where('tahun', $currentYear)
                ->where('bulan', $currentMonth)
                ->get()
                ->keyBy('kode_rekening_id');
            
            $results = $results->map(function ($item) use ($monthlyData) {
                $item->monthly_amount = $monthlyData->get($item->id)->total_penerimaan ?? 0;
                return $item;
            });
        }
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
    
    /**
     * Clear cache for fresh data
     */
    public function clearCache(Request $request)
    {
        // Only allow in development or with proper authorization
        if (app()->environment('production') && !$request->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }
        
        Cache::flush();
        
        return response()->json([
            'success' => true,
            'message' => 'Cache cleared successfully'
        ]);
    }
}