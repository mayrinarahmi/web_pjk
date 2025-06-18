<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrendAnalysisController extends Controller
{
    /**
     * Get overview data
     */
    public function overview(Request $request)
    {
        try {
            $years = $request->input('years', 3);
            $view = $request->input('view', 'yearly');
            $month = $request->input('month', date('n'));
            
            $endYear = date('Y');
            $startYear = $endYear - $years + 1;
            
            // Build year categories
            $categories = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $categories[] = (string)$year;
            }
            
            // Get kode rekening level 2
            $kodeRekenings = DB::table('kode_rekening')
                ->where('is_active', true)
                ->where('level', 2)
                ->orderBy('kode')
                ->get();
            
            $series = [];
            
            if ($view === 'yearly') {
                // Yearly view
                foreach ($kodeRekenings as $kode) {
                    $seriesData = [];
                    
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $total = DB::table('penerimaan')
                            ->where('kode_rekening_id', $kode->id)
                            ->where('tahun', $year)
                            ->sum('jumlah');
                        
                        $seriesData[] = (float)$total;
                    }
                    
                    if (array_sum($seriesData) > 0) {
                        $series[] = [
                            'name' => $kode->nama,
                            'data' => $seriesData
                        ];
                    }
                }
            } else {
                // Monthly view
                foreach ($kodeRekenings as $kode) {
                    $seriesData = [];
                    
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $total = DB::table('penerimaan')
                            ->where('kode_rekening_id', $kode->id)
                            ->where('tahun', $year)
                            ->whereMonth('tanggal', $month)
                            ->sum('jumlah');
                        
                        $seriesData[] = (float)$total;
                    }
                    
                    if (array_sum($seriesData) > 0) {
                        $series[] = [
                            'name' => $kode->nama,
                            'data' => $seriesData
                        ];
                    }
                }
            }
            
            // Calculate summary
            $totalCurrentYear = 0;
            $totalStartYear = 0;
            
            foreach ($series as $s) {
                $totalCurrentYear += $s['data'][count($s['data']) - 1] ?? 0;
                $totalStartYear += $s['data'][0] ?? 0;
            }
            
            $totalGrowth = $totalStartYear > 0 ? 
                (($totalCurrentYear - $totalStartYear) / $totalStartYear) * 100 : 0;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'series' => $series,
                    'summary' => [
                        'total_growth' => round($totalGrowth, 2),
                        'average_growth' => round($totalGrowth / $years, 2),
                        'best_performer' => count($series) > 0 ? $series[0]['name'] : '-',
                        'total_current_year' => $totalCurrentYear,
                        'total_start_year' => $totalStartYear
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Overview error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data'
            ], 500);
        }
    }
    
    /**
     * Get category specific data
     */
    public function category($categoryId, Request $request)
    {
        try {
            $years = $request->input('years', 3);
            $view = $request->input('view', 'yearly');
            $month = $request->input('month', date('n'));
            
            $endYear = date('Y');
            $startYear = $endYear - $years + 1;
            
            // Find category
            $kodeRekening = DB::table('kode_rekening')
                ->where('id', $categoryId)
                ->first();
            
            if (!$kodeRekening) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan'
                ], 404);
            }
            
            // Build categories
            $categories = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $categories[] = (string)$year;
            }
            
            // Get child categories
            $childKodes = DB::table('kode_rekening')
                ->where('is_active', true)
                ->where('parent_id', $categoryId)
                ->orderBy('kode')
                ->get();
            
            // If no children, use the category itself
            if ($childKodes->isEmpty()) {
                $childKodes = collect([$kodeRekening]);
            }
            
            $series = [];
            
            if ($view === 'yearly') {
                foreach ($childKodes as $kode) {
                    $seriesData = [];
                    
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $total = DB::table('penerimaan')
                            ->where('kode_rekening_id', $kode->id)
                            ->where('tahun', $year)
                            ->sum('jumlah');
                        
                        $seriesData[] = (float)$total;
                    }
                    
                    if (array_sum($seriesData) > 0) {
                        $series[] = [
                            'name' => $kode->nama,
                            'data' => $seriesData
                        ];
                    }
                }
            } else {
                // Monthly view
                foreach ($childKodes as $kode) {
                    $seriesData = [];
                    
                    for ($year = $startYear; $year <= $endYear; $year++) {
                        $total = DB::table('penerimaan')
                            ->where('kode_rekening_id', $kode->id)
                            ->where('tahun', $year)
                            ->whereMonth('tanggal', $month)
                            ->sum('jumlah');
                        
                        $seriesData[] = (float)$total;
                    }
                    
                    if (array_sum($seriesData) > 0) {
                        $series[] = [
                            'name' => $kode->nama,
                            'data' => $seriesData
                        ];
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'categories' => $categories,
                    'series' => $series,
                    'categoryInfo' => [
                        'id' => $kodeRekening->id,
                        'kode' => $kodeRekening->kode,
                        'nama' => $kodeRekening->nama,
                        'level' => $kodeRekening->level
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Category error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat data'
            ], 500);
        }
    }
    
    /**
     * Search categories
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
            
            $results = DB::table('kode_rekening')
                ->where('is_active', true)
                ->where(function($q) use ($query) {
                    $q->where('nama', 'like', '%' . $query . '%')
                      ->orWhere('kode', 'like', '%' . $query . '%');
                })
                ->where('level', '>=', 3)
                ->orderBy('kode', 'asc')
                ->limit(20)
                ->get(['id', 'kode', 'nama', 'level']);
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari'
            ], 500);
        }
    }
}