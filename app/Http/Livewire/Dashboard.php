<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetPeriode; // Ganti TargetBulan dengan TargetPeriode
use Carbon\Carbon;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    public $tahunAnggaranId;
    public $tahunAnggaran = [];
    
    // Total Pendapatan Daerah
    public $totalPendapatan = 0;
    public $targetPendapatan = 0;
    public $persentasePendapatan = 0;
    
    // Pendapatan Asli Daerah (PAD)
    public $totalPAD = 0;
    public $targetPAD = 0;
    public $persentasePAD = 0;
    
    // Pendapatan Transfer
    public $totalPendapatanTransfer = 0;
    public $targetPendapatanTransfer = 0;
    public $persentasePendapatanTransfer = 0;
    
    // Lain-lain Pendapatan Daerah yang Sah
    public $totalLainLain = 0;
    public $targetLainLain = 0;
    public $persentaseLainLain = 0;
    
    // Data untuk grafik
    public $dataBulanan = [];
    public $dataKategori = [];
    
    // Data lainnya
    public $targetSdBulanIni = 0;
    public $bulanSaatIni;
    public $persentaseTarget = 0;
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        $this->bulanSaatIni = Carbon::now()->month;
        
        $this->loadDashboardData();
    }
    
    public function updatedTahunAnggaranId()
    {
        $this->loadDashboardData();
    }
    
    public function loadDashboardData()
    {
        if (!$this->tahunAnggaranId) {
            return;
        }
        
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        $tahun = $tahunAnggaran->tahun;
        
        // Hitung persentase target sampai bulan ini
        // Ganti dengan fungsi dari TargetPeriode
        $periode = TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('bulan_awal', '<=', $this->bulanSaatIni)
            ->where('bulan_akhir', '>=', $this->bulanSaatIni)
            ->first();
            
        $this->persentaseTarget = $periode ? $periode->persentase : 0;
        
        // Hitung total target dan realisasi pendapatan
        $pendapatanDaerah = KodeRekening::where('kode', 'like', '4%')
            ->where('level', 1)
            ->first();
            
        if ($pendapatanDaerah) {
            $this->targetPendapatan = TargetAnggaran::getTargetAnggaran($pendapatanDaerah->id, $this->tahunAnggaranId);
            
            $level5Ids = $pendapatanDaerah->getAllLevel5Descendants();
            
            $this->totalPendapatan = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $this->persentasePendapatan = $this->targetPendapatan > 0 ? 
                round(($this->totalPendapatan / $this->targetPendapatan) * 100, 2) : 0;
                
            // Hitung target sampai bulan ini
            $this->targetSdBulanIni = $this->targetPendapatan * ($this->persentaseTarget / 100);
        }
        
        // Hitung total target dan realisasi PAD
        $padKode = KodeRekening::where('kode', 'like', '4.1%')
            ->where('level', 2)
            ->first();
            
        if ($padKode) {
            $this->targetPAD = TargetAnggaran::getTargetAnggaran($padKode->id, $this->tahunAnggaranId);
            
            $level5Ids = $padKode->getAllLevel5Descendants();
            
            $this->totalPAD = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $this->persentasePAD = $this->targetPAD > 0 ? 
                round(($this->totalPAD / $this->targetPAD) * 100, 2) : 0;
        }
        
        // Hitung total target dan realisasi Pendapatan Transfer
        $pendapatanTransfer = KodeRekening::where('kode', 'like', '4.2%')
            ->where('level', 2)
            ->first();
            
        if ($pendapatanTransfer) {
            $this->targetPendapatanTransfer = TargetAnggaran::getTargetAnggaran($pendapatanTransfer->id, $this->tahunAnggaranId);
            
            $level5Ids = $pendapatanTransfer->getAllLevel5Descendants();
            
            $this->totalPendapatanTransfer = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $this->persentasePendapatanTransfer = $this->targetPendapatanTransfer > 0 ? 
                round(($this->totalPendapatanTransfer / $this->targetPendapatanTransfer) * 100, 2) : 0;
        }
        
        // Hitung total target dan realisasi Lain-lain Pendapatan Daerah yang Sah
        $lainLain = KodeRekening::where('kode', 'like', '4.3%')
            ->where('level', 2)
            ->first();
            
        if ($lainLain) {
            $this->targetLainLain = TargetAnggaran::getTargetAnggaran($lainLain->id, $this->tahunAnggaranId);
            
            $level5Ids = $lainLain->getAllLevel5Descendants();
            
            $this->totalLainLain = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $this->persentaseLainLain = $this->targetLainLain > 0 ? 
                round(($this->totalLainLain / $this->targetLainLain) * 100, 2) : 0;
        }
        
        // Data untuk grafik bulanan
        $this->dataBulanan = [];
        $this->dataBulanan = [];
        for ($i = 1; $i <= 12; $i++) {
            // Daftar pendapatan PAD bulanan
            $padBulan = Penerimaan::whereIn('kode_rekening_id', $padKode ? $padKode->getAllLevel5Descendants() : [])
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $i)
                ->sum('jumlah');
                
            // Daftar pendapatan Transfer bulanan
            $transferBulan = Penerimaan::whereIn('kode_rekening_id', $pendapatanTransfer ? $pendapatanTransfer->getAllLevel5Descendants() : [])
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $i)
                ->sum('jumlah');
                
            // Daftar pendapatan Lain-lain bulanan
            $lainlainBulan = Penerimaan::whereIn('kode_rekening_id', $lainLain ? $lainLain->getAllLevel5Descendants() : [])
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $i)
                ->sum('jumlah');
                
            $this->dataBulanan[] = [
                'bulan' => Carbon::create()->month($i)->format('F'),
                'pad' => $padBulan,
                'transfer' => $transferBulan,
                'lainlain' => $lainlainBulan,
                'total' => $padBulan + $transferBulan + $lainlainBulan
            ];
        }
        
        // Ubah juga format dispatch event
        $this->dispatch('dashboardUpdated', [
            'dataBulanan' => $this->dataBulanan,
            'dataKategori' => $this->dataKategori
        ]);
        
        // // Tambahkan juga event browser untuk update chart
        // $this->dispatchBrowserEvent('updateCharts', [
        //     'dataBulanan' => $this->dataBulanan,
        //     'dataKategori' => $this->dataKategori
        // ]);
        
        // Data untuk grafik kategori
        $level2Kodes = KodeRekening::where('level', 2)->get();
        $this->dataKategori = [];
        
        foreach ($level2Kodes as $kode) {
            $level5Ids = $kode->getAllLevel5Descendants();
            
            $total = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $target = TargetAnggaran::getTargetAnggaran($kode->id, $this->tahunAnggaranId);
            $targetSdBulanIni = $target * ($this->persentaseTarget / 100);
                
            $this->dataKategori[] = [
                'nama' => $kode->nama,
                'total' => $total,
                'target' => $target,
                'targetSdBulanIni' => $targetSdBulanIni,
                'persentase' => $target > 0 ? round(($total / $target) * 100, 2) : 0,
                'persentaseTerhadapTarget' => $targetSdBulanIni > 0 ? round(($total / $targetSdBulanIni) * 100, 2) : 0
            ];
        }
        
        $this->dispatch('dashboardUpdated', [
            'dataBulanan' => $this->dataBulanan,
            'dataKategori' => $this->dataKategori
            
        ]
    );
        
    }
    
    public function render()
    {
        return view('livewire.dashboard')->layout('components.layouts.app');
    }
    
}