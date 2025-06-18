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

    public function mount()
    {
        $this->currentYear = date('Y');
        $this->currentMonth = date('n');
        $this->currentQuarter = ceil($this->currentMonth / 3);
        
        // Load tahun anggaran
        $this->tahunAnggaranList = TahunAnggaran::where('tahun', $this->currentYear)
            ->where('is_active', true)
            ->get();
            
        // Set default ke APBD Perubahan jika ada, kalau tidak ke Murni
        $defaultTahunAnggaran = $this->tahunAnggaranList
            ->where('jenis_anggaran', 'perubahan')
            ->first();
            
        if (!$defaultTahunAnggaran) {
            $defaultTahunAnggaran = $this->tahunAnggaranList
                ->where('jenis_anggaran', 'murni')
                ->first();
        }
        
        $this->selectedTahunAnggaran = $defaultTahunAnggaran ? $defaultTahunAnggaran->id : null;
        
        $this->loadDashboardData();
    }

    public function updatedSelectedTahunAnggaran()
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData()
    {
        if (!$this->selectedTahunAnggaran) {
            return;
        }

        $tahunAnggaran = TahunAnggaran::find($this->selectedTahunAnggaran);
        if (!$tahunAnggaran) {
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
        $kodeRekeningIds[] = $kodeRekening->id; // Include parent
        
        // Get pagu anggaran
        $pagu = TargetAnggaran::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
            ->whereIn('kode_rekening_id', $kodeRekeningIds)
            ->sum('jumlah');
            
        // FIXED: Calculate cumulative target correctly
        // Get ALL periods up to current month
        $targetPeriodes = TargetPeriode::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
            ->where('bulan_akhir', '<=', $this->currentMonth)
            ->orderBy('bulan_awal')
            ->get();
            
        $totalPersentase = 0;
        if ($targetPeriodes->count() > 0) {
            // Sum all percentages for periods that have ended
            $totalPersentase = $targetPeriodes->sum('persentase');
            
            // Special case: if we're in the middle of a period, calculate proportionally
            $currentPeriod = TargetPeriode::where('tahun_anggaran_id', $this->selectedTahunAnggaran)
                ->where('bulan_awal', '<=', $this->currentMonth)
                ->where('bulan_akhir', '>=', $this->currentMonth)
                ->first();
                
            if ($currentPeriod && !$targetPeriodes->contains('id', $currentPeriod->id)) {
                // We're in the middle of this period, add it
                $totalPersentase += $currentPeriod->persentase;
            }
        } else {
            // Fallback to default if no target periode data
            $defaultPersentase = [
                1 => 15,   // Q1
                2 => 40,   // Q1 + Q2
                3 => 70,   // Q1 + Q2 + Q3
                4 => 100   // Full year
            ];
            
            $totalPersentase = $defaultPersentase[$this->currentQuarter] ?? 100;
        }
        
        // Calculate target based on cumulative percentage
        $target = ($pagu * $totalPersentase) / 100;
        
        // Get realisasi (YTD - Year to Date)
        $realisasi = Penerimaan::whereIn('kode_rekening_id', $kodeRekeningIds)
            ->whereYear('tanggal', $tahunAnggaran->tahun)
            ->whereMonth('tanggal', '<=', $this->currentMonth)
            ->sum('jumlah');
            
        // Calculate percentage
        $persentase = 0;
        if ($target > 0) {
            $persentase = ($realisasi / $target) * 100;
        } elseif ($target == 0 && $realisasi > 0) {
            // Special case: if no target but has realization
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
        
        // Data untuk 12 bulan
        $months = [];
        $padData = [];
        $transferData = [];
        $lainLainData = [];
        
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create($tahunAnggaran->tahun, $i, 1)->format('M');
            
            if ($i <= $this->currentMonth) {
                // PAD
                $padKode = KodeRekening::where('kode', '4.1')->first();
                if ($padKode) {
                    $padIds = $this->getAllChildIds($padKode->id);
                    $padIds[] = $padKode->id;
                    
                    $padData[] = (int) Penerimaan::whereIn('kode_rekening_id', $padIds)
                        ->whereYear('tanggal', $tahunAnggaran->tahun)
                        ->whereMonth('tanggal', $i)
                        ->sum('jumlah');
                } else {
                    $padData[] = 0;
                }
                
                // Transfer
                $transferKode = KodeRekening::where('kode', '4.2')->first();
                if ($transferKode) {
                    $transferIds = $this->getAllChildIds($transferKode->id);
                    $transferIds[] = $transferKode->id;
                    
                    $transferData[] = (int) Penerimaan::whereIn('kode_rekening_id', $transferIds)
                        ->whereYear('tanggal', $tahunAnggaran->tahun)
                        ->whereMonth('tanggal', $i)
                        ->sum('jumlah');
                } else {
                    $transferData[] = 0;
                }
                
                // Lain-lain
                $lainLainKode = KodeRekening::where('kode', '4.3')->first();
                if ($lainLainKode) {
                    $lainLainIds = $this->getAllChildIds($lainLainKode->id);
                    $lainLainIds[] = $lainLainKode->id;
                    
                    $lainLainData[] = (int) Penerimaan::whereIn('kode_rekening_id', $lainLainIds)
                        ->whereYear('tanggal', $tahunAnggaran->tahun)
                        ->whereMonth('tanggal', $i)
                        ->sum('jumlah');
                } else {
                    $lainLainData[] = 0;
                }
            } else {
                $padData[] = 0;
                $transferData[] = 0;
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