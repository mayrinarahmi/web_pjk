<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
use App\Models\Skpd;
use App\Services\TrendAnalysisService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PublicDashboardController extends Controller
{
    /**
     * Cache duration in seconds (1 hour)
     */
    protected $cacheTime = 3600;
    
    /**
     * Trend Analysis Service
     */
    protected $trendService;
    
    /**
     * Constructor - Inject TrendAnalysisService
     */
    public function __construct(TrendAnalysisService $trendService)
    {
        $this->trendService = $trendService;
    }

    /**
     * Display public dashboard
     */
    public function index(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            
            return view('public-dashboard.index', compact('tahun'));
            
        } catch (\Exception $e) {
            Log::error('Public Dashboard Error: ' . $e->getMessage());
            return view('public-dashboard.index', ['tahun' => date('Y')]);
        }
    }

    /**
     * Get summary data for public dashboard
     * Returns: 4 kategori breakdown (Total, PAD, Transfer, Lain-lain)
     */
    public function getSummary(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $cacheKey = "public_summary_v2_{$tahun}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun) {
                
                // Get tahun anggaran aktif atau berdasarkan tahun
                $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)
                    ->where('is_active', true)
                    ->first();
                
                if (!$tahunAnggaran) {
                    $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)->first();
                }
                
                if (!$tahunAnggaran) {
                    return $this->getEmptyCategoryBreakdown();
                }
                
                // Get Level 1 dan Level 2 kode rekening
                $rootKode = KodeRekening::where('kode', '4')->first(); // Level 1
                $padKode = KodeRekening::where('kode', '4.1')->first(); // PAD
                $transferKode = KodeRekening::where('kode', '4.2')->first(); // Transfer
                $lainLainKode = KodeRekening::where('kode', '4.3')->first(); // Lain-lain
                
                if (!$rootKode) {
                    return $this->getEmptyCategoryBreakdown();
                }
                
                // TOTAL PENDAPATAN DAERAH (Level 1: 4)
                $totalTarget = $rootKode->getTargetAnggaranForTahun($tahunAnggaran->id, null);
                $totalRealisasi = Penerimaan::where('tahun', $tahun)->sum('jumlah');
                $totalKurang = $totalTarget - $totalRealisasi;
                $totalPersentase = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
                
                // PAD (Level 2: 4.1)
                $padTarget = $padKode ? $padKode->getTargetAnggaranForTahun($tahunAnggaran->id, null) : 0;
                $padRealisasi = 0;
                if ($padKode) {
                    $padLevel6Ids = KodeRekening::where('kode', 'like', '4.1%')
                        ->where('level', 6)
                        ->pluck('id');
                    $padRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $padLevel6Ids)
                        ->sum('jumlah');
                }
                $padKurang = $padTarget - $padRealisasi;
                $padPersentase = $padTarget > 0 ? ($padRealisasi / $padTarget) * 100 : 0;
                
                // TRANSFER (Level 2: 4.2)
                $transferTarget = $transferKode ? $transferKode->getTargetAnggaranForTahun($tahunAnggaran->id, null) : 0;
                $transferRealisasi = 0;
                if ($transferKode) {
                    $transferLevel6Ids = KodeRekening::where('kode', 'like', '4.2%')
                        ->where('level', 6)
                        ->pluck('id');
                    $transferRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $transferLevel6Ids)
                        ->sum('jumlah');
                }
                $transferKurang = $transferTarget - $transferRealisasi;
                $transferPersentase = $transferTarget > 0 ? ($transferRealisasi / $transferTarget) * 100 : 0;
                
                // LAIN-LAIN (Level 2: 4.3)
                $lainLainTarget = $lainLainKode ? $lainLainKode->getTargetAnggaranForTahun($tahunAnggaran->id, null) : 0;
                $lainLainRealisasi = 0;
                if ($lainLainKode) {
                    $lainLainLevel6Ids = KodeRekening::where('kode', 'like', '4.3%')
                        ->where('level', 6)
                        ->pluck('id');
                    $lainLainRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $lainLainLevel6Ids)
                        ->sum('jumlah');
                }
                $lainLainKurang = $lainLainTarget - $lainLainRealisasi;
                $lainLainPersentase = $lainLainTarget > 0 ? ($lainLainRealisasi / $lainLainTarget) * 100 : 0;
                
                // Update terakhir
                $latestPenerimaan = Penerimaan::where('tahun', $tahun)
                    ->orderBy('tanggal', 'desc')
                    ->first();
                
                $updateTerakhir = $latestPenerimaan 
                    ? Carbon::parse($latestPenerimaan->tanggal)->locale('id')->isoFormat('D MMMM YYYY')
                    : '-';
                
                return [
                    'total' => [
                        'realisasi' => $totalRealisasi,
                        'target' => $totalTarget,
                        'kurang' => $totalKurang,
                        'persentase' => round($totalPersentase, 2)
                    ],
                    'pad' => [
                        'realisasi' => $padRealisasi,
                        'target' => $padTarget,
                        'kurang' => $padKurang,
                        'persentase' => round($padPersentase, 2)
                    ],
                    'transfer' => [
                        'realisasi' => $transferRealisasi,
                        'target' => $transferTarget,
                        'kurang' => $transferKurang,
                        'persentase' => round($transferPersentase, 2)
                    ],
                    'lain_lain' => [
                        'realisasi' => $lainLainRealisasi,
                        'target' => $lainLainTarget,
                        'kurang' => $lainLainKurang,
                        'persentase' => round($lainLainPersentase, 2)
                    ],
                    'update_terakhir' => $updateTerakhir,
                    'tahun' => $tahun
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Summary Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data summary',
                'data' => $this->getEmptyCategoryBreakdown()
            ], 500);
        }
    }

    /**
     * Get SKPD realisasi data (aggregate)
     * Returns: Tabel realisasi per SKPD dengan target, realisasi, dan persentase
     */
    public function getSkpdRealisasi(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $cacheKey = "public_skpd_realisasi_{$tahun}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun) {
                
                $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)
                    ->where('is_active', true)
                    ->first();
                
                if (!$tahunAnggaran) {
                    $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)->first();
                }
                
                if (!$tahunAnggaran) {
                    return [];
                }
                
                // Get all active SKPD with assignments
                $skpdList = Skpd::where('status', 'aktif')
                    ->whereNotNull('kode_rekening_access')
                    ->where('kode_rekening_access', '!=', '[]')
                    ->get();
                
                $dataPerSkpd = [];
                
                // Root kode rekening
                $rootKode = KodeRekening::where('kode', '4')->first();
                
                if (!$rootKode) {
                    return [];
                }
                
                foreach ($skpdList as $skpd) {
                    // Get target untuk SKPD ini
                    $target = $rootKode->getTargetAnggaranForTahun($tahunAnggaran->id, $skpd->id);
                    
                    // Skip jika tidak ada pagu
                    if ($target <= 0) {
                        continue;
                    }
                    
                    // Hitung realisasi
                    $realisasi = Penerimaan::where('skpd_id', $skpd->id)
                        ->where('tahun', $tahun)
                        ->sum('jumlah');
                    
                    // Hitung persentase
                    $persentase = ($target > 0) ? ($realisasi / $target) * 100 : 0;
                    
                    $dataPerSkpd[] = [
                        'kode' => $skpd->kode_opd,
                        'nama' => $skpd->nama_opd,
                        'target' => $target,
                        'realisasi' => $realisasi,
                        'persentase' => round($persentase, 2),
                        'kurang' => $target - $realisasi,
                        'status' => $this->getStatusLabel($persentase)
                    ];
                }
                
                // Sort by persentase descending
                usort($dataPerSkpd, function($a, $b) {
                    return $b['persentase'] <=> $a['persentase'];
                });
                
                return $dataPerSkpd;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
                'total' => count($data)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get SKPD Realisasi Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data SKPD',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get top categories (Level 2)
     * Returns: Top 5 sumber pendapatan
     */
    public function getTopCategories(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $limit = $request->input('limit', 5);
            $cacheKey = "public_top_categories_{$tahun}_{$limit}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun, $limit) {
                
                // Get Level 2 categories (4.1, 4.2, 4.3)
                $level2Categories = KodeRekening::where('level', 2)
                    ->where('kode', 'like', '4.%')
                    ->where('is_active', true)
                    ->get();
                
                $categories = [];
                
                foreach ($level2Categories as $category) {
                    // Get all Level 6 IDs under this category
                    $level6Ids = KodeRekening::where('kode', 'like', $category->kode . '%')
                        ->where('level', 6)
                        ->where('is_active', true)
                        ->pluck('id')
                        ->toArray();
                    
                    if (empty($level6Ids)) {
                        continue;
                    }
                    
                    // Get realisasi
                    $realisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $level6Ids)
                        ->sum('jumlah');
                    
                    if ($realisasi > 0) {
                        $categories[] = [
                            'kode' => $category->kode,
                            'nama' => $category->nama,
                            'realisasi' => $realisasi
                        ];
                    }
                }
                
                // Sort by realisasi descending
                usort($categories, function($a, $b) {
                    return $b['realisasi'] <=> $a['realisasi'];
                });
                
                // Get top N
                $topCategories = array_slice($categories, 0, $limit);
                
                // Calculate total for percentage
                $total = array_sum(array_column($categories, 'realisasi'));
                
                // Add percentage
                foreach ($topCategories as &$cat) {
                    $cat['persentase'] = $total > 0 ? ($cat['realisasi'] / $total) * 100 : 0;
                }
                
                return $topCategories;
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Top Categories Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat top categories',
                'data' => []
            ], 500);
        }
    }

    /**
     * Get monthly trend
     * Returns: Trend penerimaan per bulan untuk tahun tertentu
     */
    public function getMonthlyTrend(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $cacheKey = "public_monthly_trend_{$tahun}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun) {
                
                $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
                
                // Get Level 2 categories
                $padKode = KodeRekening::where('kode', '4.1')->first();
                $transferKode = KodeRekening::where('kode', '4.2')->first();
                $lainLainKode = KodeRekening::where('kode', '4.3')->first();
                
                $padData = [];
                $transferData = [];
                $lainLainData = [];
                
                for ($i = 1; $i <= 12; $i++) {
                    // PAD
                    if ($padKode) {
                        $padIds = KodeRekening::where('kode', 'like', $padKode->kode . '%')
                            ->where('level', 6)
                            ->pluck('id')
                            ->toArray();
                        
                        $padData[] = (int) Penerimaan::where('tahun', $tahun)
                            ->whereIn('kode_rekening_id', $padIds)
                            ->whereMonth('tanggal', $i)
                            ->sum('jumlah');
                    } else {
                        $padData[] = 0;
                    }
                    
                    // Transfer
                    if ($transferKode) {
                        $transferIds = KodeRekening::where('kode', 'like', $transferKode->kode . '%')
                            ->where('level', 6)
                            ->pluck('id')
                            ->toArray();
                        
                        $transferData[] = (int) Penerimaan::where('tahun', $tahun)
                            ->whereIn('kode_rekening_id', $transferIds)
                            ->whereMonth('tanggal', $i)
                            ->sum('jumlah');
                    } else {
                        $transferData[] = 0;
                    }
                    
                    // Lain-lain
                    if ($lainLainKode) {
                        $lainLainIds = KodeRekening::where('kode', 'like', $lainLainKode->kode . '%')
                            ->where('level', 6)
                            ->pluck('id')
                            ->toArray();
                        
                        $lainLainData[] = (int) Penerimaan::where('tahun', $tahun)
                            ->whereIn('kode_rekening_id', $lainLainIds)
                            ->whereMonth('tanggal', $i)
                            ->sum('jumlah');
                    } else {
                        $lainLainData[] = 0;
                    }
                }
                
                return [
                    'categories' => $months,
                    'series' => [
                        [
                            'name' => 'PENDAPATAN ASLI DAERAH',
                            'data' => $padData
                        ],
                        [
                            'name' => 'PENDAPATAN TRANSFER',
                            'data' => $transferData
                        ],
                        [
                            'name' => 'LAIN-LAIN PENDAPATAN',
                            'data' => $lainLainData
                        ]
                    ]
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Monthly Trend Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat monthly trend',
                'data' => [
                    'categories' => [],
                    'series' => []
                ]
            ], 500);
        }
    }

    /**
     * Get yearly comparison
     * Returns: Perbandingan 3 tahun terakhir
     */
    public function getYearlyComparison(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $years = $request->input('years', 3);
            $cacheKey = "public_yearly_comparison_{$tahun}_{$years}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun, $years) {
                
                $startYear = $tahun - $years + 1;
                $categories = [];
                $seriesData = [];
                
                for ($year = $startYear; $year <= $tahun; $year++) {
                    $categories[] = (string)$year;
                    
                    $total = Penerimaan::where('tahun', $year)->sum('jumlah');
                    $seriesData[] = (float)$total;
                }
                
                // Calculate growth
                $growthData = [];
                for ($i = 1; $i < count($seriesData); $i++) {
                    $prev = $seriesData[$i - 1];
                    $current = $seriesData[$i];
                    
                    $growth = 0;
                    if ($prev > 0) {
                        $growth = (($current - $prev) / $prev) * 100;
                    }
                    
                    $growthData[] = round($growth, 2);
                }
                
                return [
                    'categories' => $categories,
                    'series' => [
                        [
                            'name' => 'TOTAL PENERIMAAN',
                            'data' => $seriesData
                        ]
                    ],
                    'growth' => $growthData
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Yearly Comparison Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat yearly comparison',
                'data' => [
                    'categories' => [],
                    'series' => [],
                    'growth' => []
                ]
            ], 500);
        }
    }

    /**
     * Clear public cache
     */
    public function clearCache()
    {
        try {
            Cache::flush();
            
            return response()->json([
                'success' => true,
                'message' => 'Cache berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Clear Cache Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus cache'
            ], 500);
        }
    }

    /**
     * Get kode rekening tree for cascading dropdown
     * Returns children based on parent_id
     */
    public function getKodeRekeningTree(Request $request)
    {
        try {
            $parentId = $request->input('parent_id', null);
            $cacheKey = "public_kode_tree_{$parentId}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($parentId) {
                $query = KodeRekening::where('is_active', true)
                    ->orderBy('kode');
                
                if ($parentId) {
                    $query->where('parent_id', $parentId);
                } else {
                    // Get Level 2 by default (4.1, 4.2, 4.3)
                    $query->where('level', 2)
                          ->where('kode', 'like', '4.%');
                }
                
                return $query->select('id', 'kode', 'nama', 'level', 'parent_id')
                             ->get();
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Kode Rekening Tree Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data kode rekening',
                'data' => []
            ], 500);
        }
    }
    
    /**
     * Get category trend data (reuse TrendAnalysisService)
     * Support ANY LEVEL (2, 3, 4, 5, 6)
     */
    public function getCategoryTrend(Request $request, $id)
    {
        try {
            $years = $request->input('years', 3);
            $view = $request->input('view', 'yearly');
            $month = $request->input('month', null);
            
            $kodeRekening = KodeRekening::findOrFail($id);
            
            // Determine parameters based on level
            if ($kodeRekening->level < 6) {
                // Level 1-5: Show aggregated or children
                $level = $kodeRekening->level + 1;
                $parentKode = $kodeRekening->id;
                $specificId = null;
            } else {
                // Level 6: Show the item itself
                $level = 6;
                $parentKode = null;
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
                    $specificId,
                    $month
                );
            } else {
                $data = $this->trendService->getYearlyChartData(
                    $level, 
                    $parentKode, 
                    $years, 
                    10, 
                    $specificId
                );
            }
            
            // Add category info
            $data['categoryInfo'] = [
                'id' => $kodeRekening->id,
                'kode' => $kodeRekening->kode,
                'nama' => $kodeRekening->nama,
                'level' => $kodeRekening->level
            ];
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get Category Trend Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat data trend kategori'
            ], 500);
        }
    }
    
    /**
     * Search kode rekening for public
     */
    public function searchKodeRekening(Request $request)
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
            Log::error('Search Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian',
                'data' => []
            ], 500);
        }
    }

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get empty category breakdown structure
     */
    private function getEmptyCategoryBreakdown()
    {
        return [
            'total' => [
                'realisasi' => 0,
                'target' => 0,
                'kurang' => 0,
                'persentase' => 0
            ],
            'pad' => [
                'realisasi' => 0,
                'target' => 0,
                'kurang' => 0,
                'persentase' => 0
            ],
            'transfer' => [
                'realisasi' => 0,
                'target' => 0,
                'kurang' => 0,
                'persentase' => 0
            ],
            'lain_lain' => [
                'realisasi' => 0,
                'target' => 0,
                'kurang' => 0,
                'persentase' => 0
            ],
            'update_terakhir' => '-',
            'tahun' => date('Y')
        ];
    }

    /**
     * Get empty summary data (deprecated - use getEmptyCategoryBreakdown)
     */
    private function getEmptySummary()
    {
        return [
            'total_penerimaan' => 0,
            'total_target' => 0,
            'bulan_ini' => 0,
            'bulan_ini_growth' => 0,
            'persentase_capaian' => 0,
            'update_terakhir' => '-',
            'tahun' => date('Y')
        ];
    }

    /**
     * Get status label based on percentage
     */
    private function getStatusLabel($persentase)
    {
        if ($persentase >= 100) {
            return 'TERCAPAI';
        } elseif ($persentase >= 70) {
            return 'PROSES';
        } else {
            return 'RENDAH';
        }
    }
}
