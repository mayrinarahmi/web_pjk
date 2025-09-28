<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetPeriode;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Dashboard extends Component
{
    public $selectedTahunAnggaran;
    public $tahunAnggaranList = [];
    public $currentYear;
    public $currentMonth;
    public $currentQuarter;
    
    // Data untuk cards - dengan default values
    public $totalPendapatan = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'pagu' => 0,
        'kurang' => 0
    ];
    
    public $pad = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'pagu' => 0,
        'kurang' => 0
    ];
    
    public $transfer = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'pagu' => 0,
        'kurang' => 0
    ];
    
    public $lainLain = [
        'realisasi' => 0,
        'target' => 0,
        'persentase' => 0,
        'pagu' => 0,
        'kurang' => 0
    ];
    
    // Data untuk chart
    public $chartData = [
        'categories' => [],
        'series' => []
    ];
    
    public $kategoris = [];
    
    // TAMBAHAN: Info user dan SKPD
    public $userInfo = '';
    public $isSkpdFiltered = false;

    public function mount()
    {
        $this->currentYear = date('Y');
        $this->currentMonth = date('n');
        $this->currentQuarter = ceil($this->currentMonth / 3);
        
        // TAMBAHAN: Set user info untuk display
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            $this->userInfo = 'Dashboard Konsolidasi - Semua SKPD';
            $this->isSkpdFiltered = false;
        } elseif ($user->isKepalaBadan()) {
            $this->userInfo = 'Dashboard Kepala BPKPAD - Semua SKPD';
            $this->isSkpdFiltered = false;
        } elseif ($user->skpd) {
            $this->userInfo = 'Dashboard ' . $user->skpd->nama_opd;
            $this->isSkpdFiltered = true;
        }
        
        // PERBAIKAN: Load semua tahun anggaran yang tersedia
        $this->tahunAnggaranList = TahunAnggaran::orderBy('tahun', 'desc')
            ->orderBy('jenis_anggaran', 'desc')
            ->get();
            
        // PERBAIKAN: Cari tahun anggaran yang aktif untuk default selection
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
    }

    public function updatedSelectedTahunAnggaran($value)
    {
        // Force refresh dengan value baru
        $this->selectedTahunAnggaran = $value;
        $this->loadDashboardData();
        
        // PERBAIKAN: Dispatch event dengan data chart terbaru
        $this->dispatch('refreshChart', ['chartData' => $this->chartData]);
    }

    public function loadDashboardData()
    {
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
        
        // Load data untuk tabel kategori
        $this->loadKategoriData();
    }
    
    private function resetDataToZero()
    {
        $defaultData = [
            'realisasi' => 0,
            'target' => 0,
            'persentase' => 0,
            'pagu' => 0,
            'kurang' => 0
        ];
        
        $this->totalPendapatan = $defaultData;
        $this->pad = $defaultData;
        $this->transfer = $defaultData;
        $this->lainLain = $defaultData;
        $this->chartData = ['categories' => [], 'series' => []];
        $this->kategoris = [];
    }

    private function calculateRealisasi($kodePrefix)
    {
        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        
        if (!$tahunAnggaran) {
            return [
                'realisasi' => 0,
                'target' => 0,
                'persentase' => 0,
                'pagu' => 0,
                'kurang' => 0
            ];
        }
        
        $selectedYear = $tahunAnggaran->tahun;
        
        // Get kode rekening
        $kodeRekening = KodeRekening::where('kode', $kodePrefix)
            ->where('is_active', true)
            ->first();
            
        if (!$kodeRekening) {
            return [
                'realisasi' => 0,
                'target' => 0,
                'persentase' => 0,
                'pagu' => 0,
                'kurang' => 0
            ];
        }

        // Get all child kode rekening IDs (recursive)
        $kodeRekeningIds = $this->getAllChildIds($kodeRekening->id);
        $kodeRekeningIds[] = $kodeRekening->id;
        
        // Get pagu anggaran
        $pagu = TargetAnggaran::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->sum('jumlah');
            
        // Calculate target berdasarkan tahun yang dipilih
        $useCurrentMonth = ($selectedYear == $this->currentYear) ? $this->currentMonth : 12;
        $useCurrentQuarter = ceil($useCurrentMonth / 3);
        
        // Get ALL periods up to current/selected month
        $targetPeriodes = TargetPeriode::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
            ->where('bulan_akhir', '<=', $useCurrentMonth)
            ->orderBy('bulan_awal')
            ->get();
            
        $totalPersentase = 0;
        if ($targetPeriodes->count() > 0) {
            $totalPersentase = $targetPeriodes->sum('persentase');
            
            $currentPeriod = TargetPeriode::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
                ->where('bulan_awal', '<=', $useCurrentMonth)
                ->where('bulan_akhir', '>=', $useCurrentMonth)
                ->first();
                
            if ($currentPeriod && !$targetPeriodes->contains('id', $currentPeriod->id)) {
                $totalPersentase += $currentPeriod->persentase;
            }
        } else {
            $defaultPersentase = [
                1 => 15,
                2 => 40,
                3 => 70,
                4 => 100
            ];
            
            $totalPersentase = $defaultPersentase[$useCurrentQuarter] ?? 100;
        }
        
        // Calculate target based on cumulative percentage
        $target = ($pagu * $totalPersentase) / 100;
        
        // PERBAIKAN: Get realisasi dengan filter tahun yang benar
        $query = Penerimaan::whereIn('kode_rekening_id', $kodeRekeningIds)
            ->where('tahun', $selectedYear);  // Gunakan field tahun, bukan whereYear
            
        // Hanya filter by month jika tahun yang dipilih adalah tahun sekarang
        if ($selectedYear == $this->currentYear) {
            $query = $query->whereMonth('tanggal', '<=', $this->currentMonth);
        }
        
        // Apply SKPD filter
        $query = $query->filterBySkpd();
        
        $realisasi = $query->sum('jumlah');
            
        // Calculate percentage
        $persentase = 0;
        if ($target > 0) {
            $persentase = ($realisasi / $target) * 100;
        } elseif ($target == 0 && $realisasi > 0) {
            $persentase = 100;
        }
        
        $kurang = $target - $realisasi;
        
        return [
            'realisasi' => $realisasi,
            'target' => $target,
            'persentase' => $persentase,
            'pagu' => $pagu,
            'kurang' => $kurang > 0 ? $kurang : 0
        ];
    }

    private function getAllChildIds($parentId)
    {
        $ids = [];
        $children = KodeRekening::where('parent_id', $parentId)
            ->where('is_active', true)
            ->get();
            
        foreach ($children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getAllChildIds($child->id));
        }
        
        return $ids;
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
        
        // PERBAIKAN: Gunakan tahun dari tahun anggaran yang dipilih
        $selectedYear = $tahunAnggaran->tahun;
        
        // Data untuk 12 bulan
        $months = [];
        $padData = [];
        $transferData = [];
        $lainLainData = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create(null, $i, 1)->format('M');
            
            // PAD
            $padKode = KodeRekening::where('kode', '4.1')->first();
            if ($padKode) {
                $padIds = $this->getAllChildIds($padKode->id);
                $padIds[] = $padKode->id;
                
                // PERBAIKAN: Gunakan field tahun yang benar
                $padData[] = (int) Penerimaan::whereIn('kode_rekening_id', $padIds)
                    ->where('tahun', $selectedYear)  // Filter by tahun field
                    ->whereMonth('tanggal', $i)
                    ->filterBySkpd()
                    ->sum('jumlah');
            } else {
                $padData[] = 0;
            }
            
            // Transfer
            $transferKode = KodeRekening::where('kode', '4.2')->first();
            if ($transferKode) {
                $transferIds = $this->getAllChildIds($transferKode->id);
                $transferIds[] = $transferKode->id;
                
                // PERBAIKAN: Gunakan field tahun yang benar
                $transferData[] = (int) Penerimaan::whereIn('kode_rekening_id', $transferIds)
                    ->where('tahun', $selectedYear)  // Filter by tahun field
                    ->whereMonth('tanggal', $i)
                    ->filterBySkpd()
                    ->sum('jumlah');
            } else {
                $transferData[] = 0;
            }
            
            // Lain-lain
            $lainLainKode = KodeRekening::where('kode', '4.3')->first();
            if ($lainLainKode) {
                $lainLainIds = $this->getAllChildIds($lainLainKode->id);
                $lainLainIds[] = $lainLainKode->id;
                
                // PERBAIKAN: Gunakan field tahun yang benar
                $lainLainData[] = (int) Penerimaan::whereIn('kode_rekening_id', $lainLainIds)
                    ->where('tahun', $selectedYear)  // Filter by tahun field
                    ->whereMonth('tanggal', $i)
                    ->filterBySkpd()
                    ->sum('jumlah');
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

    private function loadKategoriData()
    {
        // Get level 2 kode rekening
        $level2 = KodeRekening::where('level', 2)
            ->where('is_active', true)
            ->orderBy('kode')
            ->get();
            
        $this->kategoris = [];
        
        foreach ($level2 as $kode) {
            $data = $this->calculateRealisasi($kode->kode);
            
            $this->kategoris[] = [
                'nama' => strtoupper($kode->nama),
                'pagu' => $data['pagu'],
                'target' => $data['target'],
                'realisasi' => $data['realisasi'],
                'kurang' => $data['kurang'],
                'persentase' => $data['persentase']
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}