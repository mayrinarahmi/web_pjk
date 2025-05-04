<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetBulan;
use Carbon\Carbon;

class Dashboard extends Component
{
    public $tahunAnggaranId;
    public $tahunAnggaran = [];
    public $totalPendapatan = 0;
    public $targetPendapatan = 0;
    public $persentasePendapatan = 0;
    public $totalPAD = 0;
    public $targetPAD = 0;
    public $persentasePAD = 0;
    public $dataBulanan = [];
    public $dataKategori = [];
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
        $this->persentaseTarget = TargetBulan::getPersentaseSampaiDenganBulan($this->tahunAnggaranId, $this->bulanSaatIni);
        
        // Hitung total target dan realisasi pendapatan
        $pendapatanDaerah = KodeRekening::where('kode', 'like', '4%')
            ->where('level', 1)
            ->first();
            
        if ($pendapatanDaerah) {
            $this->targetPendapatan = TargetAnggaran::getTargetAnggaran($pendapatanDaerah->id, $this->tahunAnggaranId);
            
            $level4Ids = $pendapatanDaerah->getAllLevel4Descendants();
            
            $this->totalPendapatan = Penerimaan::whereIn('kode_rekening_id', $level4Ids)
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
            
            $level4Ids = $padKode->getAllLevel4Descendants();
            
            $this->totalPAD = Penerimaan::whereIn('kode_rekening_id', $level4Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            $this->persentasePAD = $this->targetPAD > 0 ? 
                round(($this->totalPAD / $this->targetPAD) * 100, 2) : 0;
        }
        
        // Data untuk grafik bulanan
        $this->dataBulanan = [];
        for ($i = 1; $i <= 12; $i++) {
            $bulanTotal = Penerimaan::where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->whereMonth('tanggal', $i)
                ->sum('jumlah');
                
            $this->dataBulanan[] = [
                'bulan' => Carbon::create()->month($i)->format('F'),
                'total' => $bulanTotal
            ];
        }
        
        // Data untuk grafik kategori
        $level2Kodes = KodeRekening::where('level', 2)->get();
        $this->dataKategori = [];
        
        foreach ($level2Kodes as $kode) {
            $level4Ids = $kode->getAllLevel4Descendants();
            
            $total = Penerimaan::whereIn('kode_rekening_id', $level4Ids)
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
        
        $this->emit('dashboardUpdated');
    }
    
    public function render()
    {
        return view('livewire.dashboard');
    }
}

