<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Carbon\Carbon;

class LaporanPenerimaanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnWidths
{
    protected $data;
    protected $tanggalMulai;
    protected $tanggalSelesai;
    protected $tahunAnggaran;
    protected $persentaseTarget;
    protected $bulanAkhir; 
    protected $viewMode;
    protected $bulanAwal;
    
    public function __construct($data, $tanggalMulai, $tanggalSelesai, $tahunAnggaran, $persentaseTarget, $viewMode = 'cumulative', $bulanAwal = 1)
    {
        $this->data = $data;
        $this->tanggalMulai = $tanggalMulai;
        $this->tanggalSelesai = $tanggalSelesai;
        $this->tahunAnggaran = $tahunAnggaran;
        $this->persentaseTarget = $persentaseTarget;
        $this->bulanAkhir = Carbon::parse($tanggalSelesai)->month;
        $this->viewMode = $viewMode;
        $this->bulanAwal = $bulanAwal;
    }
    
    public function collection()
    {
        $rows = [];
        $periodeAwal = Carbon::parse($this->tanggalMulai)->format('d-m-Y');
        $periodeAkhir = Carbon::parse($this->tanggalSelesai)->format('d-m-Y');
        
        $modeTitle = $this->viewMode === 'specific' ? 'Triwulan Spesifik' : 'Kumulatif s/d Triwulan';
        
        // Tambahkan header laporan
        $rows[] = ['LAPORAN REALISASI PENERIMAAN'];
        $rows[] = ['BPKPAD KOTA BANJARMASIN'];
        $rows[] = ['Periode: ' . $periodeAwal . ' s/d ' . $periodeAkhir . ' (Mode: ' . $modeTitle . ')'];
        
        // Tambahkan header kolom (tidak ada baris kosong, langsung header)
        $headers = [
            'Kode Rekening',
            'Uraian',
            '%',
            'Pagu Anggaran',
            'Target ' . $this->persentaseTarget . '%',
            'Kurang dr Target ' . $this->persentaseTarget . '%',
            'Penerimaan per Rincian Objek'
        ];
        
        $tampilkanDariBulan = $this->viewMode === 'specific' ? $this->bulanAwal : 1;
        $tampilkanSampaiBulan = $this->bulanAkhir;
        
        // Tambahkan header bulan
        for ($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++) {
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
            for ($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++) {
                $row[] = isset($item['penerimaan_per_bulan'][$i]) ? $item['penerimaan_per_bulan'][$i] : 0;
            }
            
            $rows[] = $row;
        }
        
        return collect($rows);
    }
    
    public function headings(): array
    {
        return [];
    }
    
    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->data) + 4; // 4 baris header (tanpa baris kosong)
        
        $tampilkanDariBulan = $this->viewMode === 'specific' ? $this->bulanAwal : 1;
        $tampilkanSampaiBulan = $this->bulanAkhir;
        $jumlahKolomBulan = $tampilkanSampaiBulan - $tampilkanDariBulan + 1;
        
        $lastColumn = 7 + $jumlahKolomBulan;
        $lastColumnName = $this->getColumnName($lastColumn);
        
        // Merge cells untuk judul
        $sheet->mergeCells('A1:' . $lastColumnName . '1');
        $sheet->mergeCells('A2:' . $lastColumnName . '2');
        $sheet->mergeCells('A3:' . $lastColumnName . '3');
        
        // Style untuk judul laporan (baris 1-3) - HARUS TEBAL
        $sheet->getStyle('A1:A3')->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ]);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(12);
        
        // Style untuk header kolom (baris 4) - HARUS TEBAL
        $sheet->getStyle('A4:' . $lastColumnName . '4')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11
            ],
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
        
        // Style untuk semua data - DEFAULT TIDAK TEBAL
        $sheet->getStyle('A5:' . $lastColumnName . $lastRow)->applyFromArray([
            'font' => [
                'bold' => false, // Explicitly set to false
                'size' => 10
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        // Override style untuk kategori utama saja (level 1 dan 2) - TEBAL
        $currentRow = 5; // Mulai dari baris 5 karena header di baris 4
        foreach ($this->data as $item) {
            if ($item['level'] <= 2) {
                // Hanya kategori level 1 dan 2 yang tebal
                $sheet->getStyle('A' . $currentRow . ':' . $lastColumnName . $currentRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 10
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $item['level'] == 1 ? 'F0F0F0' : 'F8F8F8'],
                    ],
                ]);
            }
            $currentRow++;
        }
        
        // Alignment untuk kolom angka (kanan)
        $sheet->getStyle('C5:' . $lastColumnName . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        // Format angka
        $sheet->getStyle('D5:' . $lastColumnName . $lastRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('C5:C' . $lastRow)->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Wrap text untuk kolom Uraian
        $sheet->getStyle('B5:B' . $lastRow)->getAlignment()->setWrapText(true);
        
        // Auto height untuk semua rows
        for ($i = 1; $i <= $lastRow; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(-1);
        }
        
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
        
        $tampilkanDariBulan = $this->viewMode === 'specific' ? $this->bulanAwal : 1;
        $tampilkanSampaiBulan = $this->bulanAkhir;
        
        $colIndex = 8; // Start from H
        for ($i = $tampilkanDariBulan; $i <= $tampilkanSampaiBulan; $i++) {
            $widths[$this->getColumnName($colIndex)] = 15;
            $colIndex++;
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