<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class LaporanRangkumanExport implements FromArray, WithEvents
{
    protected array $rows;
    protected array $totalRow;
    protected array $years;
    protected int   $tahunMulai;
    protected int   $tahunSelesai;

    // Warna header per tahun (ARGB)
    protected array $yearColors = [
        'FF4472C4', // Biru
        'FF70AD47', // Hijau
        'FFED7D31', // Orange
        'FF7030A0', // Ungu
        'FFC00000', // Merah
        'FF00B0A0', // Teal
    ];

    public function __construct(array $rows, array $totalRow, array $years, int $tahunMulai, int $tahunSelesai)
    {
        $this->rows         = $rows;
        $this->totalRow     = $totalRow;
        $this->years        = $years;
        $this->tahunMulai   = $tahunMulai;
        $this->tahunSelesai = $tahunSelesai;
    }

    public function array(): array
    {
        // Data akan di-build manual di AfterSheet, array kosong dulu
        return [[]];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $years      = $this->years;
                $numYears   = count($years);
                $totalCols  = 2 + ($numYears * 3); // NO + Unit Kerja + (3 col × numYears)

                // =====================
                // Helper: kolom letter dari index (0-based)
                // =====================
                $colLetter = function (int $idx): string {
                    $letters = '';
                    $idx++;
                    while ($idx > 0) {
                        $mod = ($idx - 1) % 26;
                        $letters = chr(65 + $mod) . $letters;
                        $idx = (int)(($idx - $mod) / 26);
                    }
                    return $letters;
                };

                $lastCol = $colLetter($totalCols - 1);

                // =====================
                // BARIS 1: Judul
                // =====================
                $sheet->setCellValue('A1', 'LAPORAN RINGKASAN PENERIMAAN DAERAH');
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF000000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F7']],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(24);

                // =====================
                // BARIS 2: Sub-judul
                // =====================
                $judul2 = "Tahun {$this->tahunMulai}";
                if ($this->tahunMulai !== $this->tahunSelesai) {
                    $judul2 .= " s/d {$this->tahunSelesai}";
                }
                $sheet->setCellValue('A2', $judul2);
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['bold' => false, 'size' => 11],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFD6E4F7']],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // =====================
                // BARIS 3: Tanggal cetak
                // =====================
                $sheet->setCellValue('A3', 'Dicetak pada: ' . now()->isoFormat('D MMMM Y'));
                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->getStyle('A3')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 9, 'color' => ['argb' => 'FF666666']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);

                // =====================
                // BARIS 4 & 5: Header tabel
                // =====================
                $headerRow1 = 4;
                $headerRow2 = 5;

                // NO
                $sheet->setCellValue("A{$headerRow1}", 'NO');
                $sheet->mergeCells("A{$headerRow1}:A{$headerRow2}");

                // Unit Kerja
                $sheet->setCellValue("B{$headerRow1}", 'Unit Kerja');
                $sheet->mergeCells("B{$headerRow1}:B{$headerRow2}");

                // Header per tahun
                foreach ($years as $i => $year) {
                    $startColIdx = 2 + ($i * 3);
                    $c1 = $colLetter($startColIdx);
                    $c2 = $colLetter($startColIdx + 1);
                    $c3 = $colLetter($startColIdx + 2);

                    // Tahun header (colspan=3)
                    $sheet->setCellValue("{$c1}{$headerRow1}", (string) $year);
                    $sheet->mergeCells("{$c1}{$headerRow1}:{$c3}{$headerRow1}");

                    // Sub-header
                    $sheet->setCellValue("{$c1}{$headerRow2}", 'Target');
                    $sheet->setCellValue("{$c2}{$headerRow2}", 'Realisasi');
                    $sheet->setCellValue("{$c3}{$headerRow2}", '%');

                    // Style header tahun
                    $color = $this->yearColors[$i % count($this->yearColors)];
                    $yearHeaderStyle = [
                        'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                    ];
                    $sheet->getStyle("{$c1}{$headerRow1}:{$c3}{$headerRow2}")->applyFromArray($yearHeaderStyle);
                }

                // Style NO dan Unit Kerja
                $mainHeaderStyle = [
                    'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF4472C4']],
                ];
                $sheet->getStyle("A{$headerRow1}:B{$headerRow2}")->applyFromArray($mainHeaderStyle);
                $sheet->getRowDimension($headerRow1)->setRowHeight(20);
                $sheet->getRowDimension($headerRow2)->setRowHeight(18);

                // =====================
                // BARIS DATA
                // =====================
                $dataStartRow = 6;
                $currentRow   = $dataStartRow;

                $numberFmt = '#,##0';
                $percentFmt = '#,##0.00"%"';

                foreach ($this->rows as $no => $row) {
                    $sheet->setCellValue("A{$currentRow}", $no + 1);
                    $sheet->setCellValue("B{$currentRow}", $row['nama']);

                    foreach ($years as $i => $year) {
                        $d = $row['per_tahun'][$year] ?? ['target' => 0, 'realisasi' => 0, 'persen' => 0];
                        $startColIdx = 2 + ($i * 3);
                        $c1 = $colLetter($startColIdx);
                        $c2 = $colLetter($startColIdx + 1);
                        $c3 = $colLetter($startColIdx + 2);

                        $sheet->setCellValue("{$c1}{$currentRow}", $d['target']);
                        $sheet->setCellValue("{$c2}{$currentRow}", $d['realisasi']);
                        $sheet->setCellValue("{$c3}{$currentRow}", $d['persen'] / 100);

                        $sheet->getStyle("{$c1}{$currentRow}:{$c2}{$currentRow}")
                            ->getNumberFormat()->setFormatCode($numberFmt);
                        $sheet->getStyle("{$c3}{$currentRow}")
                            ->getNumberFormat()->setFormatCode('0.00"%"');
                    }

                    // Alignment
                    $sheet->getStyle("A{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$currentRow}:{$lastCol}{$currentRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                    // Alternating row color
                    if ($no % 2 === 1) {
                        $sheet->getStyle("A{$currentRow}:{$lastCol}{$currentRow}")
                            ->getFill()->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB('FFF2F7FD');
                    }

                    $currentRow++;
                }

                // =====================
                // BARIS TOTAL
                // =====================
                $totalRowNum = $currentRow;
                $sheet->setCellValue("A{$totalRowNum}", '');
                $sheet->setCellValue("B{$totalRowNum}", 'TOTAL PAD SELURUHNYA');
                $sheet->mergeCells("A{$totalRowNum}:B{$totalRowNum}");

                foreach ($years as $i => $year) {
                    $d = $this->totalRow['per_tahun'][$year] ?? ['target' => 0, 'realisasi' => 0, 'persen' => 0];
                    $startColIdx = 2 + ($i * 3);
                    $c1 = $colLetter($startColIdx);
                    $c2 = $colLetter($startColIdx + 1);
                    $c3 = $colLetter($startColIdx + 2);

                    $sheet->setCellValue("{$c1}{$totalRowNum}", $d['target']);
                    $sheet->setCellValue("{$c2}{$totalRowNum}", $d['realisasi']);
                    $sheet->setCellValue("{$c3}{$totalRowNum}", $d['persen'] / 100);

                    $sheet->getStyle("{$c1}{$totalRowNum}:{$c2}{$totalRowNum}")
                        ->getNumberFormat()->setFormatCode($numberFmt);
                    $sheet->getStyle("{$c3}{$totalRowNum}")
                        ->getNumberFormat()->setFormatCode('0.00"%"');
                }

                $sheet->getStyle("A{$totalRowNum}:{$lastCol}{$totalRowNum}")->applyFromArray([
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFBDD7EE']],
                ]);
                $sheet->getStyle("C{$totalRowNum}:{$lastCol}{$totalRowNum}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                // =====================
                // BORDER seluruh tabel
                // =====================
                $tableRange = "A{$headerRow1}:{$lastCol}{$totalRowNum}";
                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF9BAAB5'],
                        ],
                    ],
                ]);

                // =====================
                // COLUMN WIDTHS
                // =====================
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(40);

                foreach ($years as $i => $year) {
                    $startColIdx = 2 + ($i * 3);
                    $sheet->getColumnDimension($colLetter($startColIdx))->setWidth(20);
                    $sheet->getColumnDimension($colLetter($startColIdx + 1))->setWidth(20);
                    $sheet->getColumnDimension($colLetter($startColIdx + 2))->setWidth(10);
                }

                // Freeze panes setelah header
                $sheet->freezePane("A{$dataStartRow}");
            },
        ];
    }
}
