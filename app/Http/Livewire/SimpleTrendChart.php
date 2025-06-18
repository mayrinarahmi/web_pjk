<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimpleTrendChart extends Component
{
    public $yearRange = 3;
    public $searchTerm = '';
    public $selectedCategoryId = null;
    public $selectedCategoryName = 'Overview - Semua Kategori';
    public $searchResults = [];
    public $chartData = [];
    
    // Remove listeners, not needed for direct wire:click
    
    public function mount()
    {
        $this->loadChartData();
    }
    
    // Method untuk update year range
    public function updateYearRange($value)
    {
        Log::info('updateYearRange called with: ' . $value);
        $this->yearRange = (int)$value;
        $this->loadChartData();
    }
    
    // Method untuk handle search
    public function updatedSearchTerm()
    {
        Log::info('Search term updated: ' . $this->searchTerm);
        
        if (strlen($this->searchTerm) < 2) {
            $this->searchResults = [];
            return;
        }
        
        $search = strtolower($this->searchTerm);
        
        // Search dengan query yang lebih luas
        $query = "
            SELECT id, kode, nama, level 
            FROM kode_rekening 
            WHERE is_active = 1 
            AND (LOWER(nama) LIKE ? OR LOWER(kode) LIKE ?)
            AND level IN (3, 4, 5)
            ORDER BY kode 
            LIMIT 20
        ";
        
        $results = DB::select($query, ['%'.$search.'%', '%'.$search.'%']);
        
        Log::info('Search results count: ' . count($results));
        
        $this->searchResults = $results;
    }
    
    // Method untuk select category
    public function selectCategory($id, $nama)
    {
        Log::info('Category selected', ['id' => $id, 'nama' => $nama]);
        
        $this->selectedCategoryId = $id;
        $this->selectedCategoryName = $nama;
        $this->searchTerm = '';
        $this->searchResults = [];
        $this->loadChartData();
    }
    
    // Reset to overview
    public function resetToOverview()
    {
        $this->selectedCategoryId = null;
        $this->selectedCategoryName = 'Overview - Semua Kategori';
        $this->searchTerm = '';
        $this->searchResults = [];
        $this->loadChartData();
    }
    
    private function loadChartData()
    {
        $currentYear = (int)date('Y');
        $startYear = $currentYear - $this->yearRange + 1;
        
        Log::info('Loading chart data', [
            'yearRange' => $this->yearRange,
            'startYear' => $startYear,
            'endYear' => $currentYear,
            'categoryId' => $this->selectedCategoryId
        ]);
        
        try {
            if ($this->selectedCategoryId === null) {
                $data = $this->getOverviewData($startYear, $currentYear);
            } else {
                $data = $this->getCategoryData($startYear, $currentYear);
            }
            
            $this->processChartData($data, $startYear, $currentYear);
            
            // Emit event untuk update chart
            $this->dispatch('chartDataUpdated', chartData: $this->chartData);
            
        } catch (\Exception $e) {
            Log::error('Error loading data: ' . $e->getMessage());
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }
    
    private function getOverviewData($startYear, $endYear)
    {
        $query = "
            SELECT 
                YEAR(p.tanggal) as tahun,
                CASE 
                    WHEN k.kode LIKE '4.1%' THEN 'PAD'
                    WHEN k.kode LIKE '4.2%' THEN 'Transfer'
                    WHEN k.kode LIKE '4.3%' THEN 'Lain-lain'
                END as nama,
                SUM(p.jumlah) as total
            FROM penerimaan p
            JOIN kode_rekening k ON p.kode_rekening_id = k.id
            WHERE YEAR(p.tanggal) BETWEEN ? AND ?
            AND k.is_active = 1
            AND k.kode LIKE '4%'
            GROUP BY YEAR(p.tanggal), 
                CASE 
                    WHEN k.kode LIKE '4.1%' THEN 'PAD'
                    WHEN k.kode LIKE '4.2%' THEN 'Transfer'
                    WHEN k.kode LIKE '4.3%' THEN 'Lain-lain'
                END
            ORDER BY tahun, nama
        ";
        
        return DB::select($query, [$startYear, $endYear]);
    }
    
    private function getCategoryData($startYear, $endYear)
    {
        $category = DB::table('kode_rekening')
            ->where('id', $this->selectedCategoryId)
            ->first();
            
        if (!$category) {
            return [];
        }
        
        $query = "
            SELECT 
                YEAR(p.tanggal) as tahun,
                ? as nama,
                SUM(p.jumlah) as total
            FROM penerimaan p
            JOIN kode_rekening k ON p.kode_rekening_id = k.id
            WHERE YEAR(p.tanggal) BETWEEN ? AND ?
            AND (k.id = ? OR k.kode LIKE ?)
            AND k.is_active = 1
            GROUP BY YEAR(p.tanggal)
            ORDER BY tahun
        ";
        
        return DB::select($query, [
            $category->nama,
            $startYear, 
            $endYear, 
            $this->selectedCategoryId,
            $category->kode . '.%'
        ]);
    }
    
    private function processChartData($data, $startYear, $endYear)
    {
        $categories = [];
        for ($year = $startYear; $year <= $endYear; $year++) {
            $categories[] = (string)$year;
        }
        
        $grouped = collect($data)->groupBy('nama');
        $series = [];
        
        foreach ($grouped as $nama => $items) {
            $values = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $yearData = $items->firstWhere('tahun', $year);
                $values[] = $yearData ? (float)$yearData->total : 0;
            }
            
            $series[] = [
                'name' => $nama,
                'data' => $values
            ];
        }
        
        $this->chartData = [
            'categories' => $categories,
            'series' => $series
        ];
    }
    
    public function render()
    {
        return view('livewire.simple-trend-chart')
            ->extends('layouts.app')
            ->section('content');
    }
}