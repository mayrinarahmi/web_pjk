<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SimpleTrendExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected $data;
    protected $startYear;
    protected $endYear;
    protected $title;
    
    public function __construct($params)
    {
        $this->data = $params['data'];
        $this->startYear = $params['startYear'];
        $this->endYear = $params['endYear'];
        $this->title = $params['title'];
    }
    
    public function array(): array
    {
        $exportData = [];
        
        foreach ($this->data as $row) {
            $exportRow = [
                $row['kode'],
                $row['nama']
            ];
            
            for ($year = $this->startYear; $year <= $this->endYear; $year++) {
                $exportRow[] = $row['tahun_' . $year];
            }
            
            $exportRow[] = $row['growth'] . '%';
            
            $exportData[] = $exportRow;
        }
        
        return $exportData;
    }
    
    public function headings(): array
    {
        $headers = ['Kode', 'Uraian'];
        
        for ($year = $this->startYear; $year <= $this->endYear; $year++) {
            $headers[] = $year;
        }
        
        $headers[] = 'Growth (%)';
        
        return $headers;
    }
    
    public function columnWidths(): array
    {
        $widths = [
            'A' => 15,
            'B' => 40
        ];
        
        $col = 'C';
        for ($year = $this->startYear; $year <= $this->endYear; $year++) {
            $widths[$col] = 15;
            $col++;
        }
        
        $widths[$col] = 12;
        
        return $widths;
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7E7E7']
                ]
            ]
        ];
    }
    
    public function title(): string
    {
        return 'Trend Analysis';
    }
}