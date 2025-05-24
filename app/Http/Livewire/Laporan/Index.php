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
    public $viewMode = 'cumulative'; // Mode tampilan: 'specific' atau 'cumulative'
    
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
            $this->persentaseTarget = $this->getTargetPersentase($bulanAkhir);
        }
    }
    
    // Tambahkan metode untuk mengubah mode tampilan
    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
        
        // Jika mode diubah, sesuaikan tanggal mulai berdasarkan mode dan filter yang aktif
        if (in_array($this->tipeFilter, ['triwulan1', 'triwulan2', 'triwulan3', 'triwulan4'])) {
            $this->updateDateRangeBasedOnFilter();
        }
    }
    
    // Metode bantu untuk mendapatkan persentase target - DIPERBAIKI
    private function getTargetPersentase($bulanAkhir)
    {
        // Jika mode kumulatif, gunakan persentase kumulatif
        if ($this->viewMode === 'cumulative') {
            return TargetPeriode::getPersentaseKumulatif($this->tahunAnggaranId, $bulanAkhir);
        } else {
            // Mode specific: gunakan persentase periode saja
            return TargetPeriode::getPersentaseForBulan($this->tahunAnggaranId, $bulanAkhir);
        }
    }

    public function setCustomFilter($tipeFilter, $tanggalMulai, $tanggalSelesai)
    {
        $this->tipeFilter = $tipeFilter;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
    }
    
    // Update metode setFilter untuk mendukung dual mode
    public function setFilter($tipe)
    {
        $this->tipeFilter = $tipe;
        $this->updateDateRangeBasedOnFilter();
    }
    
    // Metode baru untuk update rentang tanggal berdasarkan filter dan mode - DIPERBAIKI
    private function updateDateRangeBasedOnFilter()
    {
        $tahunSekarang = Carbon::now()->year;
        
        switch ($this->tipeFilter) {
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
                
            case 'bulanan':
                $this->tanggalMulai = Carbon::now()->startOfYear()->format('Y-m-d'); // Selalu dari awal tahun
                $this->tanggalSelesai = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;
                
            case 'triwulan1':
                // Mode spesifik: hanya bulan dalam triwulan
                if ($this->viewMode === 'specific') {
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 3, 31)->format('Y-m-d');
                } else {
                    // Mode kumulatif: dari awal tahun
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 3, 31)->format('Y-m-d');
                }
                break;
                
            case 'triwulan2':
                if ($this->viewMode === 'specific') {
                    // Mode spesifik: hanya Apr-Jun
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 4, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 6, 30)->format('Y-m-d');
                } else {
                    // Mode kumulatif: Jan-Jun
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 6, 30)->format('Y-m-d');
                }
                break;
                
            case 'triwulan3':
                if ($this->viewMode === 'specific') {
                    // Mode spesifik: hanya Jul-Sep
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 7, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 9, 30)->format('Y-m-d');
                } else {
                    // Mode kumulatif: Jan-Sep
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 9, 30)->format('Y-m-d');
                }
                break;
                
            case 'triwulan4':
                if ($this->viewMode === 'specific') {
                    // Mode spesifik: hanya Oct-Dec
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 10, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                } else {
                    // Mode kumulatif: Jan-Dec
                    $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                    $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                }
                break;
                
            case 'tahunan':
                $this->tanggalMulai = Carbon::createFromDate($tahunSekarang, 1, 1)->format('Y-m-d');
                $this->tanggalSelesai = Carbon::createFromDate($tahunSekarang, 12, 31)->format('Y-m-d');
                break;
        }
        
        // Update persentase target berdasarkan mode dan tanggal selesai
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        $this->persentaseTarget = $this->getTargetPersentase($bulanAkhir);
    }
    
    public function exportPdf()
    {
        $data = $this->getLaporanData();
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        // Pastikan ada data sebelum mencoba membuat PDF
        if (empty($data)) {
            session()->flash('error', 'Tidak ada data untuk diekspor');
            return;
        }
        
        // Tambahkan variabel $bulanAkhir untuk konsistensi dengan template
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
        
        // Atur orientasi PDF ke landscape agar semua kolom bulan muat
        $pdf->setPaper('a4', 'landscape');
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'laporan-penerimaan-' . date('Y-m-d') . '.pdf');
    }
    
    public function exportExcel()
    {
        $data = $this->getLaporanData();
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        
        // Pastikan ada data sebelum mencoba membuat Excel
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
    
    private function getLaporanData()
    {
        if (!$this->tahunAnggaranId) {
            return [];
        }
        
        $tahunAnggaran = TahunAnggaran::find($this->tahunAnggaranId);
        $tahun = $tahunAnggaran->tahun;
        
        // Tentukan bulan yang sedang dilaporkan
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;

        // Ambil persentase target berdasarkan mode dan tipe filter - DIPERBAIKI
        if (strpos($this->tipeFilter, 'minggu') !== false) {
            $persentaseTarget = TargetPeriode::getPersentaseForWeek($this->tahunAnggaranId, $this->tanggalSelesai);
        } else {
            // Gunakan method getTargetPersentase yang sudah diperbaiki
            $persentaseTarget = $this->getTargetPersentase($bulanAkhir);
        }

        $this->persentaseTarget = round($persentaseTarget, 2);
        
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
                $query = Penerimaan::where('kode_rekening_id', $kode->id)
                    ->where('tahun_anggaran_id', $this->tahunAnggaranId)
                    ->whereYear('tanggal', $tahun);
                
                // Sesuaikan rentang tanggal berdasarkan mode tampilan
                if ($this->viewMode === 'specific') {
                    // Mode spesifik: hanya dalam rentang tanggal tertentu
                    $query->whereDate('tanggal', '>=', $this->tanggalMulai)
                        ->whereDate('tanggal', '<=', $this->tanggalSelesai);
                } else {
                    // Mode kumulatif: dari awal tahun sampai tanggal selesai
                    $query->whereDate('tanggal', '<=', $this->tanggalSelesai);
                }
                
                $penerimaan = $query->get();
                
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
            
            // PERBAIKAN: Hitung lebih/kurang dari target (nilai positif = kurang dari target)
            // Jika pagu anggaran 0 atau negatif, maka kurang dari target juga 0
            if ($targetAnggaran <= 0) {
                $lebihKurang = 0;
            } else {
                $lebihKurang = $targetSdBulanIni - $realisasiSdBulanIni;
            }
            
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
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        
        return view('livewire.laporan.index', [
            'data' => $data,
            'bulanAwal' => $bulanAwal,
            'bulanAkhir' => $bulanAkhir
        ]);
    }
}