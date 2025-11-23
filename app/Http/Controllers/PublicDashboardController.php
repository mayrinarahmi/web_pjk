<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
use App\Models\Skpd;
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
     * Returns: Total penerimaan, Bulan ini, Capaian, Update terakhir
     */
    public function getSummary(Request $request)
    {
        try {
            $tahun = $request->input('tahun', date('Y'));
            $cacheKey = "public_summary_{$tahun}";
            
            $data = Cache::remember($cacheKey, $this->cacheTime, function () use ($tahun) {
                
                // Get tahun anggaran aktif atau berdasarkan tahun
                $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)
                    ->where('is_active', true)
                    ->first();
                
                if (!$tahunAnggaran) {
                    $tahunAnggaran = TahunAnggaran::where('tahun', $tahun)->first();
                }
                
                // Root kode rekening (4 = Pendapatan)
                $rootKode = KodeRekening::where('kode', '4')->first();
                
                if (!$rootKode || !$tahunAnggaran) {
                    return $this->getEmptySummary();
                }
                
                // Total Target (Konsolidasi)
                $totalTarget = $rootKode->getTargetAnggaranForTahun($tahunAnggaran->id, null);
                
                // Total Realisasi (Tahun ini)
                $totalRealisasi = Penerimaan::where('tahun', $tahun)->sum('jumlah');
                
                // Realisasi Bulan Ini
                $bulanIni = date('n');
                $realisasiBulanIni = Penerimaan::where('tahun', $tahun)
                    ->whereMonth('tanggal', $bulanIni)
                    ->sum('jumlah');
                
                // Persentase Capaian
                $persentaseCapaian = $totalTarget > 0 ? ($totalRealisasi / $totalTarget) * 100 : 0;
                
                // Tanggal update terakhir
                $latestPenerimaan = Penerimaan::where('tahun', $tahun)
                    ->orderBy('tanggal', 'desc')
                    ->first();
                
                $updateTerakhir = $latestPenerimaan 
                    ? Carbon::parse($latestPenerimaan->tanggal)->locale('id')->isoFormat('D MMMM YYYY')
                    : '-';
                
                // Growth calculation (compare dengan bulan lalu)
                $bulanLalu = $bulanIni - 1;
                $tahunBulanLalu = $tahun;
                if ($bulanLalu < 1) {
                    $bulanLalu = 12;
                    $tahunBulanLalu = $tahun - 1;
                }
                
                $realisasiBulanLalu = Penerimaan::where('tahun', $tahunBulanLalu)
                    ->whereMonth('tanggal', $bulanLalu)
                    ->sum('jumlah');
                
                $growthBulanIni = 0;
                if ($realisasiBulanLalu > 0) {
                    $growthBulanIni = (($realisasiBulanIni - $realisasiBulanLalu) / $realisasiBulanLalu) * 100;
                }
                
                // Get breakdown by category (PAD, Transfer, Lain-lain)
                $padKode = KodeRekening::where('kode', '4.1')->first();
                $transferKode = KodeRekening::where('kode', '4.2')->first();
                $lainLainKode = KodeRekening::where('kode', '4.3')->first();
                
                // PAD
                $padTarget = 0;
                $padRealisasi = 0;
                if ($padKode) {
                    $padTarget = $padKode->getTargetAnggaranForTahun($tahunAnggaran->id, null);
                    $padIds = KodeRekening::where('kode', 'like', $padKode->kode . '%')
                        ->where('level', 6)
                        ->pluck('id')
                        ->toArray();
                    $padRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $padIds)
                        ->sum('jumlah');
                }
                $padPersentase = $padTarget > 0 ? ($padRealisasi / $padTarget) * 100 : 0;
                
                // Transfer
                $transferTarget = 0;
                $transferRealisasi = 0;
                if ($transferKode) {
                    $transferTarget = $transferKode->getTargetAnggaranForTahun($tahunAnggaran->id, null);
                    $transferIds = KodeRekening::where('kode', 'like', $transferKode->kode . '%')
                        ->where('level', 6)
                        ->pluck('id')
                        ->toArray();
                    $transferRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $transferIds)
                        ->sum('jumlah');
                }
                $transferPersentase = $transferTarget > 0 ? ($transferRealisasi / $transferTarget) * 100 : 0;
                
                // Lain-lain
                $lainTarget = 0;
                $lainRealisasi = 0;
                if ($lainLainKode) {
                    $lainTarget = $lainLainKode->getTargetAnggaranForTahun($tahunAnggaran->id, null);
                    $lainIds = KodeRekening::where('kode', 'like', $lainLainKode->kode . '%')
                        ->where('level', 6)
                        ->pluck('id')
                        ->toArray();
                    $lainRealisasi = Penerimaan::where('tahun', $tahun)
                        ->whereIn('kode_rekening_id', $lainIds)
                        ->sum('jumlah');
                }
                $lainPersentase = $lainTarget > 0 ? ($lainRealisasi / $lainTarget) * 100 : 0;
                
                // Count SKPD and transactions
                $skpdCount = Skpd::where('status', 'aktif')
                    ->whereNotNull('kode_rekening_access')
                    ->where('kode_rekening_access', '!=', '[]')
                    ->count();
                
                $totalTransaksi = Penerimaan::where('tahun', $tahun)->count();
                
                return [
                    'total_penerimaan' => $totalRealisasi,
                    'total_target' => $totalTarget,
                    'bulan_ini' => $realisasiBulanIni,
                    'bulan_ini_growth' => $growthBulanIni,
                    'persentase_capaian' => round($persentaseCapaian, 2),
                    'update_terakhir' => $updateTerakhir,
                    'tahun' => $tahun,
                    'skpd_count' => $skpdCount,
                    'total_transaksi' => $totalTransaksi,
                    'categories' => [
                        [
                            'id' => 'total',
                            'nama' => 'Total Pendapatan Daerah',
                            'realisasi' => $totalRealisasi,
                            'target' => $totalTarget,
                            'persentase' => round($persentaseCapaian, 2)
                        ],
                        [
                            'id' => 'pad',
                            'nama' => 'Pendapatan Asli Daerah',
                            'realisasi' => $padRealisasi,
                            'target' => $padTarget,
                            'persentase' => round($padPersentase, 2)
                        ],
                        [
                            'id' => 'transfer',
                            'nama' => 'Pendapatan Transfer',
                            'realisasi' => $transferRealisasi,
                            'target' => $transferTarget,
                            'persentase' => round($transferPersentase, 2)
                        ],
                        [
                            'id' => 'lain',
                            'nama' => 'Lain-lain Pendapatan',
                            'realisasi' => $lainRealisasi,
                            'target' => $lainTarget,
                            'persentase' => round($lainPersentase, 2)
                        ]
                    ]
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
                'data' => $this->getEmptySummary()
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

    // ==========================================
    // HELPER METHODS
    // ==========================================

    /**
     * Get empty summary data
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