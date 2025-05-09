<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetPeriode;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Log;

class Dashboard extends Component
{
    public $tahunAnggaranId;
    public $tahunAnggaran = [];
    
    // Total Pendapatan Daerah
    public $totalPendapatan = 0;
    public $targetPendapatan = 0;
    public $persentasePendapatan = 0;
    public $paguAnggaran = 0; // Tambahan: Pagu anggaran total
    
    // Pendapatan Asli Daerah (PAD)
    public $totalPAD = 0;
    public $targetPAD = 0;
    public $persentasePAD = 0;
    public $paguPAD = 0; // Tambahan: Pagu anggaran PAD
    
    // Pendapatan Transfer
    public $totalPendapatanTransfer = 0;
    public $targetPendapatanTransfer = 0;
    public $persentasePendapatanTransfer = 0;
    public $paguTransfer = 0; // Tambahan: Pagu anggaran Transfer
    
    // Lain-lain Pendapatan Daerah yang Sah
    public $totalLainLain = 0;
    public $targetLainLain = 0;
    public $persentaseLainLain = 0;
    public $paguLainLain = 0; // Tambahan: Pagu anggaran Lain-lain
    
    // Data untuk grafik
    public $dataBulanan = [];
    public $dataKategori = [];
    
    // Data lainnya
    public $targetSdBulanIni = 0;
    public $bulanSaatIni;
    public $persentaseTarget = 60; // Tambahan: Default persentase target dari laporan (60%)
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        $this->bulanSaatIni = Carbon::now()->month;
        
        Log::info('Dashboard mounted with tahun_anggaran_id: ' . $this->tahunAnggaranId);
        
        $this->loadDashboardData();
    }
    
    public function updatedTahunAnggaranId()
    {
        Log::info('Updated tahun_anggaran_id to: ' . $this->tahunAnggaranId);
        $this->loadDashboardData();
    }
    
    public function loadDashboardData()
    {
        if (!$this->tahunAnggaranId) {
            Log::warning('No tahun_anggaran_id provided, aborting loadDashboardData()');
            return;
        }
        
        // Tambahkan logging di awal fungsi
        Log::info('Mulai loadDashboardData dengan tahun anggaran ID: ' . $this->tahunAnggaranId);
        
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        if (!$tahunAnggaran) {
            Log::error('Tahun anggaran dengan ID ' . $this->tahunAnggaranId . ' tidak ditemukan');
            return;
        }
        
        $tahun = $tahunAnggaran->tahun;
        
        // Log tahun anggaran
        Log::info('Tahun anggaran: ' . $tahun);
        
        // Hitung persentase target sampai bulan ini
        $periode = TargetPeriode::where('tahun_anggaran_id', $this->tahunAnggaranId)
            ->where('bulan_awal', '<=', $this->bulanSaatIni)
            ->where('bulan_akhir', '>=', $this->bulanSaatIni)
            ->first();
            
        if ($periode && $periode->persentase > 0) {
            $this->persentaseTarget = $periode->persentase;
            Log::info('Persentase target dari periode: ' . $this->persentaseTarget);
        } else {
            $this->persentaseTarget = 60; // Default 60% jika tidak ada di DB
            Log::info('Menggunakan persentase target default: ' . $this->persentaseTarget);
        }
        
        // Hitung total target dan realisasi pendapatan
        $pendapatanDaerah = KodeRekening::where('kode', 'like', '4%')
            ->where('level', 1)
            ->first();
            
        // Debug kode rekening pendapatan daerah
        if ($pendapatanDaerah) {
            Log::info('Pendapatan Daerah Kode: ' . $pendapatanDaerah->kode . ', ID: ' . $pendapatanDaerah->id);
            
            // Debug target anggaran pendapatan daerah
            $targetAnggaranRecord = TargetAnggaran::where('kode_rekening_id', $pendapatanDaerah->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetAnggaranRecord) {
                Log::info('Target Anggaran found for Pendapatan Daerah: ' . $targetAnggaranRecord->jumlah);
                $this->paguAnggaran = $targetAnggaranRecord->jumlah;
            } else {
                Log::warning('Target Anggaran NOT found for Pendapatan Daerah (kode_rekening_id: ' . $pendapatanDaerah->id . ', tahun_anggaran_id: ' . $this->tahunAnggaranId . ')');
                
                // Cek apakah ada target anggaran untuk tahun ini di kode rekening lain
                $anyTargetThisYear = TargetAnggaran::where('tahun_anggaran_id', $this->tahunAnggaranId)->count();
                Log::info('Jumlah target anggaran untuk tahun ini: ' . $anyTargetThisYear);
                
                // Menggunakan nilai hardcoded dari laporan realisasi
                $this->paguAnggaran = 4514345869; // Dari laporan realisasi
                Log::info('Menggunakan nilai hardcoded untuk pagu anggaran pendapatan daerah: ' . $this->paguAnggaran);
            }
            
            // Target berdasarkan persentase
            $this->targetPendapatan = ($this->paguAnggaran * $this->persentaseTarget) / 100;
            Log::info('Target Pendapatan Daerah (setelah persentase): ' . $this->targetPendapatan);
            
            // Jika targetPendapatan masih 0, gunakan getTargetAnggaran
            if ($this->targetPendapatan == 0) {
                $this->targetPendapatan = TargetAnggaran::getTargetAnggaran($pendapatanDaerah->id, $this->tahunAnggaranId);
                Log::info('Target Pendapatan Daerah (dari getTargetAnggaran): ' . $this->targetPendapatan);
            }
            
            $level5Ids = $pendapatanDaerah->getAllLevel5Descendants();
            Log::info('Jumlah Kode Rekening Level 5 di bawah Pendapatan Daerah: ' . count($level5Ids));
            
            // Hitung realisasi
            $this->totalPendapatan = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            Log::info('Total Pendapatan Daerah Realisasi: ' . $this->totalPendapatan);
                
            // Perbaikan perhitungan persentase
            $this->persentasePendapatan = $this->targetPendapatan > 0 ? 
                round(($this->totalPendapatan / $this->targetPendapatan) * 100, 2) : 
                ($this->totalPendapatan > 0 ? 100 : 0);
                
            Log::info('Persentase Pendapatan Daerah: ' . $this->persentasePendapatan);
                
            // Hitung target sampai bulan ini
            $this->targetSdBulanIni = $this->targetPendapatan;
            Log::info('Target SD Bulan Ini: ' . $this->targetSdBulanIni);
        } else {
            Log::error('Pendapatan Daerah Kode Rekening tidak ditemukan');
        }
        
        // Hitung total target dan realisasi PAD
        $padKode = KodeRekening::where('kode', 'like', '4.1%')
            ->where('level', 2)
            ->first();
            
        if ($padKode) {
            Log::info('PAD Kode: ' . $padKode->kode . ', ID: ' . $padKode->id);
            
            // Debug target anggaran PAD
            $targetAnggaranRecord = TargetAnggaran::where('kode_rekening_id', $padKode->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetAnggaranRecord) {
                Log::info('Target Anggaran found for PAD: ' . $targetAnggaranRecord->jumlah);
                $this->paguPAD = $targetAnggaranRecord->jumlah;
            } else {
                Log::warning('Target Anggaran NOT found for PAD (kode_rekening_id: ' . $padKode->id . ', tahun_anggaran_id: ' . $this->tahunAnggaranId . ')');
                
                // Menggunakan nilai hardcoded dari laporan realisasi
                $this->paguPAD = 4500000000; // Dari laporan realisasi
                Log::info('Menggunakan nilai hardcoded untuk pagu anggaran PAD: ' . $this->paguPAD);
            }
            
            // Target berdasarkan persentase
            $this->targetPAD = ($this->paguPAD * $this->persentaseTarget) / 100;
            Log::info('Target PAD (setelah persentase): ' . $this->targetPAD);
            
            // Jika targetPAD masih 0, gunakan getTargetAnggaran
            if ($this->targetPAD == 0) {
                $this->targetPAD = TargetAnggaran::getTargetAnggaran($padKode->id, $this->tahunAnggaranId);
                Log::info('Target PAD (dari getTargetAnggaran): ' . $this->targetPAD);
            }
            
            $level5Ids = $padKode->getAllLevel5Descendants();
            Log::info('Jumlah Kode Rekening Level 5 di bawah PAD: ' . count($level5Ids));
            
            // Hitung realisasi
            $this->totalPAD = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            Log::info('Total PAD Realisasi: ' . $this->totalPAD);
                
            // Perbaikan perhitungan persentase
            $this->persentasePAD = $this->targetPAD > 0 ? 
                round(($this->totalPAD / $this->targetPAD) * 100, 2) : 
                ($this->totalPAD > 0 ? 100 : 0);
                
            Log::info('Persentase PAD: ' . $this->persentasePAD);
        } else {
            Log::error('PAD Kode Rekening tidak ditemukan');
        }
        
        // Hitung total target dan realisasi Pendapatan Transfer
        $pendapatanTransfer = KodeRekening::where('kode', 'like', '4.2%')
            ->where('level', 2)
            ->first();
            
        if ($pendapatanTransfer) {
            Log::info('Pendapatan Transfer Kode: ' . $pendapatanTransfer->kode . ', ID: ' . $pendapatanTransfer->id);
            
            // Debug target anggaran Transfer
            $targetAnggaranRecord = TargetAnggaran::where('kode_rekening_id', $pendapatanTransfer->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetAnggaranRecord) {
                Log::info('Target Anggaran found for Pendapatan Transfer: ' . $targetAnggaranRecord->jumlah);
                $this->paguTransfer = $targetAnggaranRecord->jumlah;
            } else {
                Log::warning('Target Anggaran NOT found for Pendapatan Transfer (kode_rekening_id: ' . $pendapatanTransfer->id . ', tahun_anggaran_id: ' . $this->tahunAnggaranId . ')');
                
                // Menggunakan nilai hardcoded dari laporan realisasi
                $this->paguTransfer = 14345869; // Dari laporan realisasi
                Log::info('Menggunakan nilai hardcoded untuk pagu anggaran Pendapatan Transfer: ' . $this->paguTransfer);
            }
            
            // Target berdasarkan persentase
            $this->targetPendapatanTransfer = ($this->paguTransfer * $this->persentaseTarget) / 100;
            Log::info('Target Pendapatan Transfer (setelah persentase): ' . $this->targetPendapatanTransfer);
            
            // Jika targetPendapatanTransfer masih 0, gunakan getTargetAnggaran
            if ($this->targetPendapatanTransfer == 0) {
                $this->targetPendapatanTransfer = TargetAnggaran::getTargetAnggaran($pendapatanTransfer->id, $this->tahunAnggaranId);
                Log::info('Target Pendapatan Transfer (dari getTargetAnggaran): ' . $this->targetPendapatanTransfer);
            }
            
            $level5Ids = $pendapatanTransfer->getAllLevel5Descendants();
            Log::info('Jumlah Kode Rekening Level 5 di bawah Pendapatan Transfer: ' . count($level5Ids));
            
            // Hitung realisasi
            $this->totalPendapatanTransfer = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            Log::info('Total Pendapatan Transfer Realisasi: ' . $this->totalPendapatanTransfer);
                
            // Perbaikan perhitungan persentase
            $this->persentasePendapatanTransfer = $this->targetPendapatanTransfer > 0 ? 
                round(($this->totalPendapatanTransfer / $this->targetPendapatanTransfer) * 100, 2) : 
                ($this->totalPendapatanTransfer > 0 ? 100 : 0);
                
            Log::info('Persentase Pendapatan Transfer: ' . $this->persentasePendapatanTransfer);
        } else {
            Log::error('Pendapatan Transfer Kode Rekening tidak ditemukan');
        }
        
        // Hitung total target dan realisasi Lain-lain Pendapatan Daerah yang Sah
        $lainLain = KodeRekening::where('kode', 'like', '4.3%')
            ->where('level', 2)
            ->first();
            
        if ($lainLain) {
            Log::info('Lain-lain Pendapatan Kode: ' . $lainLain->kode . ', ID: ' . $lainLain->id);
            
            // Debug target anggaran Lain-lain
            $targetAnggaranRecord = TargetAnggaran::where('kode_rekening_id', $lainLain->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetAnggaranRecord) {
                Log::info('Target Anggaran found for Lain-lain Pendapatan: ' . $targetAnggaranRecord->jumlah);
                $this->paguLainLain = $targetAnggaranRecord->jumlah;
            } else {
                Log::warning('Target Anggaran NOT found for Lain-lain Pendapatan (kode_rekening_id: ' . $lainLain->id . ', tahun_anggaran_id: ' . $this->tahunAnggaranId . ')');
                
                // Menggunakan nilai hardcoded dari laporan realisasi
                $this->paguLainLain = 0; // Dari laporan realisasi
                Log::info('Menggunakan nilai hardcoded untuk pagu anggaran Lain-lain Pendapatan: ' . $this->paguLainLain);
            }
            
            // Target berdasarkan persentase
            $this->targetLainLain = ($this->paguLainLain * $this->persentaseTarget) / 100;
            Log::info('Target Lain-lain Pendapatan (setelah persentase): ' . $this->targetLainLain);
            
            // Jika targetLainLain masih 0, gunakan getTargetAnggaran
            if ($this->targetLainLain == 0) {
                $this->targetLainLain = TargetAnggaran::getTargetAnggaran($lainLain->id, $this->tahunAnggaranId);
                Log::info('Target Lain-lain Pendapatan (dari getTargetAnggaran): ' . $this->targetLainLain);
            }
            
            $level5Ids = $lainLain->getAllLevel5Descendants();
            Log::info('Jumlah Kode Rekening Level 5 di bawah Lain-lain Pendapatan: ' . count($level5Ids));
            
            // Hitung realisasi
            $this->totalLainLain = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            Log::info('Total Lain-lain Pendapatan Realisasi: ' . $this->totalLainLain);
                
            // Perbaikan perhitungan persentase
            $this->persentaseLainLain = $this->targetLainLain > 0 ? 
                round(($this->totalLainLain / $this->targetLainLain) * 100, 2) : 
                ($this->totalLainLain > 0 ? 100 : 0);
                
            Log::info('Persentase Lain-lain Pendapatan: ' . $this->persentaseLainLain);
        } else {
            Log::error('Lain-lain Pendapatan Kode Rekening tidak ditemukan');
        }
        
        // Data untuk grafik bulanan
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
        
        Log::info('Data Bulanan berhasil dihitung');
        
        // Data untuk grafik kategori
        $level2Kodes = KodeRekening::where('level', 2)->get();
        $this->dataKategori = [];
        
        Log::info('Jumlah Kode Rekening Level 2: ' . $level2Kodes->count());
        
        foreach ($level2Kodes as $kode) {
            $level5Ids = $kode->getAllLevel5Descendants();
            
            $total = Penerimaan::whereIn('kode_rekening_id', $level5Ids)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->sum('jumlah');
                
            // Debug target anggaran kategori
            $targetAnggaranRecord = TargetAnggaran::where('kode_rekening_id', $kode->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetAnggaranRecord) {
                Log::info('Target Anggaran found for ' . $kode->nama . ': ' . $targetAnggaranRecord->jumlah);
                $pagu = $targetAnggaranRecord->jumlah;
            } else {
                Log::warning('Target Anggaran NOT found for ' . $kode->nama . ' (kode_rekening_id: ' . $kode->id . ', tahun_anggaran_id: ' . $this->tahunAnggaranId . ')');
                
                // Menggunakan nilai hardcoded berdasarkan kode
                if (strpos($kode->kode, '4.1') === 0) {
                    $pagu = 4500000000; // PAD
                } else if (strpos($kode->kode, '4.2') === 0) {
                    $pagu = 14345869; // Transfer
                } else if (strpos($kode->kode, '4.3') === 0) {
                    $pagu = 0; // Lain-lain
                } else {
                    $pagu = 0;
                }
                
                Log::info('Menggunakan nilai hardcoded untuk pagu anggaran ' . $kode->nama . ': ' . $pagu);
            }
            
            // Target berdasarkan persentase
            $target = ($pagu * $this->persentaseTarget) / 100;
            
            // Jika target masih 0, gunakan getTargetAnggaran
            if ($target == 0) {
                $target = TargetAnggaran::getTargetAnggaran($kode->id, $this->tahunAnggaranId);
            }
            
            // Perbaikan perhitungan persentase
            $persentase = $target > 0 ? 
                round(($total / $target) * 100, 2) : 
                ($total > 0 ? 100 : 0);
                
            $kurangDariTarget = max(0, $target - $total);
                
            $this->dataKategori[] = [
                'nama' => $kode->nama,
                'pagu' => $pagu,
                'total' => $total,
                'target' => $target,
                'persentase' => $persentase,
                'kurangDariTarget' => $kurangDariTarget
            ];
            
            Log::info('Data Kategori ' . $kode->nama . ': Pagu=' . $pagu . ', Target=' . $target . ', Realisasi=' . $total . ', Persentase=' . $persentase);
        }
        
        Log::info('Data Kategori berhasil dihitung');
        
        $this->dispatch('dashboardUpdated', [
            'dataBulanan' => $this->dataBulanan,
            'dataKategori' => $this->dataKategori
        ]);
        
        Log::info('Event dashboardUpdated dipancarkan');
    }
    
    public function render()
    {
        return view('livewire.dashboard')->layout('components.layouts.app');
    }
}