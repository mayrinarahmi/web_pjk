<?php

namespace App\Services;

use App\Models\KodeRekening;
use App\Models\Penerimaan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TrendAnalysisService
{
    protected $cacheTime = 3600; // 1 hour cache

    /**
     * Get all Level 6 descendant IDs for aggregation
     * Used for Level 3, 4, 5 to show aggregated data
     */
    private function getDescendantLevel6Ids($parentId, $currentLevel)
    {
        try {
            // Jika sudah Level 6, return kosong (tidak perlu agregasi)
            if ($currentLevel >= 6) {
                return [];
            }
            
            // Jika Level 5, langsung ambil Level 6 children
            if ($currentLevel == 5) {
                return KodeRekening::where('parent_id', $parentId)
                    ->where('level', 6)
                    ->where('is_active', true)
                    ->pluck('id')
                    ->toArray();
            }
            
            // Untuk Level 3 & 4, perlu recursive
            $allIds = [];
            $nextLevel = $currentLevel + 1;
            
            // Ambil children level berikutnya
            $children = KodeRekening::where('parent_id', $parentId)
                ->where('level', $nextLevel)
                ->where('is_active', true)
                ->get();
            
            foreach ($children as $child) {
                if ($child->level == 6) {
                    // Jika child sudah Level 6, tambahkan
                    $allIds[] = $child->id;
                } else {
                    // Recursive untuk level lebih dalam
                    $childIds = $this->getDescendantLevel6Ids($child->id, $child->level);
                    $allIds = array_merge($allIds, $childIds);
                }
            }
            
            return array_unique($allIds); // Remove duplicates
            
        } catch (\Exception $e) {
            Log::error('Error getting descendant IDs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get overview data for trend analysis
     */
    public function getOverviewData($years = 3, $view = 'yearly', $month = null)
    {
        $cacheKey = "trend_overview_{$years}_{$view}_{$month}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($years, $view, $month) {
            $currentYear = date('Y');
            $startYear = $currentYear - $years + 1;
            
            try {
                if ($view === 'monthly' && $month !== null) {
                    // Use Query Builder instead of raw SQL
                    $data = DB::table('v_penerimaan_monthly')
                        ->select('tahun', DB::raw('SUM(jumlah) as total'))
                        ->where('bulan', $month)
                        ->whereBetween('tahun', [$startYear, $currentYear])
                        ->groupBy('tahun')
                        ->orderBy('tahun')
                        ->get();

                    // Build dataMap for quick lookup
                    $dataMap = [];
                    foreach ($data as $row) {
                        $dataMap[$row->tahun] = (float)$row->total;
                    }

                    // Build full year range — include years without data as 0
                    $categories = [];
                    $seriesData = [];
                    for ($year = $startYear; $year <= $currentYear; $year++) {
                        $categories[] = (string)$year;
                        $seriesData[] = $dataMap[$year] ?? 0;
                    }

                    return [
                        'categories' => $categories,
                        'series' => [
                            [
                                'name' => 'PENDAPATAN DAERAH',
                                'data' => $seriesData
                            ]
                        ]
                    ];
                    
                } elseif ($view === 'monthly') {
                    // Monthly view untuk satu tahun
                    $data = DB::table('v_penerimaan_monthly')
                        ->select('bulan', DB::raw('SUM(jumlah) as total'))
                        ->where('tahun', $currentYear)
                        ->groupBy('bulan')
                        ->orderBy('bulan')
                        ->get();
                    
                    $categories = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                    $seriesData = array_fill(0, 12, 0);
                    
                    foreach ($data as $row) {
                        $seriesData[$row->bulan - 1] = (float)$row->total;
                    }
                    
                    return [
                        'categories' => $categories,
                        'series' => [
                            [
                                'name' => 'PENDAPATAN DAERAH',
                                'data' => $seriesData
                            ]
                        ]
                    ];
                    
                } else {
                    // Yearly view
                    $data = DB::table('v_penerimaan_monthly')
                        ->select('tahun', DB::raw('SUM(jumlah) as total'))
                        ->whereBetween('tahun', [$startYear, $currentYear])
                        ->groupBy('tahun')
                        ->orderBy('tahun')
                        ->get();
                    
                    $categories = [];
                    $seriesData = [];
                    
                    for ($year = $startYear; $year <= $currentYear; $year++) {
                        $categories[] = (string)$year;
                        $found = false;
                        foreach ($data as $row) {
                            if ($row->tahun == $year) {
                                $seriesData[] = (float)$row->total;
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $seriesData[] = 0;
                        }
                    }
                    
                    return [
                        'categories' => $categories,
                        'series' => [
                            [
                                'name' => 'PENDAPATAN DAERAH',
                                'data' => $seriesData
                            ]
                        ]
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error in getOverviewData: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Get monthly chart data with proper filtering for level 3, 4, 5, 6
     * UPDATED: Added aggregation for Level 3, 4, 5
     */
     public function getMonthlyChartData($level, $parentKode, $tahun, $limit = 10, $specificId = null, $month = null, $years = 3)
    {
        $cacheKey = "monthly_chart_{$level}_{$parentKode}_{$tahun}_{$limit}_{$specificId}_{$month}_{$years}";

        return Cache::remember($cacheKey, $this->cacheTime, function () use ($level, $parentKode, $tahun, $limit, $specificId, $month, $years) {

            // Initialize parent variable
            $parent = null;
            if ($parentKode && is_numeric($parentKode)) {
                $parent = KodeRekening::find($parentKode);
            }

            // SPECIAL HANDLING: If month filter is applied, show year-over-year comparison
            if ($month !== null) {
                // Determine years range for comparison — respect the $years parameter
                $currentYear = date('Y');
                $startYear = $currentYear - $years + 1;
                
                // Build categories (years)
                $categories = [];
                for ($year = $startYear; $year <= $currentYear; $year++) {
                    $categories[] = (string)$year;
                }
                
                // Build query for specific month across multiple years
                $query = DB::table('v_penerimaan_monthly as vm')
                    ->select(
                        'vm.tahun',
                        DB::raw('SUM(vm.jumlah) as total')
                    )
                    ->where('vm.bulan', $month)
                    ->whereBetween('vm.tahun', [$startYear, $currentYear])
                    ->whereNotNull('vm.jumlah')
                    ->where('vm.jumlah', '>', 0);
                
                // Apply filtering based on level and parent
                if ($specificId !== null) {
                    // Level 6 selected directly
                    $query->where('vm.kode_rekening_id', $specificId);
                    
                } elseif ($parent && in_array($parent->level, [3, 4, 5])) {
                    // Level 3, 4, 5: Aggregate all Level 6 descendants
                    $descendantIds = $this->getDescendantLevel6Ids($parentKode, $parent->level);
                    
                    if (!empty($descendantIds)) {
                        $query->whereIn('vm.kode_rekening_id', $descendantIds)
                              ->where('vm.level', 6);
                    } else {
                        return [
                            'categories' => [],
                            'series' => []
                        ];
                    }
                } elseif ($parent) {
                    // Level 1, 2: Show direct children
                    $query->where('vm.parent_id', $parentKode)
                          ->where('vm.level', $level);
                } else {
                    // No parent - top level
                    $query->where('vm.level', $level);
                }
                
                // Group by year and get results
                $query->groupBy('vm.tahun');
                $results = $query->orderBy('vm.tahun')->get();
                
                // Prepare data array
                $dataMap = [];
                foreach ($results as $row) {
                    $dataMap[$row->tahun] = (float)$row->total;
                }
                
                // Build series data ensuring all years have values
                $seriesData = [];
                for ($year = $startYear; $year <= $currentYear; $year++) {
                    $seriesData[] = $dataMap[$year] ?? 0;
                }
                
                // Determine series name
                $monthName = $this->getMonthName($month);
                if ($parent) {
                    $seriesName = $parent->nama . ' - ' . $monthName;
                } elseif ($specificId) {
                    $item = KodeRekening::find($specificId);
                    $seriesName = ($item ? $item->nama : 'Item') . ' - ' . $monthName;
                } else {
                    $seriesName = $monthName;
                }
                
                Log::info('Month comparison data', [
                    'month' => $month,
                    'categories' => $categories,
                    'data' => $seriesData
                ]);
                
                return [
                    'categories' => $categories,
                    'series' => [
                        [
                            'name' => $seriesName,
                            'data' => $seriesData
                        ]
                    ]
                ];
                
            } else {
                // EXISTING LOGIC: Show all months for current year
                // Base query dari view
                $baseQuery = DB::table('v_penerimaan_monthly as vm')
                    ->select(
                        'vm.kode_rekening_id',
                        'vm.nama_rekening',
                        'vm.bulan',
                        'vm.level',
                        'vm.parent_id',
                        DB::raw('SUM(vm.jumlah) as total')
                    )
                    ->where('vm.tahun', $tahun)
                    ->whereNotNull('vm.jumlah')
                    ->where('vm.jumlah', '>', 0);
                
                // Apply filtering based on level and parent
                if ($specificId !== null) {
                    // Level 6 selected directly
                    $baseQuery->where('vm.kode_rekening_id', $specificId);
                    Log::info('Filtering by specific ID', ['specificId' => $specificId]);
                    
                } elseif ($parent && in_array($parent->level, [3, 4, 5])) {
                    // Level 3, 4, 5: Aggregate all Level 6 descendants
                    Log::info('Aggregating for Level ' . $parent->level, ['parent_id' => $parentKode]);
                    
                    $descendantIds = $this->getDescendantLevel6Ids($parentKode, $parent->level);
                    
                    if (!empty($descendantIds)) {
                        $baseQuery->whereIn('vm.kode_rekening_id', $descendantIds)
                                  ->where('vm.level', 6);
                        
                        Log::info('Found descendants', [
                            'parent_level' => $parent->level,
                            'descendant_count' => count($descendantIds),
                            'sample_ids' => array_slice($descendantIds, 0, 5)
                        ]);
                    } else {
                        Log::warning('No descendants found for Level ' . $parent->level, ['parent_id' => $parentKode]);
                        return [
                            'categories' => [],
                            'series' => []
                        ];
                    }
                } elseif ($parent && $parent->level < 3) {
                    // Level 1, 2: Show direct children
                    $baseQuery->where('vm.parent_id', $parentKode)
                              ->where('vm.level', $level);
                } else {
                    // No parent specified - get top level items
                    $baseQuery->where('vm.level', $level);
                }
                
                // GROUP BY all non-aggregate columns
                $baseQuery->groupBy(
                    'vm.kode_rekening_id',
                    'vm.nama_rekening',
                    'vm.bulan',
                    'vm.level',
                    'vm.parent_id'
                )->orderBy('total', 'desc');
                
                $results = $baseQuery->get();
                
                Log::info('Query results', [
                    'count' => $results->count(),
                    'parentKode' => $parentKode
                ]);
                
                if ($results->isEmpty()) {
                    return [
                        'categories' => [],
                        'series' => []
                    ];
                }
                
                // Process all months data
                $itemsMap = [];
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                
                foreach ($results as $row) {
                    $itemId = $row->kode_rekening_id;
                    
                    if (!isset($itemsMap[$itemId])) {
                        $itemsMap[$itemId] = [
                            'nama' => $this->truncateName($row->nama_rekening),
                            'data' => array_fill(0, 12, 0),
                            'total' => 0
                        ];
                    }
                    
                    $monthIndex = $row->bulan - 1;
                    $itemsMap[$itemId]['data'][$monthIndex] = (float)$row->total;
                    $itemsMap[$itemId]['total'] += (float)$row->total;
                }
                
                // Sort by total
                uasort($itemsMap, function($a, $b) {
                    return $b['total'] <=> $a['total'];
                });
                
                // Limit results
                $itemsMap = array_slice($itemsMap, 0, $limit, true);
                
                // Prepare series data
                $series = [];
                
                // For Level 3, 4, 5 aggregation - show as single series
                if ($parent && in_array($parent->level, [3, 4, 5])) {
                    // Aggregate all items into one series
                    $aggregatedData = array_fill(0, 12, 0);
                    foreach ($itemsMap as $item) {
                        for ($i = 0; $i < 12; $i++) {
                            $aggregatedData[$i] += $item['data'][$i];
                        }
                    }
                    
                    $series[] = [
                        'name' => $parent->nama . ' (Total)',
                        'data' => $aggregatedData
                    ];
                } else {
                    // Normal behavior for other levels
                    foreach ($itemsMap as $item) {
                        $series[] = [
                            'name' => $item['nama'],
                            'data' => $item['data']
                        ];
                    }
                }
                
                return [
                    'categories' => $months,
                    'series' => $series
                ];
            }
        });
    }

    /**
     * Get yearly chart data
     * UPDATED: Added aggregation for Level 3, 4, 5
     */
    public function getYearlyChartData($level, $parentKode, $years = 3, $limit = 10, $specificId = null)
    {
        $cacheKey = "yearly_chart_{$level}_{$parentKode}_{$years}_{$limit}_{$specificId}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($level, $parentKode, $years, $limit, $specificId) {
            $currentYear = date('Y');
            $startYear = $currentYear - $years + 1;
            
            // Query yang sudah dioptimasi menggunakan view
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.kode_rekening_id',
                    'vm.nama_rekening',
                    'vm.tahun',
                    'vm.level',
                    'vm.parent_id',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->whereBetween('vm.tahun', [$startYear, $currentYear])
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            // UPDATED: Special handling for Level 3, 4, 5 aggregation
            if ($parentKode !== null && is_numeric($parentKode)) {
                $parent = KodeRekening::find($parentKode);
                
                if ($parent && in_array($parent->level, [3, 4, 5])) {
                    // Level 3, 4, 5: Aggregate all Level 6 descendants
                    Log::info('Yearly aggregation for Level ' . $parent->level, ['parent_id' => $parentKode]);
                    
                    $descendantIds = $this->getDescendantLevel6Ids($parentKode, $parent->level);
                    
                    if (!empty($descendantIds)) {
                        $query->whereIn('vm.kode_rekening_id', $descendantIds)
                              ->where('vm.level', 6);
                    } else {
                        return [
                            'categories' => [],
                            'series' => []
                        ];
                    }
                } elseif ($parent && $parent->level < 3) {
                    // Level 1, 2: Show direct children
                    $query->where('vm.parent_id', $parentKode)
                          ->where('vm.level', $level);
                } elseif ($specificId !== null) {
                    // Level 6 selected directly
                    $query->where('vm.kode_rekening_id', $specificId);
                } else {
                    // Default case
                    $query->where('vm.parent_id', $parentKode)
                          ->where('vm.level', $level);
                }
            } elseif ($specificId !== null) {
                // Level 6 selected directly
                $query->where('vm.kode_rekening_id', $specificId);
            } else {
                // Top level
                $query->where('vm.level', $level);
            }
            
            $query->groupBy('vm.kode_rekening_id', 'vm.nama_rekening', 'vm.tahun', 'vm.level', 'vm.parent_id');
            
            $results = $query->get();
            
            if ($results->isEmpty()) {
                return [
                    'categories' => [],
                    'series' => []
                ];
            }
            
            // Prepare chart data
            $categories = [];
            for ($year = $startYear; $year <= $currentYear; $year++) {
                $categories[] = (string)$year;
            }
            
            // For Level 3, 4, 5 - aggregate into single series
            if ($parentKode && isset($parent) && in_array($parent->level, [3, 4, 5])) {
                $yearlyTotals = [];
                
                foreach ($results as $row) {
                    if (!isset($yearlyTotals[$row->tahun])) {
                        $yearlyTotals[$row->tahun] = 0;
                    }
                    $yearlyTotals[$row->tahun] += (float)$row->total;
                }
                
                $data = [];
                for ($year = $startYear; $year <= $currentYear; $year++) {
                    $data[] = $yearlyTotals[$year] ?? 0;
                }
                
                return [
                    'categories' => $categories,
                    'series' => [
                        [
                            'name' => $parent->nama . ' (Total)',
                            'data' => $data
                        ]
                    ]
                ];
            } else {
                // Normal behavior for other levels
                $kategoris = [];
                foreach ($results as $row) {
                    $kategoriId = $row->kode_rekening_id;
                    
                    if (!isset($kategoris[$kategoriId])) {
                        $kategoris[$kategoriId] = [
                            'nama' => $this->truncateName($row->nama_rekening),
                            'data' => [],
                            'total' => 0
                        ];
                    }
                    
                    $kategoris[$kategoriId]['data'][$row->tahun] = (float)$row->total;
                    $kategoris[$kategoriId]['total'] += (float)$row->total;
                }
                
                // Sort by total
                uasort($kategoris, function($a, $b) {
                    return $b['total'] <=> $a['total'];
                });
                
                // Limit
                $kategoris = array_slice($kategoris, 0, $limit, true);
                
                $series = [];
                foreach ($kategoris as $kategori) {
                    $data = [];
                    for ($year = $startYear; $year <= $currentYear; $year++) {
                        $data[] = $kategori['data'][$year] ?? 0;
                    }
                    
                    $series[] = [
                        'name' => $kategori['nama'],
                        'data' => $data
                    ];
                }
                
                return [
                    'categories' => $categories,
                    'series' => $series
                ];
            }
        });
    }

    /**
     * Alias method for backward compatibility
     */
    public function getMonthlyChartDataOptimized($level, $parentKode, $tahun, $limit = 10, $specificId = null, $month = null, $years = 3)
    {
        return $this->getMonthlyChartData($level, $parentKode, $tahun, $limit, $specificId, $month, $years);
    }

    /**
     * Yearly optimized version - alias method
     */
    public function getYearlyChartDataOptimized($level, $parentKode, $years = 3, $limit = 10, $specificId = null)
    {
        return $this->getYearlyChartData($level, $parentKode, $years, $limit, $specificId);
    }

    /**
     * Get comparison data between periods
     */
    public function getComparisonData($level, $parentKode, $period1Start, $period1End, $period2Start, $period2End)
    {
        $cacheKey = "comparison_{$level}_{$parentKode}_{$period1Start}_{$period1End}_{$period2Start}_{$period2End}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($level, $parentKode, $period1Start, $period1End, $period2Start, $period2End) {
            // Get data for period 1
            $period1Data = $this->getPeriodData($level, $parentKode, $period1Start, $period1End);
            
            // Get data for period 2
            $period2Data = $this->getPeriodData($level, $parentKode, $period2Start, $period2End);
            
            // Calculate comparison
            $comparison = [];
            foreach ($period1Data as $item) {
                $kodeRekeningId = $item->kode_rekening_id;
                $period2Item = $period2Data->firstWhere('kode_rekening_id', $kodeRekeningId);
                
                $period1Total = (float)$item->total;
                $period2Total = $period2Item ? (float)$period2Item->total : 0;
                
                $difference = $period2Total - $period1Total;
                $percentageChange = 0;
                
                if ($period1Total > 0) {
                    $percentageChange = ($difference / $period1Total) * 100;
                }
                
                $comparison[] = [
                    'kode_rekening_id' => $kodeRekeningId,
                    'nama' => $item->nama_rekening,
                    'period1_total' => $period1Total,
                    'period2_total' => $period2Total,
                    'difference' => $difference,
                    'percentage_change' => round($percentageChange, 2)
                ];
            }
            
            // Sort by absolute difference
            usort($comparison, function($a, $b) {
                return abs($b['difference']) <=> abs($a['difference']);
            });
            
            return $comparison;
        });
    }

    /**
     * Get period data helper
     */
    private function getPeriodData($level, $parentKode, $startDate, $endDate)
    {
        $query = DB::table('v_penerimaan_monthly as vm')
            ->select(
                'vm.kode_rekening_id',
                'vm.nama_rekening',
                DB::raw('SUM(vm.jumlah) as total')
            )
            ->whereBetween('vm.tanggal', [$startDate, $endDate])
            ->whereNotNull('vm.jumlah')
            ->where('vm.jumlah', '>', 0);
        
        if ($parentKode !== null) {
            if (is_numeric($parentKode)) {
                $query->where('vm.parent_id', $parentKode)
                      ->where('vm.level', $level);
            } else {
                $kodeRekeningIds = KodeRekening::where('kode', 'LIKE', $parentKode . '%')
                    ->where('level', $level)
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($kodeRekeningIds)) {
                    $query->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
                }
            }
        } else {
            $query->where('vm.level', $level);
        }
        
        return $query->groupBy('vm.kode_rekening_id', 'vm.nama_rekening')
                     ->orderBy('total', 'desc')
                     ->get();
    }

    /**
     * Get top performers
     */
    public function getTopPerformers($tahun, $limit = 10, $level = null)
    {
        $cacheKey = "top_performers_{$tahun}_{$limit}_{$level}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun, $limit, $level) {
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.kode_rekening_id',
                    'vm.nama_rekening',
                    'vm.level',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($level !== null) {
                $query->where('vm.level', $level);
            }
            
            return $query->groupBy('vm.kode_rekening_id', 'vm.nama_rekening', 'vm.level')
                         ->orderBy('total', 'desc')
                         ->limit($limit)
                         ->get();
        });
    }

    /**
     * Get bottom performers
     */
    public function getBottomPerformers($tahun, $limit = 10, $level = null)
    {
        $cacheKey = "bottom_performers_{$tahun}_{$limit}_{$level}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun, $limit, $level) {
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.kode_rekening_id',
                    'vm.nama_rekening',
                    'vm.level',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($level !== null) {
                $query->where('vm.level', $level);
            }
            
            return $query->groupBy('vm.kode_rekening_id', 'vm.nama_rekening', 'vm.level')
                         ->orderBy('total', 'asc')
                         ->limit($limit)
                         ->get();
        });
    }

    /**
     * Get growth analysis
     */
    public function getGrowthAnalysis($level, $parentKode, $startYear, $endYear)
    {
        $cacheKey = "growth_analysis_{$level}_{$parentKode}_{$startYear}_{$endYear}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($level, $parentKode, $startYear, $endYear) {
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.kode_rekening_id',
                    'vm.nama_rekening',
                    'vm.tahun',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->whereBetween('vm.tahun', [$startYear, $endYear])
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($parentKode !== null) {
                if (is_numeric($parentKode)) {
                    $query->where('vm.parent_id', $parentKode)
                          ->where('vm.level', $level);
                } else {
                    $kodeRekeningIds = KodeRekening::where('kode', 'LIKE', $parentKode . '%')
                        ->where('level', $level)
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($kodeRekeningIds)) {
                        $query->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
                    }
                }
            } else {
                $query->where('vm.level', $level);
            }
            
            $results = $query->groupBy('vm.kode_rekening_id', 'vm.nama_rekening', 'vm.tahun')
                             ->orderBy('vm.kode_rekening_id')
                             ->orderBy('vm.tahun')
                             ->get();
            
            // Process growth data
            $growthData = [];
            $grouped = $results->groupBy('kode_rekening_id');
            
            foreach ($grouped as $kodeRekeningId => $yearlyData) {
                $firstYear = $yearlyData->firstWhere('tahun', $startYear);
                $lastYear = $yearlyData->firstWhere('tahun', $endYear);
                
                if ($firstYear && $lastYear) {
                    $firstTotal = (float)$firstYear->total;
                    $lastTotal = (float)$lastYear->total;
                    
                    $growth = 0;
                    if ($firstTotal > 0) {
                        $growth = (($lastTotal - $firstTotal) / $firstTotal) * 100;
                    }
                    
                    $growthData[] = [
                        'kode_rekening_id' => $kodeRekeningId,
                        'nama' => $firstYear->nama_rekening,
                        'first_year_total' => $firstTotal,
                        'last_year_total' => $lastTotal,
                        'growth_percentage' => round($growth, 2),
                        'yearly_data' => $yearlyData->pluck('total', 'tahun')->toArray()
                    ];
                }
            }
            
            // Sort by growth percentage
            usort($growthData, function($a, $b) {
                return $b['growth_percentage'] <=> $a['growth_percentage'];
            });
            
            return $growthData;
        });
    }

    /**
     * Get seasonal analysis
     */
    public function getSeasonalAnalysis($kodeRekeningId, $years = 3)
    {
        $cacheKey = "seasonal_analysis_{$kodeRekeningId}_{$years}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($kodeRekeningId, $years) {
            $currentYear = date('Y');
            $startYear = $currentYear - $years + 1;
            
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.bulan',
                    'vm.tahun',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.kode_rekening_id', $kodeRekeningId)
                ->whereBetween('vm.tahun', [$startYear, $currentYear])
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0)
                ->groupBy('vm.bulan', 'vm.tahun')
                ->orderBy('vm.tahun')
                ->orderBy('vm.bulan')
                ->get();
            
            // Process seasonal data
            $monthlyAverages = [];
            $monthlyData = [];
            
            for ($month = 1; $month <= 12; $month++) {
                $monthData = $query->where('bulan', $month);
                $monthlyData[$month] = [];
                
                foreach ($monthData as $data) {
                    $monthlyData[$month][$data->tahun] = (float)$data->total;
                }
                
                $average = $monthData->count() > 0 ? $monthData->avg('total') : 0;
                $monthlyAverages[$month] = round($average, 2);
            }
            
            return [
                'monthly_averages' => $monthlyAverages,
                'monthly_data' => $monthlyData,
                'months' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des']
            ];
        });
    }

    /**
     * Search kode rekening
     */
    public function searchKodeRekening($query, $limit = 10)
    {
        return KodeRekening::where('nama', 'LIKE', "%{$query}%")
            ->orWhere('kode', 'LIKE', "%{$query}%")
            ->where('is_active', true)
            ->select('id', 'kode', 'nama', 'level', 'berlaku_mulai')
            ->orderBy('level')
            ->orderBy('kode')
            ->limit($limit)
            ->get();
    }

    /**
     * Get kode rekening hierarchy
     */
    public function getKodeRekeningHierarchy($parentId = null, $maxLevel = null)
    {
        $query = KodeRekening::where('is_active', true);
        
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        } else {
            $query->whereNull('parent_id');
        }
        
        if ($maxLevel !== null) {
            $query->where('level', '<=', $maxLevel);
        }
        
        $items = $query->orderBy('kode')->get();
        
        $hierarchy = [];
        foreach ($items as $item) {
            $children = [];
            if ($maxLevel === null || $item->level < $maxLevel) {
                $children = $this->getKodeRekeningHierarchy($item->id, $maxLevel);
            }
            
            $hierarchy[] = [
                'id' => $item->id,
                'kode' => $item->kode,
                'nama' => $item->nama,
                'level' => $item->level,
                'children' => $children
            ];
        }
        
        return $hierarchy;
    }

    /**
     * Get summary statistics
     */
    public function getSummaryStatistics($tahun, $level = null, $parentKode = null)
    {
        $cacheKey = "summary_stats_{$tahun}_{$level}_{$parentKode}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun, $level, $parentKode) {
            $query = DB::table('v_penerimaan_monthly as vm')
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($level !== null) {
                $query->where('vm.level', $level);
            }
            
            if ($parentKode !== null) {
                if (is_numeric($parentKode)) {
                    $query->where('vm.parent_id', $parentKode);
                } else {
                    $kodeRekeningIds = KodeRekening::where('kode', 'LIKE', $parentKode . '%')
                        ->where('level', $level)
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($kodeRekeningIds)) {
                        $query->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
                    }
                }
            }
            
            $total = $query->sum('vm.jumlah');
            $count = $query->count();
            $average = $count > 0 ? $total / $count : 0;
            
            // Get monthly breakdown
            $monthlyData = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.bulan',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($level !== null) {
                $monthlyData->where('vm.level', $level);
            }
            
            $kodeRekeningIds = [];
            if ($parentKode !== null) {
                if (is_numeric($parentKode)) {
                    $monthlyData->where('vm.parent_id', $parentKode);
                } else {
                    $kodeRekeningIds = KodeRekening::where('kode', 'LIKE', $parentKode . '%')
                        ->where('level', $level)
                        ->pluck('id')
                        ->toArray();
                    
                    if (!empty($kodeRekeningIds)) {
                        $monthlyData->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
                    }
                }
            }
            
            $monthlyTotals = $monthlyData->groupBy('vm.bulan')
                                          ->orderBy('vm.bulan')
                                          ->pluck('total', 'bulan')
                                          ->toArray();
            
            // Calculate growth from previous year
            $previousYearTotal = DB::table('v_penerimaan_monthly as vm')
                ->where('vm.tahun', $tahun - 1)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($level !== null) {
                $previousYearTotal->where('vm.level', $level);
            }
            
            if ($parentKode !== null) {
                if (is_numeric($parentKode)) {
                    $previousYearTotal->where('vm.parent_id', $parentKode);
                } else {
                    if (!empty($kodeRekeningIds)) {
                        $previousYearTotal->whereIn('vm.kode_rekening_id', $kodeRekeningIds);
                    }
                }
            }
            
            $prevTotal = $previousYearTotal->sum('vm.jumlah');
            $growth = 0;
            
            if ($prevTotal > 0) {
                $growth = (($total - $prevTotal) / $prevTotal) * 100;
            }
            
            return [
                'total' => $total,
                'count' => $count,
                'average' => round($average, 2),
                'growth_from_previous_year' => round($growth, 2),
                'monthly_totals' => $monthlyTotals,
                'highest_month' => !empty($monthlyTotals) ? array_keys($monthlyTotals, max($monthlyTotals))[0] : null,
                'lowest_month' => !empty($monthlyTotals) ? array_keys($monthlyTotals, min($monthlyTotals))[0] : null
            ];
        });
    }

    /**
     * Get forecast data based on historical trends
     */
    public function getForecastData($kodeRekeningId, $months = 12)
    {
        $cacheKey = "forecast_{$kodeRekeningId}_{$months}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($kodeRekeningId, $months) {
            // Get historical data for last 3 years
            $currentYear = date('Y');
            $startYear = $currentYear - 3;
            
            $historicalData = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.tahun',
                    'vm.bulan',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.kode_rekening_id', $kodeRekeningId)
                ->whereBetween('vm.tahun', [$startYear, $currentYear])
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0)
                ->groupBy('vm.tahun', 'vm.bulan')
                ->orderBy('vm.tahun')
                ->orderBy('vm.bulan')
                ->get();
            
            // Simple moving average forecast
            $forecast = [];
            $monthlyAverages = [];
            
            for ($month = 1; $month <= 12; $month++) {
                $monthData = $historicalData->where('bulan', $month);
                $average = $monthData->count() > 0 ? $monthData->avg('total') : 0;
                $monthlyAverages[$month] = $average;
            }
            
            // Generate forecast for next N months
            $forecastStartMonth = date('n') + 1;
            $forecastStartYear = date('Y');
            
            for ($i = 0; $i < $months; $i++) {
                $forecastMonth = ($forecastStartMonth + $i - 1) % 12 + 1;
                $forecastYear = $forecastStartYear + floor(($forecastStartMonth + $i - 1) / 12);
                
                // Apply seasonal adjustment
                $baseValue = $monthlyAverages[$forecastMonth];
                
                // Simple growth factor (2% monthly growth)
                $growthFactor = pow(1.02, $i);
                
                $forecast[] = [
                    'year' => $forecastYear,
                    'month' => $forecastMonth,
                    'month_name' => $this->getMonthName($forecastMonth),
                    'value' => round($baseValue * $growthFactor, 2)
                ];
            }
            
            return [
                'historical' => $historicalData,
                'forecast' => $forecast,
                'monthly_averages' => $monthlyAverages
            ];
        });
    }

    /**
     * Get achievement rate against target
     */
    public function getAchievementRate($kodeRekeningId, $tahun, $target = null)
    {
        $cacheKey = "achievement_{$kodeRekeningId}_{$tahun}_{$target}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($kodeRekeningId, $tahun, $target) {
            $actualTotal = DB::table('v_penerimaan_monthly as vm')
                ->where('vm.kode_rekening_id', $kodeRekeningId)
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0)
                ->sum('vm.jumlah');
            
            // Get monthly breakdown
            $monthlyData = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.bulan',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.kode_rekening_id', $kodeRekeningId)
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0)
                ->groupBy('vm.bulan')
                ->orderBy('vm.bulan')
                ->get();
            
            $monthlyAchievement = [];
            $monthlyTarget = $target ? $target / 12 : 0;
            
            foreach ($monthlyData as $data) {
                $achievement = $monthlyTarget > 0 ? ($data->total / $monthlyTarget) * 100 : 0;
                $monthlyAchievement[$data->bulan] = [
                    'actual' => (float)$data->total,
                    'target' => $monthlyTarget,
                    'achievement_rate' => round($achievement, 2)
                ];
            }
            
            $overallAchievement = $target > 0 ? ($actualTotal / $target) * 100 : 0;
            
            return [
                'actual_total' => $actualTotal,
                'target_total' => $target,
                'achievement_rate' => round($overallAchievement, 2),
                'monthly_achievement' => $monthlyAchievement,
                'status' => $overallAchievement >= 100 ? 'Achieved' : 'Not Achieved'
            ];
        });
    }

    /**
     * Get detailed breakdown by sub-categories
     */
    public function getDetailedBreakdown($parentId, $tahun, $bulan = null)
    {
        $cacheKey = "breakdown_{$parentId}_{$tahun}_{$bulan}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($parentId, $tahun, $bulan) {
            $query = DB::table('v_penerimaan_monthly as vm')
                ->join('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
                ->select(
                    'kr.id',
                    'kr.kode',
                    'kr.nama',
                    'kr.level',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('kr.parent_id', $parentId)
                ->where('vm.tahun', $tahun)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            if ($bulan !== null) {
                $query->where('vm.bulan', $bulan);
            }
            
            $results = $query->groupBy('kr.id', 'kr.kode', 'kr.nama', 'kr.level')
                             ->orderBy('total', 'desc')
                             ->get();
            
            $totalSum = $results->sum('total');
            
            // Calculate percentage contribution
            $breakdown = [];
            foreach ($results as $item) {
                $percentage = $totalSum > 0 ? ($item->total / $totalSum) * 100 : 0;
                $breakdown[] = [
                    'id' => $item->id,
                    'kode' => $item->kode,
                    'nama' => $item->nama,
                    'level' => $item->level,
                    'total' => (float)$item->total,
                    'percentage' => round($percentage, 2)
                ];
            }
            
            return [
                'items' => $breakdown,
                'total' => $totalSum,
                'count' => count($breakdown)
            ];
        });
    }

    /**
     * Export data to array for Excel/CSV
     */
    public function exportData($level, $parentKode, $tahun, $format = 'array')
    {
        $query = DB::table('v_penerimaan_monthly as vm')
            ->join('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
            ->select(
                'kr.kode',
                'kr.nama',
                'vm.bulan',
                'vm.tahun',
                'vm.jumlah'
            )
            ->where('vm.tahun', $tahun)
            ->whereNotNull('vm.jumlah')
            ->where('vm.jumlah', '>', 0);
        
        if ($parentKode !== null) {
            if (is_numeric($parentKode)) {
                $query->where('kr.parent_id', $parentKode)
                      ->where('kr.level', $level);
            } else {
                $query->where('kr.kode', 'LIKE', $parentKode . '%')
                      ->where('kr.level', $level);
            }
        } else {
            $query->where('kr.level', $level);
        }
        
        $results = $query->orderBy('kr.kode')
                         ->orderBy('vm.bulan')
                         ->get();
        
        if ($format === 'array') {
            return $results->toArray();
        }
        
        // Format for CSV
        $csvData = [];
        $csvData[] = ['Kode', 'Nama', 'Bulan', 'Tahun', 'Jumlah'];
        
        foreach ($results as $row) {
            $csvData[] = [
                $row->kode,
                $row->nama,
                $this->getMonthName($row->bulan),
                $row->tahun,
                $row->jumlah
            ];
        }
        
        return $csvData;
    }

    /**
     * Clear cache for specific key pattern
     */
    public function clearCache($pattern = null)
    {
        if ($pattern) {
            // Clear specific cache pattern
            Cache::forget($pattern);
        } else {
            // Clear all trend analysis cache
            Cache::flush();
        }
        
        return true;
    }

    /**
     * Validate data integrity
     */
    public function validateDataIntegrity($tahun)
    {
        $issues = [];
        
        // Check for negative values
        $negativeValues = DB::table('v_penerimaan_monthly')
            ->where('tahun', $tahun)
            ->where('jumlah', '<', 0)
            ->count();
        
        if ($negativeValues > 0) {
            $issues[] = "Found {$negativeValues} negative values";
        }
        
        // Check for orphaned records
        $orphanedRecords = DB::table('v_penerimaan_monthly as vm')
            ->leftJoin('kode_rekening as kr', 'vm.kode_rekening_id', '=', 'kr.id')
            ->where('vm.tahun', $tahun)
            ->whereNull('kr.id')
            ->count();
        
        if ($orphanedRecords > 0) {
            $issues[] = "Found {$orphanedRecords} orphaned records";
        }
        
        // Check for missing months
        $monthCounts = DB::table('v_penerimaan_monthly')
            ->select('bulan', DB::raw('COUNT(*) as count'))
            ->where('tahun', $tahun)
            ->groupBy('bulan')
            ->pluck('count', 'bulan')
            ->toArray();
        
        for ($month = 1; $month <= 12; $month++) {
            if (!isset($monthCounts[$month]) || $monthCounts[$month] == 0) {
                $issues[] = "No data for month {$month}";
            }
        }
        
        return [
            'valid' => empty($issues),
            'issues' => $issues,
            'year' => $tahun
        ];
    }

    /**
     * Get data quality metrics
     */
    public function getDataQualityMetrics($tahun)
    {
        $totalRecords = DB::table('v_penerimaan_monthly')
            ->where('tahun', $tahun)
            ->count();
        
        $nullRecords = DB::table('v_penerimaan_monthly')
            ->where('tahun', $tahun)
            ->whereNull('jumlah')
            ->count();
        
        $zeroRecords = DB::table('v_penerimaan_monthly')
            ->where('tahun', $tahun)
            ->where('jumlah', 0)
            ->count();
        
        $completeness = $totalRecords > 0 ? (($totalRecords - $nullRecords) / $totalRecords) * 100 : 0;
        $nonZeroRate = $totalRecords > 0 ? (($totalRecords - $zeroRecords) / $totalRecords) * 100 : 0;
        
        return [
            'total_records' => $totalRecords,
            'null_records' => $nullRecords,
            'zero_records' => $zeroRecords,
            'completeness_rate' => round($completeness, 2),
            'non_zero_rate' => round($nonZeroRate, 2)
        ];
    }

    /**
     * Get insights and recommendations
     */
    public function getInsights($kodeRekeningId, $tahun)
    {
        $insights = [];
        
        // Get current year data
        $currentYearData = $this->getSummaryStatistics($tahun, null, $kodeRekeningId);
        
        // Get previous year data for comparison
        $previousYearData = $this->getSummaryStatistics($tahun - 1, null, $kodeRekeningId);
        
        // Growth insight
        if ($currentYearData['growth_from_previous_year'] > 10) {
            $insights[] = [
                'type' => 'positive',
                'message' => "Strong growth of {$currentYearData['growth_from_previous_year']}% compared to previous year"
            ];
        } elseif ($currentYearData['growth_from_previous_year'] < -10) {
            $insights[] = [
                'type' => 'warning',
                'message' => "Significant decline of {$currentYearData['growth_from_previous_year']}% compared to previous year"
            ];
        }
        
        // Seasonal pattern insight
        $seasonalData = $this->getSeasonalAnalysis($kodeRekeningId, 3);
        $highestMonth = array_keys($seasonalData['monthly_averages'], max($seasonalData['monthly_averages']))[0];
        $lowestMonth = array_keys($seasonalData['monthly_averages'], min($seasonalData['monthly_averages']))[0];
        
        $insights[] = [
            'type' => 'info',
            'message' => "Peak performance typically in " . $this->getMonthName($highestMonth) . 
                         ", lowest in " . $this->getMonthName($lowestMonth)
        ];
        
        // Consistency insight
        $monthlyTotals = $currentYearData['monthly_totals'];
        if (!empty($monthlyTotals)) {
            $stdDev = $this->calculateStandardDeviation(array_values($monthlyTotals));
            $mean = array_sum($monthlyTotals) / count($monthlyTotals);
            $cv = $mean > 0 ? ($stdDev / $mean) * 100 : 0;
            
            if ($cv < 20) {
                $insights[] = [
                    'type' => 'positive',
                    'message' => 'Revenue shows consistent monthly performance'
                ];
            } elseif ($cv > 50) {
                $insights[] = [
                    'type' => 'warning',
                    'message' => 'High volatility in monthly revenue'
                ];
            }
        }
        
        return $insights;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStandardDeviation($values)
    {
        $count = count($values);
        if ($count <= 1) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $variance = 0;
        
        foreach ($values as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $variance /= ($count - 1);
        
        return sqrt($variance);
    }

    /**
     * Helper method to truncate long names
     */
    private function truncateName($name, $maxLength = 30)
    {
        if (strlen($name) > $maxLength) {
            return substr($name, 0, $maxLength) . '...';
        }
        return $name;
    }

    /**
     * Helper method to get month name in Indonesian
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
     * ✨ NEW: Get monthly detail summary for drill-down cards
     * 
     * @param string|int $categoryId - ID kategori
     * @param int $year - Tahun yang dipilih
     * @return array|null
     */
    public function getMonthlySummary($categoryId, $year)
    {
        $cacheKey = "monthly_summary_{$categoryId}_{$year}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($categoryId, $year) {
            
            // Get category info
            $category = KodeRekening::find($categoryId);
            if (!$category) {
                Log::warning("Category not found for getMonthlySummary", ['categoryId' => $categoryId]);
                return null;
            }
            
            Log::info("Getting monthly summary", [
                'categoryId' => $categoryId,
                'year' => $year,
                'level' => $category->level
            ]);
            
            // Query monthly data
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.bulan',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.tahun', $year)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            // Apply filtering based on level
            if (in_array($category->level, [3, 4, 5])) {
                // Aggregate Level 6 descendants
                $descendantIds = $this->getDescendantLevel6Ids($categoryId, $category->level);
                
                Log::info("Aggregating descendants", [
                    'level' => $category->level,
                    'descendant_count' => count($descendantIds)
                ]);
                
                if (!empty($descendantIds)) {
                    $query->whereIn('vm.kode_rekening_id', $descendantIds);
                } else {
                    Log::warning("No descendants found", ['categoryId' => $categoryId]);
                    return null;
                }
            } else if ($category->level == 6) {
                // Direct Level 6
                $query->where('vm.kode_rekening_id', $categoryId);
            } else {
                // Level 1, 2 - use parent_id
                $query->where('vm.parent_id', $categoryId);
            }
            
            $monthlyData = $query->groupBy('vm.bulan')
                                 ->orderBy('vm.bulan')
                                 ->get();
            
            if ($monthlyData->isEmpty()) {
                Log::warning("No monthly data found", [
                    'categoryId' => $categoryId,
                    'year' => $year
                ]);
                return null;
            }
            
            // Calculate statistics
            $monthlyTotals = [];
            $total = 0;
            
            foreach ($monthlyData as $row) {
                $monthlyTotals[$row->bulan] = (float)$row->total;
                $total += (float)$row->total;
            }
            
            $monthCount = count($monthlyTotals);
            $avgMonthly = $monthCount > 0 ? $total / $monthCount : 0;
            
            // Find peak and lowest month
            if (!empty($monthlyTotals)) {
                $maxValue = max($monthlyTotals);
                $minValue = min($monthlyTotals);
                $peakMonth = array_search($maxValue, $monthlyTotals);
                $lowestMonth = array_search($minValue, $monthlyTotals);
            } else {
                $peakMonth = null;
                $lowestMonth = null;
            }
            
            // Calculate monthly growth (first month to last month)
            $monthNumbers = array_keys($monthlyTotals);
            $firstMonth = min($monthNumbers);
            $lastMonth = max($monthNumbers);
            
            $firstMonthValue = $monthlyTotals[$firstMonth] ?? 0;
            $lastMonthValue = $monthlyTotals[$lastMonth] ?? 0;
            $monthlyGrowth = 0;
            
            if ($firstMonthValue > 0 && $lastMonthValue > 0 && count($monthlyTotals) > 1) {
                $monthlyGrowth = (($lastMonthValue - $firstMonthValue) / $firstMonthValue) * 100;
            }
            
            // Determine trend direction
            $trendDirection = 'stable';
            if ($monthlyGrowth > 5) {
                $trendDirection = 'increasing';
            } else if ($monthlyGrowth < -5) {
                $trendDirection = 'decreasing';
            }
            
            $result = [
                'year' => $year,
                'categoryInfo' => [
                    'id' => $category->id,
                    'kode' => $category->kode,
                    'nama' => $category->nama,
                    'level' => $category->level
                ],
                'total' => $total,
                'avgMonthly' => $avgMonthly,
                'peakMonth' => [
                    'month' => $peakMonth,
                    'name' => $peakMonth ? $this->getMonthName($peakMonth) : '-',
                    'value' => $peakMonth ? ($monthlyTotals[$peakMonth] ?? 0) : 0
                ],
                'lowestMonth' => [
                    'month' => $lowestMonth,
                    'name' => $lowestMonth ? $this->getMonthName($lowestMonth) : '-',
                    'value' => $lowestMonth ? ($monthlyTotals[$lowestMonth] ?? 0) : 0
                ],
                'monthlyGrowth' => round($monthlyGrowth, 2),
                'trendDirection' => $trendDirection,
                'monthlyTotals' => $monthlyTotals,
                'monthCount' => $monthCount
            ];
            
            Log::info("Monthly summary calculated", [
                'total' => $total,
                'avgMonthly' => $avgMonthly,
                'monthCount' => $monthCount
            ]);
            
            return $result;
        });
    }

    /**
     * ✨ NEW: Get monthly growth table data for drill-down
     * 
     * @param string|int $categoryId
     * @param int $year
     * @return array
     */
    public function getMonthlyGrowthTable($categoryId, $year)
    {
        $cacheKey = "monthly_growth_table_{$categoryId}_{$year}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($categoryId, $year) {
            
            $category = KodeRekening::find($categoryId);
            if (!$category) {
                Log::warning("Category not found for getMonthlyGrowthTable", ['categoryId' => $categoryId]);
                return [];
            }
            
            // Query monthly data
            $query = DB::table('v_penerimaan_monthly as vm')
                ->select(
                    'vm.bulan',
                    DB::raw('SUM(vm.jumlah) as total')
                )
                ->where('vm.tahun', $year)
                ->whereNotNull('vm.jumlah')
                ->where('vm.jumlah', '>', 0);
            
            // Apply filtering based on level
            if (in_array($category->level, [3, 4, 5])) {
                $descendantIds = $this->getDescendantLevel6Ids($categoryId, $category->level);
                if (!empty($descendantIds)) {
                    $query->whereIn('vm.kode_rekening_id', $descendantIds);
                } else {
                    return [];
                }
            } else if ($category->level == 6) {
                $query->where('vm.kode_rekening_id', $categoryId);
            } else {
                $query->where('vm.parent_id', $categoryId);
            }
            
            $results = $query->groupBy('vm.bulan')
                             ->orderBy('vm.bulan')
                             ->get();
            
            if ($results->isEmpty()) {
                Log::warning("No monthly data for growth table", [
                    'categoryId' => $categoryId,
                    'year' => $year
                ]);
                return [];
            }
            
            // Build table data with month-over-month growth
            $tableData = [];
            $previousValue = null;
            
            foreach ($results as $row) {
                $value = (float)$row->total;
                $growth = 0;
                $trend = 'stable';
                $description = 'Bulan pertama';
                
                if ($previousValue !== null && $previousValue > 0) {
                    $growth = (($value - $previousValue) / $previousValue) * 100;
                    
                    if ($growth > 0) {
                        $trend = 'up';
                        if ($growth > 20) {
                            $description = 'Pertumbuhan sangat tinggi';
                        } else if ($growth > 10) {
                            $description = 'Pertumbuhan tinggi';
                        } else if ($growth > 5) {
                            $description = 'Pertumbuhan moderat';
                        } else {
                            $description = 'Pertumbuhan ringan';
                        }
                    } else if ($growth < 0) {
                        $trend = 'down';
                        if ($growth < -20) {
                            $description = 'Penurunan drastis';
                        } else if ($growth < -10) {
                            $description = 'Penurunan signifikan';
                        } else if ($growth < -5) {
                            $description = 'Penurunan moderat';
                        } else {
                            $description = 'Penurunan ringan';
                        }
                    } else {
                        $trend = 'stable';
                        $description = 'Tidak ada perubahan';
                    }
                }
                
                $tableData[] = [
                    'month' => $row->bulan,
                    'monthName' => $this->getMonthName($row->bulan),
                    'value' => $value,
                    'growth' => round($growth, 2),
                    'trend' => $trend,
                    'description' => $description
                ];
                
                $previousValue = $value;
            }
            
            Log::info("Monthly growth table generated", [
                'categoryId' => $categoryId,
                'year' => $year,
                'rows' => count($tableData)
            ]);
            
            return $tableData;
        });
    }
}