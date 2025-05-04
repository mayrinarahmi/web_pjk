<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class LaporanPenerimaanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $tahunAnggaran;
    protected $persentaseTarget;
    
    public function __construct($data, $tanggalMulai, $tanggalSelesai, $tahunAnggaran, $persentaseTarget)
    {
        $this->data = $data;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->tahunAnggaran = $tahunAnggaran;
        $this->persentaseTarget = $persentaseTarget;
    }
    
    public function collection()
    {
        $rows = [];
        $periodeAwal = Carbon::parse($this->tanggalMulai)->format('d-m-Y');
        $periodeAkhir = Carbon::parse($this->tanggalSelesai)->format('d-m-Y');
        
        // Tambahkan header laporan
        $rows[] = ['LAPORAN REALISASI PENERIMAAN'];
        $rows[] = ['BPKPAD KOTA BANJARMASIN'];
        $rows[] = ['Periode: ' . $periodeAwal . ' s/d ' . $periodeAkhir];
        $rows[] = []; // Baris kosong
        
        // Tambahkan header kolom
        $headers = [
            'Kode Rekening',
            'Uraian',
            '%',
            'Pagu Anggaran',
            'Target ' . $this->persentaseTarget . '%',
            'Kurang dr Target ' . $this->persentaseTarget . '%',
            'Penerimaan per Rincian Objek Penerimaan'
        ];
        
        // Tambahkan header bulan
        $bulanAwal = Carbon::parse($this->tanggalMulai)->month;
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        
        for ($i = 1; $i <= $bulanAkhir; $i++) {
            $headers[] = Carbon::create()->month($i)->format('F');
        }
        
        $rows[] = $headers;
        
        // Tambahkan data
        foreach ($this->data as $item) {
            $row = [
                $item['kode'],
                str_repeat('  ', $item['level'] - 1) . $item['uraian'],
                $item['persentase'] . '%',
                $item['target_anggaran'],
                $item['target_sd_bulan_ini'],
                $item['kurang_dari_target'],
                $item['realisasi_sd_bulan_ini']
            ];
            
            // Tambahkan data penerimaan per bulan
            for ($i = 1; $i <= $bulanAkhir; $i++) {
                $row[] = $item['penerimaan_per_bulan'][$i];
            }
            
            $rows[] = $row;
        }
        
        return collect($rows);
    }
    
    public function headings(): array
    {
        // Headings sudah dimasukkan dalam collection
        return [];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Styling untuk Excel
        $lastRow = count($this->data) + 5; // 5 baris header
        $lastColumn = 7 + Carbon::parse($this->tanggalSelesai)->month; // 7 kolom awal + jumlah bulan
        
        // Merge cells untuk judul
        $sheet->mergeCells('A1:' . $this->getColumnName($lastColumn) . '1');
        $sheet->mergeCells('A2:' . $this->getColumnName($lastColumn) . '2');
        $sheet->mergeCells('A3:' . $this->getColumnName($lastColumn) . '3');
        
        // Style untuk judul
        $sheet->getStyle('A1:A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setBold(true);
        
        // Style untuk header kolom
        $sheet->getStyle('A5:' . $this->getColumnName($lastColumn) . '5')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E9ECEF'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Style untuk data
        $sheet->getStyle('A6:' . $this->getColumnName($lastColumn) . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Alignment untuk kolom angka (kanan)
        $sheet->getStyle('C6:' . $this->getColumnName($lastColumn) . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Format angka
        $sheet->getStyle('D6:' . $this->getColumnName($lastColumn) . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('C6:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        return $sheet;
    }
    
    public function columnWidths(): array
    {
        $widths = [
            'A' => 20, // Kode Rekening
            'B' => 40, // Uraian
            'C' => 10, // %
            'D' => 20, // Pagu Anggaran
            'E' => 20, // Target %
            'F' => 20, // Kurang dr Target %
            'G' => 20, // Penerimaan per Rincian Objek
        ];
        
        // Tambahkan lebar untuk kolom bulan
        $bulanAkhir = Carbon::parse($this->tanggalSelesai)->month;
        for ($i = 1; $i <= $bulanAkhir; $i++) {
            $widths[$this->getColumnName(7 + $i - 1)] = 15; // Kolom bulan
        }
        
        return $widths;
    }
    
    private function getColumnName($columnNumber)
    {
        $dividend = $columnNumber;
        $columnName = '';
        
        while ($dividend > 0) {
            $modulo = ($dividend - 1) % 26;
            $columnName = chr(65 + $modulo) . $columnName;
            $dividend = (int)(($dividend - $modulo) / 26);
        }
        
        return $columnName;
    }
    
    public function title(): string
    {
        return 'Laporan Penerimaan';
    }
}
