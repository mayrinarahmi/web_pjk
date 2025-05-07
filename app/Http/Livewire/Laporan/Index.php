<?php

namespace App\Http\Livewire\Laporan;

use Livewire\Component;
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

class Index extends Component
{
    public $tahunAnggaranId;
    public $tanggalMulai;
    public $tanggalSelesai;
    public $tipeFilter = 'custom';
    public $tahunAnggaran = [];
    public $persentaseTarget = 40; // Nilai default yang akan diganti dengan nilai dinamis
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        // Default tanggal (bulan ini)
        $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
        
        // Set persentase target berdasarkan bulan saat ini
        if ($this->tahunAnggaranId) {
            $bulanAkhir = Carbon::now()->month;
            $this->persentaseTarget = TargetBulan::getPersentaseSampaiDenganBulan($this->tahunAnggaranId, $bulanAkhir);
        }
    }

    public function setCustomFilter($tipeFilter, $tanggalMulai, $tanggalSelesai)
{
    $this->tipeFilter = $tipeFilter;
    $this->tanggalMulai = $tanggalMulai;
    $this->tanggalSelesai = $tanggalSelesai;
}
    
    public function setFilter($tipe)
    {
        $this->tipeFilter = $tipe;
        $tahunSekarang = Carbon::now()->year;
        
        switch ($tipe) {
            case 'mingguan':
                // Filter untuk minggu ini
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;
                
            case 'minggu_lalu':
                // Filter untuk minggu lalu
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;
            }
        switch ($tipe) {
            case 'bulanan':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d'); // Selalu dari awal tahun
                $this->tanggalSelesai = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'triwulan1':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 3, 31)->format('Y-m-d');
                break;
            case 'triwulan2':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d'); // Selalu dari awal tahun
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 6, 30)->format('Y-m-d');
                break;
            case 'triwulan3':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d'); // Selalu dari awal tahun
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 9, 30)->format('Y-m-d');
                break;
            case 'triwulan4':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d'); // Selalu dari awal tahun
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                break;
            case 'tahunan':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                break;
        }
    }
    
    public function exportPdf()
    {
        $data = $this->getLaporanData();
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        $pdf = PDF::loadView('exports.laporan-pdf', [
            'data' => $data,
            'tanggalMulai' => $this->tanggalMulai,
            'tanggalSelesai' => $this->tanggalSelesai,
            'tahunAnggaran' => $tahunAnggaran,
            'persentaseTarget' => $this->persentaseTarget
        ]);
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-penerimaan-' . date('Y-m-d') . '.pdf');
    }
    
    public function exportExcel()
    {
        $data = $this->getLaporanData();
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        return Excel::download(new LaporanPenerimaanExport(
            $data,
            $this->tanggalMulai,
            $this->tanggalSelesai,
            $tahunAnggaran,
            $this->persentaseTarget
        ), 'laporan-penerimaan-' . date('Y-m-d') . '.xlsx');
    }
    
    private function getLaporanData()
{
    if (!$this->tahunAnggaranId) {
        return [];
    }
    
    $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
    $tahun = $tahunAnggaran->tahun;
    
    // Tentukan bulan yang sedang dilaporkan
    $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;

    // Ambil persentase target berdasarkan tipe filter
if (strpos($this->tipeFilter, 'minggu') !== false) {
    $persentaseTarget = TargetPeriode::getPersentaseForWeek($this->tahunAnggaranId, $this->tanggalSelesai);
} else {
    $persentaseTarget = TargetPeriode::getPersentaseForBulan($this->tahunAnggaranId, $bulanAkhir);
}

$this->persentaseTarget = round($persentaseTarget, 2);
    
    // Ambil persentase target dari model TargetPeriode
    $persentaseTarget = TargetPeriode::getPersentaseForBulan($this->tahunAnggaranId, $bulanAkhir);
    $this->persentaseTarget = $persentaseTarget ?: 0; // Default ke 0 jika tidak ada target
    
    // Ambil semua kode rekening dan kelompokkan berdasarkan level
    $kodeRekening = KodeRekening::orderBy('kode')->get();
    $kodeByLevel = [];
    foreach ($kodeRekening as $kode) {
        $kodeByLevel[$kode->level][] = $kode;
    }
    
    // Siapkan data untuk semua kode rekening
    $dataPerKode = [];
    
    // Langkah 1: Inisialisasi semua data kode rekening
    foreach ($kodeRekening as $kode) {
        // Siapkan array penerimaan per bulan
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
    
    // Langkah 2: Hitung nilai untuk level terbawah (level 5)
    if (isset($kodeByLevel[5])) {
        foreach ($kodeByLevel[5] as $kode) {
            // Ambil target anggaran
            $targetData = TargetAnggaran::where('kode_rekening_id', $kode->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
            
            if ($targetData) {
                $dataPerKode[$kode->id]['target_anggaran'] = $targetData->jumlah;
            }
            
            // Ambil data penerimaan
            $penerimaan = Penerimaan::where('kode_rekening_id', $kode->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->whereYear('tanggal', $tahun)
                ->whereDate('tanggal', '<=', $this->tanggalSelesai)
                ->get();
                
            
            foreach ($penerimaan as $p) {
                $bulan = $p->tanggal->month;
                $dataPerKode[$kode->id]['penerimaan_per_bulan'][$bulan] += $p->jumlah;
                $dataPerKode[$kode->id]['realisasi_sd_bulan_ini'] += $p->jumlah;
            }
        }
    }
    
    // Langkah 3: Agregasi dari bawah ke atas (level 5 -> 4 -> 3 -> 2 -> 1)
    for ($level = 4; $level >= 1; $level--) {
        if (isset($kodeByLevel[$level])) {
            foreach ($kodeByLevel[$level] as $kode) {
                // Cari semua anak langsung
                $children = $kodeRekening->where('parent_id', $kode->id);
                
                // Hitung total dari anak-anak
                foreach ($children as $child) {
                    $childData = $dataPerKode[$child->id];
                    
                    // Tambahkan target anggaran
                    $dataPerKode[$kode->id]['target_anggaran'] += $childData['target_anggaran'];
                    
                    // Tambahkan realisasi
                    $dataPerKode[$kode->id]['realisasi_sd_bulan_ini'] += $childData['realisasi_sd_bulan_ini'];
                    
                    // Tambahkan penerimaan per bulan
                    for ($i = 1; $i <= 12; $i++) {
                        $dataPerKode[$kode->id]['penerimaan_per_bulan'][$i] += $childData['penerimaan_per_bulan'][$i];
                    }
                }
            }
        }
    }
    
    // Langkah 4: Format data untuk output
    $dataLaporan = [];
    
    foreach ($kodeRekening as $kode) {
        $data = $dataPerKode[$kode->id];
        $targetAnggaran = $data['target_anggaran'];
        $realisasiSdBulanIni = $data['realisasi_sd_bulan_ini'];
        
        // Hitung target sampai dengan bulan ini
        $targetSdBulanIni = $targetAnggaran * ($this->persentaseTarget / 100);
        
        // Hitung lebih/kurang dari target (nilai positif = kurang dari target)
        $lebihKurang = $targetSdBulanIni - $realisasiSdBulanIni;
        
        // Hitung persentase realisasi
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
    
    // Urutkan data laporan berdasarkan kode rekening
    usort($dataLaporan, function($a, $b) {
        return $a['kode'] <=> $b['kode'];
    });
    
    return $dataLaporan;
}
    
    public function render()
    {
        $data = $this->getLaporanData();
        
        return view('livewire.laporan.index', [
            'data' => $data
        ]);
    }
}