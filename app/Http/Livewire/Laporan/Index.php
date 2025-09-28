<?php

namespace App\Http\Livewire\Laporan;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetBulan;
use App\Models\TargetPeriode;
use Carbon\Carbon;
use App\Exports\LaporanPenerimaanExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'bootstrap';
    
    public $tahunAnggaranId;
    public $tanggalMulai;
    public $tanggalSelesai;
    public $tipeFilter = 'custom';
    public $tahunAnggaran = [];
    public $persentaseTarget = 40;
    public $viewMode = 'cumulative';
    
    // Pagination properties
    public $perPage = 50;
    public $perPageOptions = [25, 50, 100, 200, 500];
    
    // Cache untuk data keseluruhan (untuk export)
    private $allData = null;
    
    protected $queryString = [
        'perPage' => ['except' => 50],
        'tahunAnggaranId' => ['except' => ''],
        'viewMode' => ['except' => 'cumulative'],
        'tipeFilter' => ['except' => 'custom'],
    ];
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::getActive();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
        
        if ($this->tahunAnggaranId) {
            $bulanAkhir = Carbon::now()->month;
            $this->persentaseTarget = $this->getTargetPersentase($bulanAkhir);
        }
    }
    
    public function updatedPerPage()
    {
        $this->resetPage();
    }
    
    public function updatedTahunAnggaranId()
    {
        $this->resetPage();
    }
    
    public function updatedTanggalMulai()
    {
        $this->resetPage();
    }
    
    public function updatedTanggalSelesai()
    {
        $this->resetPage();
    }
    
    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->resetPage();
        
        if (in_array($this->tipeFilter, ['triwulan1', 'triwulan2', 'triwulan3', 'triwulan4'])) {
            $this->updateDateRangeBasedOnFilter();
        }
    }
    
    private function getTargetPersentase($bulanAkhir)
    {
        if ($this->viewMode === 'cumulative') {
            return TargetPeriode::getPersentaseKumulatif($this->tahunAnggaranId, $bulanAkhir);
        } else {
            return TargetPeriode::getPersentaseForBulan($this->tahunAnggaranId, $bulanAkhir);
        }
    }

    public function setCustomFilter($tipeFilter, $tanggalMulai, $tanggalSelesai)
    {
        $this->tipeFilter = $tipeFilter;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->resetPage();
    }
    
    public function setFilter($tipe)
    {
        $this->tipeFilter = $tipe;
        $this->updateDateRangeBasedOnFilter();
        $this->resetPage();
    }
    
    private function updateDateRangeBasedOnFilter()
    {
        $tahunSekarang = Carbon::now()->year;
        
        switch ($this->tipeFilter) {
            case 'mingguan':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
                
            case 'minggu_lalu':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
                
            case 'bulanan':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
                
            case 'triwulan1':
                if ($this->viewMode === 'specific') {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 3, 31)->format('Y-m-d');
                } else {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 3, 31)->format('Y-m-d');
                }
                break;
                
            case 'triwulan2':
                if ($this->viewMode === 'specific') {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 4, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 6, 30)->format('Y-m-d');
                } else {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 6, 30)->format('Y-m-d');
                }
                break;
                
            case 'triwulan3':
                if ($this->viewMode === 'specific') {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 7, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 9, 30)->format('Y-m-d');
                } else {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 9, 30)->format('Y-m-d');
                }
                break;
                
            case 'triwulan4':
                if ($this->viewMode === 'specific') {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 10, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                } else {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                }
                break;
                
            case 'tahunan':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                break;
        }
        
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        $this->persentaseTarget = $this->getTargetPersentase($bulanAkhir);
    }
    
    public function exportPdf()
    {
        $data = $this->getAllLaporanData(); // Gunakan method untuk semua data
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        if (empty($data)) {
            session()->flash('error', 'Tidak ada data untuk diekspor');
            return;
        }
        
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;
        
        $pdf = PDF::loadView('exports.laporan-pdf', [
            'data' => $data,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'tahunAnggaran' => $tahunAnggaran,
            'persentaseTarget' => $this->persentaseTarget,
            'bulanAkhir' => $bulanAkhir,
            'bulanAwal' => $bulanAwal,
            'viewMode' => $this->viewMode
        ]);
        
        $pdf->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-penerimaan-' . date('Y-m-d') . '.pdf');
    }
    
    public function exportExcel()
    {
        $data = $this->getAllLaporanData(); // Gunakan method untuk semua data
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        if (empty($data)) {
            session()->flash('error', 'Tidak ada data untuk diekspor');
            return;
        }
        
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;
        
        return Excel::download(new LaporanPenerimaanExport(
            $data,
            $this->tanggalMulai,
            $this->tanggalSelesai,
            $tahunAnggaran,
            $this->persentaseTarget,
            $this->viewMode,
            $bulanAwal
        ), 'laporan-penerimaan-' . date('Y-m-d') . '.xlsx');
    }
    
    // Method baru untuk mendapatkan semua data (untuk export)
    private function getAllLaporanData()
    {
        if ($this->allData === null) {
            $this->allData = $this->getLaporanData(false); // false = tanpa pagination
        }
        return $this->allData;
    }
    
    private function getLaporanData($paginate = true)
    {
        if (!$this->tahunAnggaranId) {
            return $paginate ? collect() : [];
        }
        
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        $tahun = $tahunAnggaran->tahun;
        
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;

        if (strpos($this->tipeFilter, 'minggu') !== false) {
            $persentaseTarget = TargetPeriode::getPersentaseForWeek($this->tahunAnggaranId, $this->tanggalSelesai);
        } else {
            $persentaseTarget = $this->getTargetPersentase($bulanAkhir);
        }

        $this->persentaseTarget = round($persentaseTarget, 2);
        
        $kodeRekening = KodeRekening::orderBy('kode')->get();
        $kodeByLevel = [];
        foreach ($kodeRekening as $kode) {
            $kodeByLevel[$kode->level][] = $kode;
        }
        
        $dataPerKode = [];
        
        foreach ($kodeRekening as $kode) {
            $penerimaanPerBulan = [];
            for ($i = 1; $i <= 12; $i++) {
                $penerimaanPerBulan[$i] = 0;
            }
            
            $dataPerKode[$kode->id] = [
                'id' => $kode->id,
                'kode' => $kode->kode,
                'uraian' => $kode->nama,
                'level' => $kode->level,
                'parent_id' => $kode->parent_id,
                'target_anggaran' => 0,
                'penerimaan_per_bulan' => $penerimaanPerBulan,
                'realisasi_sd_bulan_ini' => 0
            ];
        }
        
        // Process level 6 data
        if (isset($kodeByLevel[6])) {
            foreach ($kodeByLevel[6] as $kode) {
                $targetData = TargetAnggaran::where('kode_rekening_id', $kode->id)
                    ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                    ->first();
                
                if ($targetData) {
                    $dataPerKode[$kode->id]['target_anggaran'] = $targetData->jumlah;
                }
                
                $penerimaanPerBulanQuery = Penerimaan::select(
                        DB::raw('MONTH(tanggal) as bulan'),
                        DB::raw('MAX(tanggal) as tanggal_terakhir')
                    )
                    ->where('kode_rekening_id', $kode->id)
                    ->where('tahun', $tahun)
                    ->whereDate('tanggal', '<=', $this->tanggalSelesai)
                    ->filterBySkpd()
                    ->groupBy('bulan');
                
                if ($this->viewMode === 'specific') {
                    $penerimaanPerBulanQuery->whereDate('tanggal', '>=', $this->tanggalMulai);
                }
                
                $tanggalTerakhirPerBulan = $penerimaanPerBulanQuery->get();
                
                foreach ($tanggalTerakhirPerBulan as $item) {
                    $penerimaan = Penerimaan::where('kode_rekening_id', $kode->id)
                        ->where('tahun', $tahun)
                        ->whereDate('tanggal', $item->tanggal_terakhir)
                        ->filterBySkpd()
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($penerimaan) {
                        $bulan = $item->bulan;
                        $dataPerKode[$kode->id]['penerimaan_per_bulan'][$bulan] = $penerimaan->jumlah;
                    }
                }
                
                if ($this->viewMode === 'cumulative') {
                    $penerimaanTerakhir = Penerimaan::where('kode_rekening_id', $kode->id)
                        ->where('tahun', $tahun)
                        ->whereDate('tanggal', '<=', $this->tanggalSelesai)
                        ->filterBySkpd()
                        ->orderBy('tanggal', 'desc')
                        ->orderBy('id', 'desc')
                        ->first();
                    
                    if ($penerimaanTerakhir) {
                        $dataPerKode[$kode->id]['realisasi_sd_bulan_ini'] = $penerimaanTerakhir->jumlah;
                    }
                } else {
                    $totalRealisasi = 0;
                    for ($i = $bulanAwal; $i <= $bulanAkhir; $i++) {
                        $totalRealisasi += $dataPerKode[$kode->id]['penerimaan_per_bulan'][$i];
                    }
                    $dataPerKode[$kode->id]['realisasi_sd_bulan_ini'] = $totalRealisasi;
                }
            }
        }
        
        // Aggregate from level 5 up
        for ($level = 5; $level >= 1; $level--) {
            if (isset($kodeByLevel[$level])) {
                foreach ($kodeByLevel[$level] as $kode) {
                    $children = $kodeRekening->where('parent_id', $kode->id);
                    
                    foreach ($children as $child) {
                        $childData = $dataPerKode[$child->id];
                        
                        $dataPerKode[$kode->id]['target_anggaran'] += $childData['target_anggaran'];
                        $dataPerKode[$kode->id]['realisasi_sd_bulan_ini'] += $childData['realisasi_sd_bulan_ini'];
                        
                        for ($i = 1; $i <= 12; $i++) {
                            $dataPerKode[$kode->id]['penerimaan_per_bulan'][$i] += $childData['penerimaan_per_bulan'][$i];
                        }
                    }
                }
            }
        }
        
        // Format data
        $dataLaporan = [];
        
        foreach ($kodeRekening as $kode) {
            $data = $dataPerKode[$kode->id];
            $targetAnggaran = $data['target_anggaran'];
            $realisasiSdBulanIni = $data['realisasi_sd_bulan_ini'];
            
            $targetSdBulanIni = $targetAnggaran * ($this->persentaseTarget / 100);
            
            if ($targetAnggaran <= 0) {
                $lebihKurang = 0;
            } else {
                $lebihKurang = $targetSdBulanIni - $realisasiSdBulanIni;
            }
            
            $persentase = 0;
            if ($targetAnggaran > 0) {
                $persentase = ($realisasiSdBulanIni / $targetAnggaran) * 100;
            }
            
            $dataLaporan[] = [
                'id' => $data['id'],
                'kode' => $data['kode'],
                'uraian' => $data['uraian'],
                'level' => $data['level'],
                'parent_id' => $data['parent_id'],
                'persentase' => round($persentase, 2),
                'target_anggaran' => $targetAnggaran,
                'target_sd_bulan_ini' => $targetSdBulanIni,
                'kurang_dari_target' => $lebihKurang,
                'realisasi_sd_bulan_ini' => $realisasiSdBulanIni,
                'penerimaan_per_bulan' => $data['penerimaan_per_bulan']
            ];
        }
        
        // Filter empty rows
        $dataLaporan = array_filter($dataLaporan, function($item) {
            return !($item['target_anggaran'] == 0 && $item['realisasi_sd_bulan_ini'] == 0);
        });
        
        // Sort by kode
        usort($dataLaporan, function($a, $b) {
            return $a['kode'] <=> $b['kode'];
        });
        
        // Return paginated atau full data
        if ($paginate) {
            return collect($dataLaporan);
        }
        
        return $dataLaporan;
    }
    
    public function render()
    {
        $dataCollection = $this->getLaporanData(true);
        
        // Paginate the collection
        $currentPage = $this->getPage() ?? 1;
        $perPage = $this->perPage;
        
        $paginatedData = $dataCollection->forPage($currentPage, $perPage);
        
        $data = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedData,
            $dataCollection->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
        
        $data->withPath('');
        
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        
        return view('livewire.laporan.index', [
            'data' => $data,
            'bulanAwal' => $bulanAwal,
            'bulanAkhir' => $bulanAkhir,
            'perPageOptions' => $this->perPageOptions,
        ]);
    }
}