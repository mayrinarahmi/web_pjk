<?php

namespace App\Exports;

use App\Models\KodeRekening;
use App\Models\Penerimaan;
use App\Models\TargetAnggaran;
use App\Models\TahunAnggaran;
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
    protected $data = [];
    protected $rowNumber = 0;
    protected $summaryData = [];

    public function __construct($tahun, $tanggalAwal = null, $tanggalAkhir = null, $skpdId = null)
    {
        $this->tahun = $tahun;
        $this->tanggalAwal = $tanggalAwal;
        $this->tanggalAkhir = $tanggalAkhir;
        $this->skpdId = $skpdId;
        $this->loadData();
    }

    protected function loadData()
    {
        // Get tahun_anggaran_id
        $tahunAnggaran = TahunAnggaran::where('tahun', $this->tahun)
            ->where('jenis_anggaran', 'murni')
            ->first();
            
        $tahunAnggaranId = $tahunAnggaran ? $tahunAnggaran->id : null;
        $tahunLalu = $this->tahun - 1;
        
        // Initialize summary
        $this->summaryData = [
            'pendapatan_daerah' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0],
            'belanja_daerah' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0],
            'surplus_defisit' => ['target' => 0, 'realisasi' => 0, 'realisasi_lalu' => 0]
        ];
        
        // Get all kode rekening - HANYA PENDAPATAN (kode 4.x.x)
        $kodeRekenings = KodeRekening::where('is_active', 1)
            ->where('kode', 'LIKE', '4%')
            ->orderByRaw("
                CAST(SUBSTRING_INDEX(kode, '.', 1) AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 2), '.', -1), SUBSTRING_INDEX(kode, '.', 1)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 3), '.', -1), SUBSTRING_INDEX(kode, '.', 2)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 4), '.', -1), SUBSTRING_INDEX(kode, '.', 3)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 5), '.', -1), SUBSTRING_INDEX(kode, '.', 4)), '0') AS UNSIGNED),
                CAST(IFNULL(NULLIF(SUBSTRING_INDEX(SUBSTRING_INDEX(kode, '.', 6), '.', -1), SUBSTRING_INDEX(kode, '.', 5)), '0') AS UNSIGNED)
            ")
            ->get();
            
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
                
                if (!empty($childrenIds)) {
                    if ($tahunAnggaranId) {
                        $target = TargetAnggaran::whereIn('kode_rekening_id', $childrenIds)
                            ->where('tahun_anggaran_id', $tahunAnggaranId)
                            ->sum('jumlah');
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
            
            // PERBAIKAN: Skip jika semua nilai 0 (tidak ada transaksi)
            if ($target == 0 && $realisasi == 0 && $realisasiLalu == 0) {
                continue; // Skip this row
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
            
        return [
            ['PEMERINTAH KOTA BANJARMASIN'],
            ['LAPORAN REALISASI ANGGARAN PENDAPATAN DAN BELANJA DAERAH (KONSOLIDASI)'],
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
        
        // PERBAIKAN: Merge cells untuk header kolom tabel
        $sheet->mergeCells('A6:A7'); // Kode Rekening
        $sheet->mergeCells('B6:B7'); // URAIAN  
        $sheet->mergeCells('C6:C7'); // ANGGARAN
        $sheet->mergeCells('D6:D7'); // REALISASI
        $sheet->mergeCells('E6:E7'); // %
        $sheet->mergeCells('F6:F7'); // REALISASI tahun lalu
        
        // Center alignment untuk header
        $sheet->getStyle('A1:F4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:F3')->getFont()->setBold(true);
        
        // Header tabel - apply to rows 6-8
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
                
                // Bold untuk level 1 dan 2
                foreach ($this->data as $index => $row) {
                    $rowNumber = $index + 9; // Starting from row 9
                    
                    if ($row['level'] <= 2) {
                        $sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)
                            ->getFont()->setBold(true);
                    }
                    
                    if ($row['level'] == 1) {
                        $sheet->getStyle('A' . $rowNumber . ':F' . $rowNumber)
                            ->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setRGB('E8F4F8');
                    }
                }
            }
        ];
    }
}