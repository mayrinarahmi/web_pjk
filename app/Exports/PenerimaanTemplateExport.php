<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PenerimaanTemplateExport implements FromArray, WithStyles, WithColumnWidths
{
    private array $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = count($this->rows);

        // Header row
        $sheet->getStyle('A1:D1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        // Kolom kode & uraian sebagai teks agar tidak dikonversi Excel
        $sheet->getStyle('A:B')->getNumberFormat()->setFormatCode('@');

        // Border untuk semua baris
        if ($lastRow > 1) {
            $sheet->getStyle("A1:D{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(
                \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN
            );
        }

        // Wrap text kolom uraian, vertical center semua
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("A2:D{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Kolom jumlah: format angka
        $sheet->getStyle("D2:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
    }

    public function columnWidths(): array
    {
        return [
            'A' => 24, // kode
            'B' => 52, // uraian
            'C' => 15, // tanggal
            'D' => 20, // jumlah
        ];
    }
}
