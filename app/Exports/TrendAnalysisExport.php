<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TrendAnalysisExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $headers;
    
    public function __construct($exportData)
    {
        $this->headers = $exportData['headers'];
        $this->data = $exportData['data'];
    }
    
    public function array(): array
    {
        return $this->data;
    }
    
    public function headings(): array
    {
        return $this->headers;
    }
    
    public function columnWidths(): array
    {
        $widths = [
            'A' => 15,  // Kode
            'B' => 40,  // Nama
        ];
        
        // Dynamic column widths for year data
        $col = 'C';
        for ($i = 2; $i < count($this->headers); $i++) {
            $widths[$col] = 15;
            $col++;
        }
        
        return $widths;
    }
    
    public function styles(Worksheet $sheet)
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        
        return [
            // Header row styling
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '696CFF']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true
                ]
            ],
            
            // All cells border
            "A1:{$lastCol}{$lastRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E0E0E0']
                    ]
                ]
            ],
            
            // Data cells alignment
            "A2:B{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ],
            
            "C2:{$lastCol}{$lastRow}" => [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]
        ];
    }
    
    public function title(): string
    {
        return 'Analisis Tren';
    }
}