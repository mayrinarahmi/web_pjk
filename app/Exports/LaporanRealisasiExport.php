<?php

namespace App\Exports;

use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanRealisasiExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithEvents
{
    protected $tahun;
    protected $tanggalAwal;
    protected $tanggalAkhir;
    protected $skpdId;
    protected $allowedKodeRekeningIds; // NEW: Filter by kode rekening
    protected $data = [];
    protected $rowNumber = 0;
    protected $summaryData = [];

    public function __construct($tahun, $tanggalAwal = null, $tanggalAkhir = null, $skpdId = null, $allowedKodeRekeningIds = [])
    {
        $this->tahun = $tahun;
        $this->tanggalAwal = $tanggalAwal;
        $this->tanggalAkhir = $tanggalAkhir;
        $this->skpdId = $skpdId;
        $this->allowedKodeRekeningIds = $allowedKodeRekeningIds;
        $this->loadData();
    }

    protected function loadData()
    {
        // Get tahun_anggaran_id - PRIORITAS: yang aktif, fallback ke perubahan, lalu murni
        $tahunAnggaran = TahunAnggaran::where('tahun', $this->tahun)
            ->where('is_active', 1)
            ->first();

        // Jika tidak ada yang aktif, ambil perubahan dulu, baru murni
        if (!$tahunAnggaran) {
            $tahunAnggaran = TahunAnggaran::where('tahun', $this->tahun)
                ->orderByRaw("FIELD(jenis_anggaran, 'perubahan', 'murni')")
                ->first();
        }

        $tahunAnggaranId = $tahunAnggaran ? $tahunAnggaran->id : null;
        $tahunLalu = $this->tahun - 1;

        // Log untuk debugging
        Log::info('Export LRA - Tahun Anggaran Selected', [
            'tahun' => $this->tahun,
            'tahun_anggaran_id' => $tahunAnggaranId,
            'jenis_anggaran' => $tahunAnggaran ? $tahunAnggaran->jenis_anggaran : 'N/A',
            'is_active' => $tahunAnggaran ? $tahunAnggaran->is_active : 'N/A'
        ]);

        // Initialize summary
        $this->summaryData = [
            'pendapatan_daerah' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0],
            'belanja_daerah' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0],
            'surplus_defisit' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0]
        ];
        
        // Get all kode rekening - HANYA PENDAPATAN (kode 4.x.x)
        $query = KodeRekening::where('is_active', 1)
            ->where('kode', 'LIKE', '4%');
        
        // NEW: Apply filter by allowed kode rekening IDs
        if (!empty($this->allowedKodeRekeningIds)) {
            $query->whereIn('id', $this->allowedKodeRekeningIds);
        }
        
        $kodeRekenings = $query->orderByRaw("
                CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1), SUBSTRING_INDEX(kode, '.', 1)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1), SUBSTRING_INDEX(kode, '.', 2)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1), SUBSTRING_INDEX(kode, '.', 3)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1), SUBSTRING_INDEX(kode, '.', 4)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1), SUBSTRING_INDEX(kode, '.', 5)), '0') AS UNSIGNED)
            ")
            ->get();

        // Log summary
        Log::info('Export LRA - Processing Kode Rekening', [
            'total_kode' => $kodeRekenings->count(),
            'allowed_kode_filter' => !empty($this->allowedKodeRekeningIds) ? count($this->allowedKodeRekeningIds) : 'NONE (Konsolidasi)',
            'skpd_id_filter' => $this->skpdId ?? 'NONE (Konsolidasi)'
        ]);

        foreach ($kodeRekenings as $kode) {
            $target = 0;
            $realisasi = 0;
            $realisasiLalu = 0;
            
            if ($kode->level == 6) {
                // Level 6: Direct data
                if ($tahunAnggaranId) {
                    $target = TargetAnggaran::where('kode_rekening_id', $kode->id)
                        ->where('tahun_anggaran_id', $tahunAnggaranId)
                        ->value('jumlah') ?? 0;

                    // Debug logging untuk kode tertentu
                    if (in_array($kode->kode, ['4.1.01.09.01.0001', '4.1.01.06.01.0001', '4'])) {
                        Log::info("Export LRA - Level 6 Target", [
                            'kode' => $kode->kode,
                            'kode_rekening_id' => $kode->id,
                            'tahun_anggaran_id' => $tahunAnggaranId,
                            'target' => $target
                        ]);
                    }
                }
                
                // Realisasi tahun ini
                $query = Penerimaan::where('kode_rekening_id', $kode->id)
                    ->where('tahun', $this->tahun);
                
                // Add SKPD filter if specified
                if ($this->skpdId) {
                    $query->where('skpd_id', $this->skpdId);
                }
                    
                if ($this->tanggalAwal && $this->tanggalAkhir) {
                    $query->whereBetween('tanggal', [$this->tanggalAwal, $this->tanggalAkhir]);
                }
                
                $realisasi = $query->sum('jumlah');
                
                // Realisasi tahun lalu
                $queryLalu = Penerimaan::where('kode_rekening_id', $kode->id)
                    ->where('tahun', $tahunLalu);
                
                // Add SKPD filter for last year too
                if ($this->skpdId) {
                    $queryLalu->where('skpd_id', $this->skpdId);
                }
                    
                if ($this->tanggalAwal && $this->tanggalAkhir) {
                    // Ambil periode yang sama tahun lalu
                    $tanggalAwalLalu = date('Y-m-d', strtotime('-1 year', strtotime($this->tanggalAwal)));
                    $tanggalAkhirLalu = date('Y-m-d', strtotime('-1 year', strtotime($this->tanggalAkhir)));
                    $queryLalu->whereBetween('tanggal', [$tanggalAwalLalu, $tanggalAkhirLalu]);
                }
                
                $realisasiLalu = $queryLalu->sum('jumlah');
            } else {
                // Level 1-5: Aggregate
                $childrenIds = $this->getLevel6Descendants($kode);
                
                // NEW: Filter children by allowed IDs
                if (!empty($this->allowedKodeRekeningIds)) {
                    $childrenIds = array_intersect($childrenIds, $this->allowedKodeRekeningIds);
                }
                
                if (!empty($childrenIds)) {
                    if ($tahunAnggaranId) {
                        $target = TargetAnggaran::whereIn('kode_rekening_id', $childrenIds)
                            ->where('tahun_anggaran_id', $tahunAnggaranId)
                            ->sum('jumlah');

                        // Debug logging untuk kode tertentu
                        if (in_array($kode->kode, ['4', '4.1', '4.1.01', '4.1.01.09', '4.1.01.09.01'])) {
                            Log::info("Export LRA - Level {$kode->level} Target (Aggregate)", [
                                'kode' => $kode->kode,
                                'children_count' => count($childrenIds),
                                'tahun_anggaran_id' => $tahunAnggaranId,
                                'target' => $target
                            ]);
                        }
                    }
                    
                    $query = Penerimaan::whereIn('kode_rekening_id', $childrenIds)
                        ->where('tahun', $this->tahun);
                    
                    // Add SKPD filter
                    if ($this->skpdId) {
                        $query->where('skpd_id', $this->skpdId);
                    }
                        
                    if ($this->tanggalAwal && $this->tanggalAkhir) {
                        $query->whereBetween('tanggal', [$this->tanggalAwal, $this->tanggalAkhir]);
                    }
                    
                    $realisasi = $query->sum('jumlah');
                    
                    // Tahun lalu
                    $queryLalu = Penerimaan::whereIn('kode_rekening_id', $childrenIds)
                        ->where('tahun', $tahunLalu);
                    
                    // Add SKPD filter
                    if ($this->skpdId) {
                        $queryLalu->where('skpd_id', $this->skpdId);
                    }
                        
                    if ($this->tanggalAwal && $this->tanggalAkhir) {
                        $tanggalAwalLalu = date('Y-m-d', strtotime('-1 year', strtotime($this->tanggalAwal)));
                        $tanggalAkhirLalu = date('Y-m-d', strtotime('-1 year', strtotime($this->tanggalAkhir)));
                        $queryLalu->whereBetween('tanggal', [$tanggalAwalLalu, $tanggalAkhirLalu]);
                    }
                    
                    $realisasiLalu = $queryLalu->sum('jumlah');
                }
            }
            
            // Skip jika semua nilai 0 (tidak ada transaksi)
            if ($target == 0 && $realisasi == 0 && $realisasiLalu == 0) {
                continue;
            }
            
            // Update summary untuk level 1
            if ($kode->level == 1 && $kode->kode == '4') {
                $this->summaryData['pendapatan_daerah'] = [
                    'target' => $target,
                    'realisasi' => $realisasi,
                    'realisasi_lalu' => $realisasiLalu
                ];
            }
            
            $this->data[] = [
                'kode' => $kode->kode,
                'nama' => strtoupper($kode->nama),
                'level' => $kode->level,
                'target' => $target,
                'realisasi' => $realisasi,
                'persentase' => $target > 0 ? round(($realisasi / $target * 100), 2) : 0,
                'realisasi_lalu' => $realisasiLalu
            ];
        }

        // Log final summary
        $targetCount = count(array_filter($this->data, function($d) { return $d['target'] > 0; }));
        $totalTarget = array_sum(array_column($this->data, 'target'));

        Log::info('Export LRA - Final Data Summary', [
            'total_rows' => count($this->data),
            'rows_with_target' => $targetCount,
            'total_target_amount' => $totalTarget
        ]);
    }

    private function getLevel6Descendants($kode)
    {
        $descendants = [];
        
        if ($kode->level == 6) {
            return [$kode->id];
        }
        
        $children = KodeRekening::where('parent_id', $kode->id)->get();
        
        foreach ($children as $child) {
            if ($child->level == 6) {
                $descendants[] = $child->id;
            } else {
                $descendants = array_merge($descendants, $this->getLevel6Descendants($child));
            }
        }
        
        return $descendants;
    }

    public function collection()
    {
        return collect($this->data);
    }

    public function headings(): array
    {
        $periode = $this->tanggalAwal && $this->tanggalAkhir 
            ? date('d F Y', strtotime($this->tanggalAwal)) . ' Sampai ' . date('d F Y', strtotime($this->tanggalAkhir))
            : '01 Januari ' . $this->tahun . ' Sampai 31 Desember ' . $this->tahun;
        
        // NEW: Tentukan label berdasarkan filter
        $label = !empty($this->allowedKodeRekeningIds) && $this->skpdId 
            ? 'PER SKPD' 
            : 'KONSOLIDASI';
            
        return [
            ['PEMERINTAH KOTA BANJARMASIN'],
            ['LAPORAN REALISASI ANGGARAN PENDAPATAN DAN BELANJA DAERAH (' . $label . ')'],
            ['TAHUN ANGGARAN ' . $this->tahun],
            [$periode],
            [''],
            [
                'Kode',
                'URAIAN',
                'ANGGARAN ' . $this->tahun,
                'REALISASI ' . $this->tahun,
                '% ' . $this->tahun,
                'REALISASI ' . ($this->tahun - 1)
            ],
            [
                'Rekening',
                '',
                '',
                '',
                '',
                ''
            ],
            [
                '1',
                '2',
                '3',
                '4',
                '5 = (4 / 3) * 100',
                '6'
            ]
        ];
    }

    public function map($row): array
    {
        $this->rowNumber++;
        
        return [
            $row['kode'],
            $row['nama'],
            $row['target'],
            $row['realisasi'],
            number_format($row['persentase'], 2, ',', '.'),
            $row['realisasi_lalu']
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 80,
            'C' => 25,
            'D' => 25,
            'E' => 15,
            'F' => 25
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Merge cells untuk header
        $sheet->mergeCells('A1:F1');
        $sheet->mergeCells('A2:F2');
        $sheet->mergeCells('A3:F3');
        $sheet->mergeCells('A4:F4');
        
        // Merge cells untuk header kolom tabel
        $sheet->mergeCells('A6:A7');
        $sheet->mergeCells('B6:B7');
        $sheet->mergeCells('C6:C7');
        $sheet->mergeCells('D6:D7');
        $sheet->mergeCells('E6:E7');
        $sheet->mergeCells('F6:F7');
        
        // Center alignment untuk header
        $sheet->getStyle('A1:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:F3')->getFont()->setBold(true);
        
        // Header tabel
        $sheet->getStyle('A6:F8')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN
                ]
            ]
        ]);
        
        // Format angka
        $lastRow = $this->rowNumber + 8;
        $sheet->getStyle('C9:D' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        $sheet->getStyle('F9:F' . $lastRow)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                
                // Process each data row
                foreach ($this->data as $index => $row) {
                    $rowNumber = $index + 9;
                    
                    if ($row['level'] <= 2) {
                        $sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)
                            ->getFont()->setBold(true);
                        
                        $sheet->getStyle('B' . $rowNumber)
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    }
                    
                    if ($row['level'] == 1) {
                        $sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('E8F4F8');
                    }
                    
                    $sheet->getStyle('B' . $rowNumber)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    
                    $sheet->getStyle('A' . $rowNumber)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    
                    $sheet->getStyle('C' . $rowNumber . ':D' . $rowNumber)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        
                    $sheet->getStyle('F' . $rowNumber)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        
                    $sheet->getStyle('E' . $rowNumber)
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        ];
    }
}