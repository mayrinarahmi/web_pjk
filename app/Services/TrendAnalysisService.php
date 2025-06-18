<?php

namespace App\Services;

use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrendAnalysisService
{
    /**
     * Get multi-year trend data for specified parameters
     */
    public function getMultiYearTrend($startYear, $endYear, $level = null, $parentKode = null)
    {
        $results = [];
        
        try {
            // Get kode rekening based on level and parent
            $kodeRekenings = $this->getKodeRekeningByFilter($level, $parentKode);
            
            Log::info('Found kode rekening count: ' . $kodeRekenings->count());
            
            foreach ($kodeRekenings as $kode) {
                $yearlyData = [];
                
                for ($year = $startYear; $year <= $endYear; $year++) {
                    $data = $this->getYearlyData($kode, $year);
                    $yearlyData[] = $data;
                }
                
                // Calculate growth rates
                $yearlyData = $this->calculateGrowthRates($yearlyData);
                
                $results[] = [
                    'kode_rekening' => $kode,
                    'yearly_data' => $yearlyData
                ];
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Error in getMultiYearTrend: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get summary statistics for trend analysis
     */
    public function getSummaryStatistics($startYear, $endYear, $level = null, $parentKode = null)
    {
        try {
            Log::info('Getting summary statistics', compact('startYear', 'endYear', 'level', 'parentKode'));
            
            $data = $this->getMultiYearTrend($startYear, $endYear, $level, $parentKode);
            
            // Calculate overall growth
            $totalStart = 0;
            $totalEnd = 0;
            
            foreach ($data as $item) {
                foreach ($item['yearly_data'] as $yearData) {
                    if ($yearData['tahun'] == $startYear) {
                        $totalStart += $yearData['realisasi'];
                    }
                    if ($yearData['tahun'] == $endYear) {
                        $totalEnd += $yearData['realisasi'];
                    }
                }
            }
            
            $overallGrowth = $totalStart > 0 ? (($totalEnd - $totalStart) / $totalStart) * 100 : 0;
            
            // Find top performers
            $topPerformers = $this->getTopPerformers($data, 3);
            
            // Find declining categories
            $decliningCategories = $this->getDecliningCategories($data);
            
            // Calculate average achievement
            $avgAchievement = $this->getAverageAchievement($data, $endYear);
            
            return [
                'total_growth' => round($overallGrowth, 2),
                'top_performers' => $topPerformers,
                'declining_categories' => $decliningCategories,
                'average_achievement' => round($avgAchievement, 2),
                'total_current_year' => $totalEnd,
                'total_start_year' => $totalStart
            ];
        } catch (\Exception $e) {
            Log::error('Error in getSummaryStatistics: ' . $e->getMessage());
            return [
                'total_growth' => 0,
                'top_performers' => [],
                'declining_categories' => [],
                'average_achievement' => 0,
                'total_current_year' => 0,
                'total_start_year' => 0
            ];
        }
    }
    
    /**
     * Get data for trend chart
     */
    /**
 * Get data for trend chart
 * Updated to use direct query instead of getMultiYearTrend
 */
public function getChartData($startYear, $endYear, $level = null, $parentKode = null)
{
    try {
        // Default level jika tidak ada filter
        if ($level === null) {
            $level = 1;
        }

        // Query untuk mendapatkan data per tahun
        $query = DB::table('kode_rekening as kr')
            ->select(
                'kr.id',
                'kr.kode',
                'kr.nama',
                'p.tahun',
                DB::raw('COALESCE(SUM(p.jumlah), 0) as total')
            )
            ->leftJoin('penerimaan as p', function($join) use ($startYear, $endYear) {
                $join->on('kr.id', '=', 'p.kode_rekening_id')
                     ->whereBetween('p.tahun', [$startYear, $endYear]);
            })
            ->where('kr.is_active', true)
            ->where('kr.level', $level)
            ->groupBy('kr.id', 'kr.kode', 'kr.nama', 'p.tahun');

        // Apply parent filter jika ada
        if ($parentKode !== null) {
            if (is_numeric($parentKode)) {
                $query->where('kr.parent_id', $parentKode);
            } else {
                $query->where('kr.kode', 'like', $parentKode . '%');
            }
        }

        $data = $query->orderBy('kr.kode')
                     ->orderBy('p.tahun')
                     ->get();

        // Process data untuk chart
        $categories = [];
        $series = [];
        
        // Build year categories
        for ($year = $startYear; $year <= $endYear; $year++) {
            $categories[] = (string)$year;
        }
        
        // Group data by kode rekening
        $groupedData = $data->groupBy('id');
        
        // Build series data
        foreach ($groupedData as $kodeRekeningId => $yearlyData) {
            // Get nama from first record
            $firstRecord = $yearlyData->first();
            $nama = $firstRecord->nama;
            
            // Build data array for all years
            $seriesData = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $yearRecord = $yearlyData->firstWhere('tahun', $year);
                $seriesData[] = $yearRecord ? (int)$yearRecord->total : 0;
            }
            
            // Only add to series if has data
            if (array_sum($seriesData) > 0) {
                $series[] = [
                    'name' => $nama,
                    'data' => $seriesData
                ];
            }
        }
        
        return [
            'categories' => $categories,
            'series' => $series
        ];
    } catch (\Exception $e) {
        Log::error('Error in getChartData: ' . $e->getMessage());
        return [
            'categories' => [],
            'series' => []
        ];
    }
}
    
    /**
     * Get growth rate chart data
     */
    public function getGrowthChartData($startYear, $endYear, $level = null, $parentKode = null)
    {
        try {
            $data = $this->getMultiYearTrend($startYear, $endYear, $level, $parentKode);
            
            $categories = [];
            $series = [];
            
            // Build categories from kode rekening names
            foreach ($data as $item) {
                $categories[] = $item['kode_rekening']->nama;
            }
            
            // Build series for each year's growth
            for ($year = $startYear + 1; $year <= $endYear; $year++) {
                $growthData = [];
                
                foreach ($data as $item) {
                    $value = 0;
                    foreach ($item['yearly_data'] as $yearData) {
                        if ($yearData['tahun'] == $year && isset($yearData['growth_rate'])) {
                            $value = round($yearData['growth_rate'], 2);
                            break;
                        }
                    }
                    $growthData[] = $value;
                }
                
                $series[] = [
                    'name' => "Growth " . ($year - 1) . "-" . $year,
                    'data' => $growthData
                ];
            }
            
            return [
                'categories' => $categories,
                'series' => $series
            ];
        } catch (\Exception $e) {
            Log::error('Error in getGrowthChartData: ' . $e->getMessage());
            return [
                'categories' => [],
                'series' => []
            ];
        }
    }
    
    /**
     * Get detailed table data
     */
    public function getTableData($startYear, $endYear, $level = null, $parentKode = null)
    {
        try {
            $data = $this->getMultiYearTrend($startYear, $endYear, $level, $parentKode);
            
            $tableData = [];
            
            foreach ($data as $item) {
                $row = [
                    'kode' => $item['kode_rekening']->kode,
                    'nama' => $item['kode_rekening']->nama,
                ];
                
                // Add yearly data
                foreach ($item['yearly_data'] as $yearData) {
                    $row['tahun_' . $yearData['tahun']] = [
                        'realisasi' => $yearData['realisasi'],
                        'target' => $yearData['target'],
                        'persentase' => $yearData['persentase_capaian'],
                        'growth' => isset($yearData['growth_rate']) ? $yearData['growth_rate'] : 0
                    ];
                }
                
                // Calculate average growth
                $growthCount = 0;
                $growthSum = 0;
                foreach ($item['yearly_data'] as $yearData) {
                    if (isset($yearData['growth_rate']) && $yearData['growth_rate'] !== null) {
                        $growthSum += $yearData['growth_rate'];
                        $growthCount++;
                    }
                }
                
                $row['avg_growth'] = $growthCount > 0 ? round($growthSum / $growthCount, 2) : 0;
                
                $tableData[] = $row;
            }
            
            return $tableData;
        } catch (\Exception $e) {
            Log::error('Error in getTableData: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Private helper methods
     */
    private function getKodeRekeningByFilter($level = null, $parentKode = null)
    {
        $query = KodeRekening::where('is_active', true);
        
        if ($level) {
            $query->where('level', $level);
        }
        
        if ($parentKode) {
            if (is_numeric($parentKode)) {
                // If numeric, assume it's parent_id
                $query->where('parent_id', $parentKode);
            } else {
                // If string, search by kode pattern
                $query->where('kode', 'like', $parentKode . '%');
                if ($level) {
                    $query->where('level', $level);
                }
            }
        } else {
            // Default to level 2 if no filter specified
            $query->where('level', 2);
        }
        
        // Add limit for performance during testing - remove this in production
        // $query->limit(5);
        
        return $query->orderBy('kode', 'asc')->get();
    }
    
    private function getYearlyData($kodeRekening, $year)
    {
        try {
            // For level 2, we need to aggregate all children
            if ($kodeRekening->level == 2) {
                // Get all descendant IDs efficiently
                $kodeRekeningIds = $this->getAllDescendantIds($kodeRekening->id);
            } else {
                // For other levels, just use direct children
                $kodeRekeningIds = $this->getAllChildIds($kodeRekening->id, 3); // Max depth 3
            }
            
            // Always include the parent itself
            $kodeRekeningIds[] = $kodeRekening->id;
            
            // Remove duplicates
            $kodeRekeningIds = array_unique($kodeRekeningIds);
            
            // Get realisasi with optimized query
            $realisasi = Penerimaan::whereIn('kode_rekening_id', $kodeRekeningIds)
                ->whereYear('tanggal', $year)
                ->sum('jumlah');
            
            // Get target from active tahun anggaran
            $tahunAnggaran = TahunAnggaran::where('tahun', $year)
                ->where('is_active', true)
                ->orderBy('jenis_anggaran', 'desc') // Prioritize 'perubahan' over 'murni'
                ->first();
            
            $target = 0;
            if ($tahunAnggaran) {
                $target = TargetAnggaran::where('tahun_anggaran_id', $tahunAnggaran->id)
                    ->whereIn('kode_rekening_id', $kodeRekeningIds)
                    ->sum('jumlah');
            }
            
            // If no target but has realisasi, use realisasi as baseline
            if ($target == 0 && $realisasi > 0) {
                $target = $realisasi;
            }
            
            // Calculate achievement percentage
            $persentase = $target > 0 ? ($realisasi / $target) * 100 : 0;
            
            return [
                'tahun' => $year,
                'realisasi' => $realisasi,
                'target' => $target,
                'persentase_capaian' => round($persentase, 2),
                'growth_rate' => null // Will be calculated later
            ];
        } catch (\Exception $e) {
            Log::error('Error in getYearlyData: ' . $e->getMessage());
            return [
                'tahun' => $year,
                'realisasi' => 0,
                'target' => 0,
                'persentase_capaian' => 0,
                'growth_rate' => null
            ];
        }
    }
    
    private function calculateGrowthRates($yearlyData)
    {
        $previousYear = null;
        
        for ($i = 0; $i < count($yearlyData); $i++) {
            if ($previousYear !== null) {
                $growth = 0;
                if ($previousYear['realisasi'] > 0) {
                    $growth = (($yearlyData[$i]['realisasi'] - $previousYear['realisasi']) / $previousYear['realisasi']) * 100;
                } elseif ($yearlyData[$i]['realisasi'] > 0) {
                    $growth = 100; // From 0 to positive is 100% growth
                }
                
                $yearlyData[$i]['growth_rate'] = round($growth, 2);
            }
            
            $previousYear = $yearlyData[$i];
        }
        
        return $yearlyData;
    }
    
    /**
     * Get all child IDs with depth limit to prevent infinite recursion
     */
    private function getAllChildIds($parentId, $maxDepth = 5, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }
        
        $ids = [];
        $children = KodeRekening::where('parent_id', $parentId)
            ->where('is_active', true)
            ->select('id') // Only select ID for performance
            ->get();
            
        foreach ($children as $child) {
            $ids[] = $child->id;
            $childIds = $this->getAllChildIds($child->id, $maxDepth, $currentDepth + 1);
            $ids = array_merge($ids, $childIds);
        }
        
        return $ids;
    }
    
    /**
     * Get all descendant IDs more efficiently for level 2
     */
    private function getAllDescendantIds($parentId)
    {
        // Use a more efficient query for getting all descendants
        $parent = KodeRekening::find($parentId);
        if (!$parent) {
            return [];
        }
        
        // Get all descendants based on kode pattern
        $descendants = KodeRekening::where('kode', 'like', $parent->kode . '.%')
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();
            
        return $descendants;
    }
    
    private function getTopPerformers($data, $limit = 3)
    {
        $performers = [];
        
        foreach ($data as $item) {
            $growthSum = 0;
            $growthCount = 0;
            $latestGrowth = 0;
            
            foreach ($item['yearly_data'] as $yearData) {
                if (isset($yearData['growth_rate']) && $yearData['growth_rate'] !== null) {
                    $growthSum += $yearData['growth_rate'];
                    $growthCount++;
                    $latestGrowth = $yearData['growth_rate']; // Keep updating to get latest
                }
            }
            
            if ($growthCount > 0) {
                $avgGrowth = $growthSum / $growthCount;
                
                $performers[] = [
                    'nama' => $item['kode_rekening']->nama,
                    'kode' => $item['kode_rekening']->kode,
                    'avg_growth' => round($avgGrowth, 2),
                    'latest_growth' => $latestGrowth
                ];
            }
        }
        
        // Sort by average growth descending
        usort($performers, function($a, $b) {
            return $b['avg_growth'] <=> $a['avg_growth'];
        });
        
        return array_slice($performers, 0, $limit);
    }
    
    private function getDecliningCategories($data, $threshold = -10)
    {
        $declining = [];
        
        foreach ($data as $item) {
            // Get last year data with growth rate
            $lastGrowth = null;
            foreach (array_reverse($item['yearly_data']) as $yearData) {
                if (isset($yearData['growth_rate']) && $yearData['growth_rate'] !== null) {
                    $lastGrowth = $yearData['growth_rate'];
                    break;
                }
            }
            
            if ($lastGrowth !== null && $lastGrowth < $threshold) {
                $declining[] = [
                    'nama' => $item['kode_rekening']->nama,
                    'kode' => $item['kode_rekening']->kode,
                    'growth_rate' => $lastGrowth
                ];
            }
        }
        
        return $declining;
    }
    
    private function getAverageAchievement($data, $year)
    {
        $achievementSum = 0;
        $achievementCount = 0;
        
        foreach ($data as $item) {
            foreach ($item['yearly_data'] as $yearData) {
                if ($yearData['tahun'] == $year && $yearData['target'] > 0) {
                    $achievementSum += $yearData['persentase_capaian'];
                    $achievementCount++;
                }
            }
        }
        
        return $achievementCount > 0 ? $achievementSum / $achievementCount : 0;
    }
    
    /**
     * Export data to Excel
     */
    public function exportToExcel($startYear, $endYear, $level = null, $parentKode = null)
    {
        $data = $this->getTableData($startYear, $endYear, $level, $parentKode);
        
        // Format data for Excel export
        $exportData = [];
        $headers = ['Kode', 'Nama'];
        
        // Add year headers
        for ($year = $startYear; $year <= $endYear; $year++) {
            $headers[] = "Realisasi $year";
            $headers[] = "Target $year";
            $headers[] = "Capaian $year (%)";
            if ($year > $startYear) {
                $headers[] = "Growth $year (%)";
            }
        }
        $headers[] = 'Avg Growth (%)';
        
        // Build rows
        foreach ($data as $row) {
            $exportRow = [
                $row['kode'],
                $row['nama']
            ];
            
            for ($year = $startYear; $year <= $endYear; $year++) {
                if (isset($row['tahun_' . $year])) {
                    $yearData = $row['tahun_' . $year];
                    $exportRow[] = $yearData['realisasi'];
                    $exportRow[] = $yearData['target'];
                    $exportRow[] = $yearData['persentase'];
                    if ($year > $startYear) {
                        $exportRow[] = $yearData['growth'];
                    }
                } else {
                    $exportRow[] = 0;
                    $exportRow[] = 0;
                    $exportRow[] = 0;
                    if ($year > $startYear) {
                        $exportRow[] = 0;
                    }
                }
            }
            
            $exportRow[] = $row['avg_growth'];
            $exportData[] = $exportRow;
        }
        
        return [
            'headers' => $headers,
            'data' => $exportData
        ];
    }

    /**
 * Add these methods to your existing TrendAnalysisService.php
 * These methods leverage the database views you've created
 */

/**
 * Get monthly comparison using database view
 * Much faster than previous implementation
 */
public function getMonthlyComparisonOptimized($month, $startYear, $endYear, $level = null, $parentKode = null)
{
    try {
        // Build query using the monthly view
        $query = DB::table('v_penerimaan_monthly as vm')
            ->join('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
            ->where('vm.bulan', $month)
            ->whereBetween('vm.tahun', [$startYear, $endYear])
            ->where('kr.is_active', true);
        
        // Apply filters
        if ($level !== null) {
            $query->where('kr.level', $level);
        }
        
        if ($parentKode !== null) {
            if (is_numeric($parentKode)) {
                // Get all descendants of this parent
                $parent = KodeRekening::find($parentKode);
                if ($parent) {
                    $query->where('kr.kode', 'like', $parent->kode . '%');
                }
            } else {
                $query->where('kr.kode', 'like', $parentKode . '%');
            }
        } else {
            // Default to level 2 for overview
            $query->where('kr.level', 2);
        }
        
        // Get the data
        $data = $query->select(
            'vm.tahun',
            'vm.bulan',
            'vm.nama_bulan',
            'kr.id as kode_rekening_id',
            'kr.kode',
            'kr.nama',
            'kr.level',
            DB::raw('SUM(vm.total_penerimaan) as total_penerimaan'),
            DB::raw('SUM(vm.jumlah_transaksi) as jumlah_transaksi')
        )
        ->groupBy('vm.tahun', 'vm.bulan', 'vm.nama_bulan', 'kr.id', 'kr.kode', 'kr.nama', 'kr.level')
        ->orderBy('kr.kode')
        ->orderBy('vm.tahun')
        ->get();
        
        // Group by kode_rekening for easier processing
        $grouped = $data->groupBy('kode_rekening_id');
        
        $results = [];
        foreach ($grouped as $kodeRekeningId => $monthlyData) {
            // Get kode rekening info from first item
            $firstItem = $monthlyData->first();
            
            $kodeRekening = (object)[
                'id' => $kodeRekeningId,
                'kode' => $firstItem->kode,
                'nama' => $firstItem->nama,
                'level' => $firstItem->level
            ];
            
            // Build yearly data array
            $yearlyData = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $yearData = $monthlyData->firstWhere('tahun', $year);
                
                if ($yearData) {
                    $yearlyData[] = [
                        'tahun' => $year,
                        'bulan' => $month,
                        'nama_bulan' => $yearData->nama_bulan,
                        'realisasi' => (float)$yearData->total_penerimaan,
                        'jumlah_transaksi' => $yearData->jumlah_transaksi,
                        'growth_rate' => null
                    ];
                } else {
                    // No data for this year
                    $yearlyData[] = [
                        'tahun' => $year,
                        'bulan' => $month,
                        'nama_bulan' => Carbon::create()->month($month)->format('F'),
                        'realisasi' => 0,
                        'jumlah_transaksi' => 0,
                        'growth_rate' => null
                    ];
                }
            }
            
            // Calculate growth rates
            $yearlyData = $this->calculateMonthlyGrowthRates($yearlyData);
            
            $results[] = [
                'kode_rekening' => $kodeRekening,
                'monthly_data' => $yearlyData
            ];
        }
        
        return $results;
    } catch (\Exception $e) {
        Log::error('Error in getMonthlyComparisonOptimized: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get monthly growth data directly from view
 * Even faster for growth analysis
 */
public function getMonthlyGrowthDirect($month, $startYear, $endYear, $level = null, $parentKode = null)
{
    try {
        $query = DB::table('v_monthly_growth_rate as vg')
            ->join('kode_rekening as kr', 'vg.kode_rekening_id', '=', 'kr.id')
            ->where('vg.bulan', $month)
            ->whereBetween('vg.current_year', [$startYear, $endYear])
            ->where('kr.is_active', true);
        
        // Apply filters same as above
        if ($level !== null) {
            $query->where('kr.level', $level);
        }
        
        if ($parentKode !== null) {
            if (is_numeric($parentKode)) {
                $parent = KodeRekening::find($parentKode);
                if ($parent) {
                    $query->where('kr.kode', 'like', $parent->kode . '%');
                }
            } else {
                $query->where('kr.kode', 'like', $parentKode . '%');
            }
        } else {
            $query->where('kr.level', 2);
        }
        
        return $query->select(
            'vg.*',
            'kr.nama'
        )
        ->orderBy('kr.kode')
        ->orderBy('vg.current_year')
        ->get();
    } catch (\Exception $e) {
        Log::error('Error in getMonthlyGrowthDirect: ' . $e->getMessage());
        return collect();
    }
}

/**
 * Get monthly chart data - Optimized version
 */
public function getMonthlyChartDataOptimized($month, $startYear, $endYear, $level = null, $parentKode = null)
{
    try {
        // Use optimized method
        $data = $this->getMonthlyComparisonOptimized($month, $startYear, $endYear, $level, $parentKode);
        
        if (empty($data)) {
            return [
                'categories' => [],
                'series' => [],
                'month' => $month,
                'monthName' => ''
            ];
        }
        
        $categories = [];
        $series = [];
        
        // Build year categories
        for ($year = $startYear; $year <= $endYear; $year++) {
            $categories[] = (string)$year;
        }
        
        // Build series data
        foreach ($data as $item) {
            $seriesData = [];
            
            foreach ($item['monthly_data'] as $monthData) {
                $seriesData[] = (int)$monthData['realisasi'];
            }
            
            // Only include series with data
            if (array_sum($seriesData) > 0) {
                $series[] = [
                    'name' => $item['kode_rekening']->nama,
                    'data' => $seriesData
                ];
            }
        }
        
        // Get month name from first result
        $monthName = '';
        if (!empty($data) && !empty($data[0]['monthly_data'])) {
            $monthName = $data[0]['monthly_data'][0]['nama_bulan'];
        }
        
        return [
            'categories' => $categories,
            'series' => $series,
            'month' => $month,
            'monthName' => $monthName
        ];
    } catch (\Exception $e) {
        Log::error('Error in getMonthlyChartDataOptimized: ' . $e->getMessage());
        return [
            'categories' => [],
            'series' => [],
            'month' => $month,
            'monthName' => ''
        ];
    }
}

/**
 * Get top performing categories for specific month
 */
public function getMonthlyTopPerformers($month, $startYear, $endYear, $limit = 5)
{
    try {
        return DB::table('v_monthly_growth_rate as vg')
            ->join('kode_rekening as kr', 'vg.kode_rekening_id', '=', 'kr.id')
            ->where('vg.bulan', $month)
            ->where('vg.current_year', $endYear)
            ->where('vg.previous_year', $endYear - 1)
            ->where('kr.is_active', true)
            ->where('kr.level', 2) // Top level categories
            ->whereNotNull('vg.growth_rate_pct')
            ->where('vg.current_amount', '>', 0)
            ->select(
                'kr.kode',
                'kr.nama',
                'vg.current_amount',
                'vg.previous_amount',
                'vg.growth_rate_pct'
            )
            ->orderBy('vg.growth_rate_pct', 'desc')
            ->limit($limit)
            ->get();
    } catch (\Exception $e) {
        Log::error('Error in getMonthlyTopPerformers: ' . $e->getMessage());
        return collect();
    }
}

/**
 * Get monthly trend for all months in a year
 * Useful for seasonal analysis
 */
public function getYearlyMonthlyTrend($year, $kodeRekeningId = null)
{
    try {
        $query = DB::table('v_penerimaan_monthly')
            ->where('tahun', $year);
        
        if ($kodeRekeningId) {
            $query->where('kode_rekening_id', $kodeRekeningId);
        }
        
        return $query->select(
            'bulan',
            'nama_bulan',
            DB::raw('SUM(total_penerimaan) as total'),
            DB::raw('SUM(jumlah_transaksi) as transaksi')
        )
        ->groupBy('bulan', 'nama_bulan')
        ->orderBy('bulan')
        ->get();
    } catch (\Exception $e) {
        Log::error('Error in getYearlyMonthlyTrend: ' . $e->getMessage());
        return collect();
    }
}

/**
 * Compare same month across multiple years
 * Direct query to monthly view
 */
public function getMonthComparisonAcrossYears($month, $kodeRekeningIds = [])
{
    try {
        $query = DB::table('v_penerimaan_monthly as vm')
            ->join('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
            ->where('vm.bulan', $month)
            ->where('kr.is_active', true);
        
        if (!empty($kodeRekeningIds)) {
            $query->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
        }
        
        return $query->select(
            'vm.tahun',
            'vm.nama_bulan',
            'kr.kode',
            'kr.nama',
            'vm.total_penerimaan',
            'vm.jumlah_transaksi'
        )
        ->orderBy('vm.tahun', 'desc')
        ->orderBy('kr.kode')
        ->get();
    } catch (\Exception $e) {
        Log::error('Error in getMonthComparisonAcrossYears: ' . $e->getMessage());
        return collect();
    }
}

/**
 * Get monthly summary with best/worst months
 */
public function getMonthlySummaryAnalysis($year, $level = 2)
{
    try {
        $monthlyTotals = DB::table('v_penerimaan_monthly as vm')
            ->join('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
            ->where('vm.tahun', $year)
            ->where('kr.level', $level)
            ->where('kr.is_active', true)
            ->select(
                'vm.bulan',
                'vm.nama_bulan',
                DB::raw('SUM(vm.total_penerimaan) as total')
            )
            ->groupBy('vm.bulan', 'vm.nama_bulan')
            ->orderBy('total', 'desc')
            ->get();
        
        if ($monthlyTotals->isEmpty()) {
            return null;
        }
        
        return [
            'best_month' => $monthlyTotals->first(),
            'worst_month' => $monthlyTotals->last(),
            'average' => $monthlyTotals->avg('total'),
            'total' => $monthlyTotals->sum('total'),
            'all_months' => $monthlyTotals
        ];
    } catch (\Exception $e) {
        Log::error('Error in getMonthlySummaryAnalysis: ' . $e->getMessage());
        return null;
    }
}
private function calculateMonthlyGrowthRates($monthlyData)
{
    $previousData = null;
    
    for ($i = 0; $i < count($monthlyData); $i++) {
        if ($previousData !== null) {
            $growth = 0;
            if ($previousData['realisasi'] > 0) {
                $growth = (($monthlyData[$i]['realisasi'] - $previousData['realisasi']) / $previousData['realisasi']) * 100;
            } elseif ($monthlyData[$i]['realisasi'] > 0) {
                $growth = 100; // From 0 to positive is 100% growth
            }
            
            $monthlyData[$i]['growth_rate'] = round($growth, 2);
        }
        
        $previousData = $monthlyData[$i];
    }
    
    return $monthlyData;
}

/**
 * Get monthly summary statistics
 */
public function getMonthlySummaryStatistics($month, $startYear, $endYear, $level = null, $parentKode = null)
{
    try {
        // Get monthly comparison data
        $data = $this->getMonthlyComparisonOptimized($month, $startYear, $endYear, $level, $parentKode);
        
        if (empty($data)) {
            return [
                'total_growth' => 0,
                'monthName' => Carbon::create()->month($month)->format('F'),
                'yearly_totals' => []
            ];
        }
        
        // Calculate totals for each year
        $yearlyTotals = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $yearlyTotals[$year] = 0;
        }
        
        foreach ($data as $item) {
            foreach ($item['monthly_data'] as $monthData) {
                $yearlyTotals[$monthData['tahun']] += $monthData['realisasi'];
            }
        }
        
        // Calculate overall growth
        $firstYearTotal = $yearlyTotals[$startYear] ?? 0;
        $lastYearTotal = $yearlyTotals[$endYear] ?? 0;
        
        $totalGrowth = 0;
        if ($firstYearTotal > 0) {
            $totalGrowth = (($lastYearTotal - $firstYearTotal) / $firstYearTotal) * 100;
        }
        
        return [
            'total_growth' => round($totalGrowth, 2),
            'monthName' => $data[0]['monthly_data'][0]['nama_bulan'] ?? Carbon::create()->month($month)->format('F'),
            'yearly_totals' => $yearlyTotals,
            'first_year_total' => $firstYearTotal,
            'last_year_total' => $lastYearTotal
        ];
    } catch (\Exception $e) {
        Log::error('Error in getMonthlySummaryStatistics: ' . $e->getMessage());
        return [
            'total_growth' => 0,
            'monthName' => Carbon::create()->month($month)->format('F'),
            'yearly_totals' => []
        ];
    }
}
}