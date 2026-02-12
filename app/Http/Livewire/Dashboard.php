<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetPeriode;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\Skpd;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Dashboard extends Component
{
    public $selectedTahunAnggaran;
    public $tahunAnggaranList = [];
    public $currentYear;
    public $currentMonth;
    public $currentQuarter;
    
    // Data untuk cards - TANPA PAGU
    public $totalPendapatan = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'kurang' => 0,
        'trend' => 'neutral'
    ];
    
    public $pad = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'kurang' => 0,
        'trend' => 'neutral'
    ];
    
    public $transfer = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'kurang' => 0,
        'trend' => 'neutral'
    ];
    
    public $lainLain = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'kurang' => 0,
        'trend' => 'neutral'
    ];
    
    // Data untuk chart
    public $chartData = [
        'categories' => [],
        'series' => []
    ];


// TAMBAHAN: Data untuk chart breakdown PAD
public $chartDataPadBreakdown = [
    'categories' => [],
    'series' => []
];

    
    public $kategoris = [];
    
    // Info user dan SKPD
    public $userInfo = '';
    public $isSkpdFiltered = false;
    public $canSeeAllSkpd = false; // Flag untuk konsolidasi
    
    // Info tanggal data terakhir
    public $latestPenerimaanDate = null;
    public $latestPenerimaanDateFormatted = null;
    
    // TAMBAHAN: Data per SKPD
    public $dataPerSkpd = [];
    public $topSkpd = [];
    public $bottomSkpd = [];
    
    // Summary statistics
    public $totalTransaksi = 0;
    public $totalSkpd = 0;
    public $persentaseCapaian = 0;

    public function mount()
    {
        try {
            $this->currentYear = date('Y');
            $this->currentMonth = date('n');
            $this->currentQuarter = ceil($this->currentMonth / 3);
            
            // Set user info untuk display
            $user = auth()->user();
            if ($user->isSuperAdmin()) {
                $this->userInfo = 'Dashboard Konsolidasi - Semua SKPD';
                $this->isSkpdFiltered = false;
                $this->canSeeAllSkpd = true; // Super admin bisa lihat semua
            } elseif ($user->isKepalaBadan()) {
                $this->userInfo = 'Dashboard Kepala BPKPAD - Semua SKPD';
                $this->isSkpdFiltered = false;
                $this->canSeeAllSkpd = true; // Kepala badan juga bisa lihat semua
            } elseif ($user->skpd) {
                $this->userInfo = 'Dashboard ' . $user->skpd->nama_opd;
                $this->isSkpdFiltered = true;
                $this->canSeeAllSkpd = false; // User SKPD tidak bisa lihat konsolidasi
            }
            
            // Load semua tahun anggaran yang tersedia
            $this->tahunAnggaranList = TahunAnggaran::orderBy('tahun', 'desc')
                ->orderBy('jenis_anggaran', 'desc')
                ->get();
                
            // Cari tahun anggaran yang aktif untuk default selection
            $activeTahunAnggaran = TahunAnggaran::where('is_active', true)->first();
            
            if ($activeTahunAnggaran) {
                $this->selectedTahunAnggaran = $activeTahunAnggaran->id;
            } else {
                $defaultTahunAnggaran = $this->tahunAnggaranList
                    ->where('tahun', $this->currentYear)
                    ->where('jenis_anggaran', 'perubahan')
                    ->first();
                    
                if (!$defaultTahunAnggaran) {
                    $defaultTahunAnggaran = $this->tahunAnggaranList
                        ->where('tahun', $this->currentYear)
                        ->where('jenis_anggaran', 'murni')
                        ->first();
                }
                
                if (!$defaultTahunAnggaran) {
                    $defaultTahunAnggaran = $this->tahunAnggaranList->first();
                }
                
                $this->selectedTahunAnggaran = $defaultTahunAnggaran ? $defaultTahunAnggaran->id : null;
            }
            
            $this->loadDashboardData();
        } catch (\Exception $e) {
            Log::error('Error in Dashboard mount: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat dashboard');
        }
    }

    public function updatedSelectedTahunAnggaran($value)
    {
        $this->selectedTahunAnggaran = $value;
        $this->loadDashboardData();
        $this->dispatch('refreshChart', ['chartData' => $this->chartData]);

        $this->dispatch('refreshChartPadBreakdown', ['chartData' => $this->chartDataPadBreakdown]);
    }

    public function loadDashboardData()
    {
        try {
            if (!$this->selectedTahunAnggaran) {
                $this->resetDataToZero();
                return;
            }

            $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
            if (!$tahunAnggaran) {
                $this->resetDataToZero();
                return;
            }

            // Calculate untuk setiap kategori
            $this->totalPendapatan = $this->calculateRealisasi('4');
            $this->pad = $this->calculateRealisasi('4.1');
            $this->transfer = $this->calculateRealisasi('4.2');
            $this->lainLain = $this->calculateRealisasi('4.3');
            
            // Load data untuk chart
            $this->loadChartData();
            
             // TAMBAHAN: Load data untuk chart breakdown PAD
        $this->loadChartDataPadBreakdown();

            // Load data untuk tabel kategori
            $this->loadKategoriData();
            
            // Load tanggal data terakhir
            $this->loadLatestPenerimaanDate();
            
            // Load summary statistics
            $this->loadSummaryStatistics();
            
            // TAMBAHAN: Load data per SKPD (hanya untuk super admin/kepala badan)
            if ($this->canSeeAllSkpd) {
                $this->loadDataPerSkpd();
            }
            
        } catch (\Exception $e) {
            Log::error('Error loading dashboard data: ' . $e->getMessage());
            session()->flash('error', 'Terjadi kesalahan saat memuat data');
            $this->resetDataToZero();
        }
    }
    
    private function resetDataToZero()
    {
        $defaultData = [
            'realisasi' => 0,
            'target' => 0,
            'persentase' => 0,
            'kurang' => 0,
            'trend' => 'neutral'
        ];
        
        $this->totalPendapatan = $defaultData;
        $this->pad = $defaultData;
        $this->transfer = $defaultData;
        $this->lainLain = $defaultData;
        $this->chartData = ['categories' => [], 'series' => []];
        $this->chartDataPadBreakdown = ['categories' => [], 'series' => []];
        $this->kategoris = [];
        
        // Reset tanggal terakhir
        $this->latestPenerimaanDate = null;
        $this->latestPenerimaanDateFormatted = null;
        
        // Reset data per SKPD
        $this->dataPerSkpd = [];
        $this->topSkpd = [];
        $this->bottomSkpd = [];
        
        // Reset summary
        $this->totalTransaksi = 0;
        $this->totalSkpd = 0;
        $this->persentaseCapaian = 0;
    }

    private function loadLatestPenerimaanDate()
    {
        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        
        if (!$tahunAnggaran) {
            $this->latestPenerimaanDate = null;
            $this->latestPenerimaanDateFormatted = null;
            return;
        }
        
        $selectedYear = $tahunAnggaran->tahun;
        
        $query = Penerimaan::query()->where('tahun', $selectedYear);
        
        if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
            $query = $query->filterBySkpd();
        }
        
        $latestPenerimaan = $query->orderBy('tanggal', 'desc')->first();
        
        if ($latestPenerimaan && $latestPenerimaan->tanggal) {
            if ($latestPenerimaan->tanggal instanceof \Carbon\Carbon) {
                $this->latestPenerimaanDate = $latestPenerimaan->tanggal->format('Y-m-d');
            } else {
                $this->latestPenerimaanDate = Carbon::parse($latestPenerimaan->tanggal)->format('Y-m-d');
            }
            
            $this->latestPenerimaanDateFormatted = Carbon::parse($this->latestPenerimaanDate)
                ->locale('id')
                ->isoFormat('D MMMM YYYY');
        } else {
            $this->latestPenerimaanDate = null;
            $this->latestPenerimaanDateFormatted = null;
        }
    }
    
    private function loadSummaryStatistics()
    {
        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        if (!$tahunAnggaran) {
            return;
        }
        
        $selectedYear = $tahunAnggaran->tahun;
        
        $query = Penerimaan::where('tahun', $selectedYear);
        if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
            $query = $query->filterBySkpd();
        }
        $this->totalTransaksi = $query->count();
        
        $this->totalSkpd = Penerimaan::where('tahun', $selectedYear)
            ->distinct('skpd_id')
            ->count('skpd_id');
        
        if ($this->totalPendapatan['target'] > 0) {
            $this->persentaseCapaian = ($this->totalPendapatan['realisasi'] / $this->totalPendapatan['target']) * 100;
        } else {
            $this->persentaseCapaian = 0;
        }
    }

   private function calculateRealisasi($kodePrefix)
{
    $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
    
    if (!$tahunAnggaran) {
        return [
            'realisasi' => 0,
            'target' => 0,
            'persentase' => 0,
            'kurang' => 0,
            'trend' => 'neutral'
        ];
    }
    
    $selectedYear = $tahunAnggaran->tahun;
    
    $kodeRekening = KodeRekening::where('kode', $kodePrefix)
        ->where('is_active', true)
        ->first();
        
    if (!$kodeRekening) {
        $kodeRekening = KodeRekening::where('kode', $kodePrefix)->first();
        
        if (!$kodeRekening) {
            return [
                'realisasi' => 0,
                'target' => 0,
                'persentase' => 0,
                'kurang' => 0,
                'trend' => 'neutral'
            ];
        }
    }

    // ✅ Query langsung: ambil semua Level 6 yang kodenya dimulai dengan prefix ini
    $kodeRekeningIds = KodeRekening::where('kode', 'like', $kodeRekening->kode . '%')
        ->where('level', 6) // ← Hanya level 6 (leaf nodes)
        ->forTahun($selectedYear)
        ->pluck('id')
        ->toArray();
    
    // Jika tidak ada level 6, gunakan ID ini sendiri (untuk handling edge case)
    if (empty($kodeRekeningIds)) {
        $kodeRekeningIds = [$kodeRekening->id];
    }
    
    // TARGET = PAGU ANGGARAN (hanya dari leaf nodes level 6)
    $target = TargetAnggaran::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
        ->whereIn('kode_rekening_id', $kodeRekeningIds)
        ->sum('jumlah');
    
    // REALISASI (hanya dari leaf nodes level 6)
    $query = Penerimaan::whereIn('kode_rekening_id', $kodeRekeningIds)
        ->where('tahun', $selectedYear);
    
    if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
        $query = $query->filterBySkpd();
    }
    
    $realisasi = $query->sum('jumlah');
    
    // PERSENTASE
    $persentase = 0;
    if ($target > 0) {
        $persentase = ($realisasi / $target) * 100;
    }
    
    $kurang = $target - $realisasi;
    
    // Determine trend
    $trend = 'neutral';
    if ($persentase >= 100) {
        $trend = 'up';
    } elseif ($persentase >= 70) {
        $trend = 'neutral';
    } else {
        $trend = 'down';
    }
    
    return [
        'realisasi' => $realisasi,
        'target' => $target,
        'persentase' => $persentase,
        'kurang' => $kurang > 0 ? $kurang : 0,
        'trend' => $trend
    ];
}

    private function getAllChildIds($parentId, $tahun = null)
    {
        $ids = [];
        $query = KodeRekening::where('parent_id', $parentId);
        if ($tahun) {
            $query->forTahun($tahun);
        }
        $children = $query->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildIds($child->id, $tahun));
        }

        return $ids;
    }

    // LANJUTAN METHOD DARI BAGIAN 1
    
    private function loadDataPerSkpd()
{
    $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
    
    if (!$tahunAnggaran) {
        $this->dataPerSkpd = [];
        $this->topSkpd = [];
        $this->bottomSkpd = [];
        return;
    }
    
    $selectedYear = $tahunAnggaran->tahun;
    
    // Get all active SKPD yang punya assignment
    $skpdList = Skpd::where('status', 'aktif')
        ->whereNotNull('kode_rekening_access')
        ->where('kode_rekening_access', '!=', '[]')
        ->get();
    
    $dataPerSkpd = [];
    
    // ✅ AMBIL ROOT KODE REKENING (Level 1) untuk kalkulasi recursive
    $rootKodeRekening = KodeRekening::where('level', 1)
        ->where('kode', '4') // Root pendapatan
        ->first();
    
    if (!$rootKodeRekening) {
        // Fallback jika tidak ada root
        $this->dataPerSkpd = [];
        $this->topSkpd = [];
        $this->bottomSkpd = [];
        return;
    }
    
    foreach ($skpdList as $skpd) {
        // ✅ GUNAKAN METHOD RECURSIVE dari Model KodeRekening
        $target = $rootKodeRekening->getTargetAnggaranForTahun(
            $this->selectedTahunAnggaran, 
            $skpd->id
        );
        
        // Skip jika tidak ada pagu
        if ($target <= 0) {
            continue;
        }
        
        // Hitung REALISASI dari Penerimaan
        $realisasi = Penerimaan::where('skpd_id', $skpd->id)
            ->where('tahun', $selectedYear)
            ->sum('jumlah');
        
        // Hitung persentase
        $persentase = ($target > 0) ? ($realisasi / $target) * 100 : 0;
        
        $dataPerSkpd[] = [
            'id' => $skpd->id,
            'kode' => $skpd->kode_opd,
            'nama' => $skpd->nama_opd,
            'target' => $target,
            'realisasi' => $realisasi,
            'persentase' => $persentase,
            'kurang' => $target - $realisasi
        ];
    }
    
    // Sort by persentase descending
    usort($dataPerSkpd, function($a, $b) {
        return $b['persentase'] <=> $a['persentase'];
    });
    
    $this->dataPerSkpd = $dataPerSkpd;
    
    // Filter data dengan realisasi > 0 untuk top dan bottom
    $dataWithRealisasi = array_filter($dataPerSkpd, function($item) {
        return $item['realisasi'] > 0;
    });
    
    // Get Top 5 SKPD (dengan realisasi > 0)
    $this->topSkpd = array_slice($dataWithRealisasi, 0, 5);
    
    // Get Bottom 5 SKPD (dengan realisasi > 0)
    $this->bottomSkpd = array_slice(array_reverse($dataWithRealisasi), 0, 5);
}

    private function loadChartData()
    {
        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        
        if (!$tahunAnggaran) {
            $this->chartData = [
                'categories' => [],
                'series' => []
            ];
            return;
        }
        
        $selectedYear = $tahunAnggaran->tahun;
        
        // Data untuk 12 bulan
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $padData = [];
        $transferData = [];
        $lainLainData = [];
        
        for ($i = 1; $i <= 12; $i++) {
            // PAD
            $padKode = KodeRekening::where('kode', '4.1')->forTahun($selectedYear)->first();
            if ($padKode) {
                $padIds = $this->getAllChildIds($padKode->id, $selectedYear);
                $padIds[] = $padKode->id;
                
                $query = Penerimaan::whereIn('kode_rekening_id', $padIds)
                    ->where('tahun', $selectedYear)
                    ->whereMonth('tanggal', $i);
                    
                if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                    $query = $query->filterBySkpd();
                }
                
                $padData[] = (int) $query->sum('jumlah');
            } else {
                $padData[] = 0;
            }
            
            // Transfer
            $transferKode = KodeRekening::where('kode', '4.2')->forTahun($selectedYear)->first();
            if ($transferKode) {
                $transferIds = $this->getAllChildIds($transferKode->id, $selectedYear);
                $transferIds[] = $transferKode->id;
                
                $query = Penerimaan::whereIn('kode_rekening_id', $transferIds)
                    ->where('tahun', $selectedYear)
                    ->whereMonth('tanggal', $i);
                    
                if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                    $query = $query->filterBySkpd();
                }
                
                $transferData[] = (int) $query->sum('jumlah');
            } else {
                $transferData[] = 0;
            }
            
            // Lain-lain
            $lainLainKode = KodeRekening::where('kode', '4.3')->forTahun($selectedYear)->first();
            if ($lainLainKode) {
                $lainLainIds = $this->getAllChildIds($lainLainKode->id, $selectedYear);
                $lainLainIds[] = $lainLainKode->id;
                
                $query = Penerimaan::whereIn('kode_rekening_id', $lainLainIds)
                    ->where('tahun', $selectedYear)
                    ->whereMonth('tanggal', $i);
                    
                if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                    $query = $query->filterBySkpd();
                }
                
                $lainLainData[] = (int) $query->sum('jumlah');
            } else {
                $lainLainData[] = 0;
            }
        }
        
        $this->chartData = [
            'categories' => $months,
            'series' => [
                [
                    'name' => 'PENDAPATAN ASLI DAERAH (PAD)',
                    'data' => $padData
                ],
                [
                    'name' => 'PENDAPATAN TRANSFER',
                    'data' => $transferData
                ],
                [
                    'name' => 'LAIN-LAIN PENDAPATAN DAERAH YANG SAH',
                    'data' => $lainLainData
                ]
            ]
        ];
    }

    private function loadChartDataPadBreakdown()
{
    $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
    
    if (!$tahunAnggaran) {
        $this->chartDataPadBreakdown = [
            'categories' => [],
            'series' => []
        ];
        return;
    }
    
    $selectedYear = $tahunAnggaran->tahun;
    
    // Data untuk 12 bulan
    $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    
    // Array untuk menyimpan data setiap sub-kategori PAD
    $pajakDaerahData = [];
    $retribusiDaerahData = [];
    $hasilPengelolaanData = [];
    $lainPadData = [];
    
    for ($i = 1; $i <= 12; $i++) {
        // 4.1.01 - Pajak Daerah
        $pajakKode = KodeRekening::where('kode', '4.1.01')->forTahun($selectedYear)->first();
        if ($pajakKode) {
            $pajakIds = $this->getAllChildIds($pajakKode->id, $selectedYear);
            $pajakIds[] = $pajakKode->id;
            
            $query = Penerimaan::whereIn('kode_rekening_id', $pajakIds)
                ->where('tahun', $selectedYear)
                ->whereMonth('tanggal', $i);
                
            if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                $query = $query->filterBySkpd();
            }
            
            $pajakDaerahData[] = (int) $query->sum('jumlah');
        } else {
            $pajakDaerahData[] = 0;
        }
        
        // 4.1.02 - Retribusi Daerah
        $retribusiKode = KodeRekening::where('kode', '4.1.02')->forTahun($selectedYear)->first();
        if ($retribusiKode) {
            $retribusiIds = $this->getAllChildIds($retribusiKode->id, $selectedYear);
            $retribusiIds[] = $retribusiKode->id;
            
            $query = Penerimaan::whereIn('kode_rekening_id', $retribusiIds)
                ->where('tahun', $selectedYear)
                ->whereMonth('tanggal', $i);
                
            if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                $query = $query->filterBySkpd();
            }
            
            $retribusiDaerahData[] = (int) $query->sum('jumlah');
        } else {
            $retribusiDaerahData[] = 0;
        }
        
        // 4.1.03 - Hasil Pengelolaan Kekayaan Daerah yang Dipisahkan
        $hasilKode = KodeRekening::where('kode', '4.1.03')->forTahun($selectedYear)->first();
        if ($hasilKode) {
            $hasilIds = $this->getAllChildIds($hasilKode->id, $selectedYear);
            $hasilIds[] = $hasilKode->id;
            
            $query = Penerimaan::whereIn('kode_rekening_id', $hasilIds)
                ->where('tahun', $selectedYear)
                ->whereMonth('tanggal', $i);
                
            if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                $query = $query->filterBySkpd();
            }
            
            $hasilPengelolaanData[] = (int) $query->sum('jumlah');
        } else {
            $hasilPengelolaanData[] = 0;
        }
        
        // 4.1.04 - Lain-lain PAD yang Sah
        $lainKode = KodeRekening::where('kode', '4.1.04')->forTahun($selectedYear)->first();
        if ($lainKode) {
            $lainIds = $this->getAllChildIds($lainKode->id, $selectedYear);
            $lainIds[] = $lainKode->id;
            
            $query = Penerimaan::whereIn('kode_rekening_id', $lainIds)
                ->where('tahun', $selectedYear)
                ->whereMonth('tanggal', $i);
                
            if (method_exists($query->getModel(), 'scopeFilterBySkpd')) {
                $query = $query->filterBySkpd();
            }
            
            $lainPadData[] = (int) $query->sum('jumlah');
        } else {
            $lainPadData[] = 0;
        }
    }
    
    $this->chartDataPadBreakdown = [
        'categories' => $months,
        'series' => [
            [
                'name' => 'Pajak Daerah',
                'data' => $pajakDaerahData
            ],
            [
                'name' => 'Retribusi Daerah',
                'data' => $retribusiDaerahData
            ],
            [
                'name' => 'Hasil Pengelolaan Kekayaan Daerah',
                'data' => $hasilPengelolaanData
            ],
            [
                'name' => 'Lain-lain PAD yang Sah',
                'data' => $lainPadData
            ]
        ]
    ];
}

    private function loadKategoriData()
    {
        // Get tahun for filtering
        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        $selectedYear = $tahunAnggaran ? $tahunAnggaran->tahun : null;

        // Get level 2 kode rekening
        $query = KodeRekening::where('level', 2);
        if ($selectedYear) {
            $query->forTahun($selectedYear);
        }
        $level2 = $query->orderBy('kode')->get();
            
        $this->kategoris = [];
        
        foreach ($level2 as $kode) {
            $data = $this->calculateRealisasi($kode->kode);
            
            // Hanya tambahkan jika ada data (target > 0 atau realisasi > 0)
            if ($data['target'] > 0 || $data['realisasi'] > 0) {
                $this->kategoris[] = [
                    'nama' => strtoupper($kode->nama),
                    'target' => $data['target'],
                    'realisasi' => $data['realisasi'],
                    'kurang' => $data['kurang'],
                    'persentase' => $data['persentase']
                ];
            }
        }
    }
    
    public function refreshData()
    {
        $this->loadDashboardData();
        $this->dispatch('dataRefreshed');
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}