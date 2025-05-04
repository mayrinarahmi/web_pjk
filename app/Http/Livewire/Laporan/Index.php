<?php

namespace App\Http\Livewire\Laporan;

use Livewire\Component;
use App\Models\Penerimaan;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use App\Models\TargetAnggaran;
use App\Models\TargetBulan;
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
    public $persentaseTarget = 40; // Default 40% sesuai contoh laporan
    
    public function mount()
    {
        $this->tahunAnggaran = TahunAnggaran::orderBy('tahun', 'desc')->get();
        $activeTahun = TahunAnggaran::where('is_active', true)->first();
        $this->tahunAnggaranId = $activeTahun ? $activeTahun->id : null;
        
        // Default tanggal (bulan ini)
        $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->tanggalSelesai = Carbon::now()->format('Y-m-d');
    }
    
    public function setFilter($tipe)
    {
        $this->tipeFilter = $tipe;
        
        switch ($tipe) {
            case 'bulanan':
                $this->tanggalMulai = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
            case 'triwulan1':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->startOfYear()->addMonths(3)->subDay()->format('Y-m-d');
                break;
            case 'triwulan2':
                $this->tanggalMulai = Carbon::now()->startOfYear()->addMonths(3)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->startOfYear()->addMonths(6)->subDay()->format('Y-m-d');
                break;
            case 'triwulan3':
                $this->tanggalMulai = Carbon::now()->startOfYear()->addMonths(6)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->startOfYear()->addMonths(9)->subDay()->format('Y-m-d');
                break;
            case 'triwulan4':
                $this->tanggalMulai = Carbon::now()->startOfYear()->addMonths(9)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfYear()->format('Y-m-d');
                break;
            case 'tahunan':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->tanggalSelesai = Carbon::now()->endOfYear()->format('Y-m-d');
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
        
        // Ambil semua kode rekening
        $kodeRekening = KodeRekening::orderBy('kode')->get();
        
        // Siapkan data laporan
        $dataLaporan = [];
        
        // Proses untuk setiap kode rekening
        foreach ($kodeRekening as $kode) {
            $targetAnggaran = 0; // Pagu Anggaran
            
            // Ambil target anggaran
            $targetData = TargetAnggaran::where('kode_rekening_id', $kode->id)
                ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                ->first();
                
            if ($targetData) {
                $targetAnggaran = $targetData->jumlah;
            }
            
            // Hitung target sampai dengan bulan ini (Target 40% pada contoh laporan)
            $targetSdBulanIni = $targetAnggaran * ($this->persentaseTarget / 100);
            
            // Inisialisasi array untuk menyimpan penerimaan per bulan
            $penerimaanPerBulan = [];
            for ($i = 1; $i <= 12; $i++) {
                $penerimaanPerBulan[$i] = 0;
            }
            
            // Hitung realisasi per bulan dan total
            $realisasiSdBulanIni = 0;
            
            if ($kode->level == 4) {
                // Untuk level 4, ambil data langsung dari tabel penerimaan
                $penerimaan = Penerimaan::where('kode_rekening_id', $kode->id)
                    ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                    ->whereYear('tanggal', $tahun)
                    ->whereDate('tanggal', '<=', $this->tanggalSelesai)
                    ->get();
                
                foreach ($penerimaan as $p) {
                    $bulan = $p->tanggal->month;
                    $penerimaanPerBulan[$bulan] += $p->jumlah;
                    $realisasiSdBulanIni += $p->jumlah;
                }
            } else {
                // Untuk level 1-3, hitung dari level di bawahnya
                $childrenIds = $kode->getAllLevel4Descendants();
                
                if (!empty($childrenIds)) {
                    $penerimaan = Penerimaan::whereIn('kode_rekening_id', $childrenIds)
                        ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                        ->whereYear('tanggal', $tahun)
                        ->whereDate('tanggal', '<=', $this->tanggalSelesai)
                        ->get();
                    
                    foreach ($penerimaan as $p) {
                        $bulan = $p->tanggal->month;
                        $penerimaanPerBulan[$bulan] += $p->jumlah;
                        $realisasiSdBulanIni += $p->jumlah;
                    }
                }
            }
            
            // Hitung realisasi bulan ini (bulan terakhir dalam periode laporan)
            $realisasiBulanIni = $penerimaanPerBulan[$bulanAkhir];
            
            // Hitung lebih/kurang dari target
            $lebihKurang = $targetSdBulanIni - $realisasiSdBulanIni;
            
            // Hitung persentase realisasi
            $persentase = 0;
            if ($targetAnggaran > 0) {
                $persentase = ($realisasiSdBulanIni / $targetAnggaran) * 100;
            }
            
            // Tambahkan ke data laporan
            $dataLaporan[] = [
                'id' => $kode->id,
                'kode' => $kode->kode,
                'uraian' => $kode->nama,
                'level' => $kode->level,
                'parent_id' => $kode->parent_id,
                'persentase' => round($persentase, 2),
                'target_anggaran' => $targetAnggaran,
                'target_sd_bulan_ini' => $targetSdBulanIni,
                'kurang_dari_target' => $lebihKurang,
                'realisasi_sd_bulan_ini' => $realisasiSdBulanIni,
                'realisasi_bulan_ini' => $realisasiBulanIni,
                'penerimaan_per_bulan' => $penerimaanPerBulan
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