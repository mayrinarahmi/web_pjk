<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Services\TrendAnalysisService;
use App\Models\KodeRekening;
use App\Models\TahunAnggaran;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TrendAnalysisExport;

class TrenAnalisis extends Component
{
    // Filters
    public $startYear;
    public $endYear;
    public $selectedLevel = 2;
    public $selectedParentKode = null;
    public $yearRange = 3; // Default 3 years
    
    // Data - Initialize as arrays to avoid Livewire issues
    public $summaryData = [
        'total_growth' => 0,
        'top_performers' => [],
        'declining_categories' => [],
        'average_achievement' => 0,
        'total_current_year' => 0,
        'total_start_year' => 0
    ];
    
    public $chartData = [
        'categories' => [],
        'series' => []
    ];
    
    public $growthChartData = [
        'categories' => [],
        'series' => []
    ];
    
    public $tableData = [];
    public $levelOptions = [];
    public $parentOptions = [];
    
    // Loading states
    public $isLoading = false;
    public $isInitialized = false;
    
    // Service
    protected $trendService;
    
    public function boot(TrendAnalysisService $trendService)
    {
        $this->trendService = $trendService;
    }
    
    public function mount()
    {
        // Initialize default data structure
        $this->initializeData();
        
        // Set default years
        $currentYear = date('Y');
        $this->endYear = $currentYear;
        $this->startYear = $currentYear - $this->yearRange + 1;
        
        // Load level options
        $this->levelOptions = [
            ['value' => 2, 'label' => 'Level 2 - Kategori Utama'],
            ['value' => 3, 'label' => 'Level 3 - Sub Kategori'],
            ['value' => 4, 'label' => 'Level 4 - Jenis'],
            ['value' => 5, 'label' => 'Level 5 - Detail'],
        ];
        
        // Set loading to false initially
        $this->isLoading = false;
        
        // Load initial data directly in mount for simplicity
        $this->loadData();
    }
    
    private function initializeData()
    {
        $this->summaryData = [
            'total_growth' => 0,
            'top_performers' => [],
            'declining_categories' => [],
            'average_achievement' => 0,
            'total_current_year' => 0,
            'total_start_year' => 0
        ];
        
        $this->chartData = [
            'categories' => [],
            'series' => []
        ];
        
        $this->growthChartData = [
            'categories' => [],
            'series' => []
        ];
        
        $this->tableData = [];
        $this->parentOptions = [];
    }
    
    public function updatedYearRange()
    {
        $currentYear = date('Y');
        $this->endYear = $currentYear;
        $this->startYear = $currentYear - $this->yearRange + 1;
        $this->loadData();
    }
    
    public function updatedSelectedLevel()
    {
        $this->selectedParentKode = null;
        $this->loadParentOptions();
        $this->loadData();
    }
    
    public function updatedSelectedParentKode()
    {
        $this->loadData();
    }
    
    public function loadInitialData()
    {
        $this->loadData();
    }

    public function refreshData()
    {
        $this->loadData();
    }
    
    private function loadData()
    {
        $this->isLoading = true;
        
        try {
            // Load parent options based on selected level
            $this->loadParentOptions();
            
            // Test with simple data first to ensure component works
            $testMode = false; // Set to true for testing
            
            if ($testMode) {
                // Test data
                $this->summaryData = [
                    'total_growth' => 15.5,
                    'top_performers' => [
                        ['nama' => 'Pajak Reklame', 'kode' => '4.1.01.09', 'avg_growth' => 25.5]
                    ],
                    'declining_categories' => [],
                    'average_achievement' => 85.5,
                    'total_current_year' => 1000000000,
                    'total_start_year' => 850000000
                ];
                
                $this->chartData = [
                    'categories' => ['2023', '2024', '2025'],
                    'series' => [
                        ['name' => 'PAD', 'data' => [100000000, 150000000, 200000000]]
                    ]
                ];
                
                $this->growthChartData = [
                    'categories' => ['PAD', 'Transfer', 'Lain-lain'],
                    'series' => [
                        ['name' => 'Growth 2024', 'data' => [15, -5, 10]]
                    ]
                ];
                
                $this->tableData = [
                    [
                        'kode' => '4.1',
                        'nama' => 'PENDAPATAN ASLI DAERAH',
                        'tahun_2023' => ['realisasi' => 100000000, 'target' => 120000000, 'persentase' => 83.33, 'growth' => 0],
                        'tahun_2024' => ['realisasi' => 150000000, 'target' => 160000000, 'persentase' => 93.75, 'growth' => 50],
                        'tahun_2025' => ['realisasi' => 200000000, 'target' => 200000000, 'persentase' => 100, 'growth' => 33.33],
                        'avg_growth' => 41.67
                    ]
                ];
            } else {
                // Real data loading
                \Log::info('Loading trend analysis data', [
                    'startYear' => $this->startYear,
                    'endYear' => $this->endYear,
                    'level' => $this->selectedLevel,
                    'parentKode' => $this->selectedParentKode
                ]);

                // Step 1: Get growth analysis (shared source for summary, growth chart, table)
                $growthData = $this->trendService->getGrowthAnalysis(
                    $this->selectedLevel,
                    $this->selectedParentKode,
                    $this->startYear,
                    $this->endYear
                );

                // Step 2: Build summaryData from growthData
                $topPerformers = [];
                $decliningCategories = [];
                $totalCurrentYear = 0;
                $totalStartYear = 0;

                foreach ($growthData as $item) {
                    $totalCurrentYear += $item['last_year_total'];
                    $totalStartYear += $item['first_year_total'];

                    if ($item['growth_percentage'] > 0) {
                        $topPerformers[] = [
                            'nama' => $item['nama'],
                            'avg_growth' => $item['growth_percentage'],
                        ];
                    }
                    if ($item['growth_percentage'] < -10) {
                        $decliningCategories[] = $item;
                    }
                }

                // growthData is already sorted desc by growth_percentage
                $topPerformers = array_slice($topPerformers, 0, 3);

                $totalGrowth = $totalStartYear > 0
                    ? round((($totalCurrentYear - $totalStartYear) / $totalStartYear) * 100, 2)
                    : 0;

                $this->summaryData = [
                    'total_growth' => $totalGrowth,
                    'top_performers' => $topPerformers,
                    'declining_categories' => $decliningCategories,
                    'average_achievement' => 0,
                    'total_current_year' => $totalCurrentYear,
                    'total_start_year' => $totalStartYear,
                ];

                // Step 3: Trend line chart — yearly data per category
                $this->chartData = $this->trendService->getYearlyChartData(
                    $this->selectedLevel,
                    $this->selectedParentKode,
                    $this->yearRange,
                    10
                );

                // Step 4: Growth bar chart — top 10 items by growth percentage
                $topGrowthItems = array_slice($growthData, 0, 10);
                $this->growthChartData = [
                    'categories' => array_map(
                        fn($item) => mb_substr($item['nama'], 0, 25),
                        $topGrowthItems
                    ),
                    'series' => [
                        [
                            'name' => 'Growth % (' . $this->startYear . '-' . $this->endYear . ')',
                            'data' => array_map(fn($item) => $item['growth_percentage'], $topGrowthItems),
                        ]
                    ],
                ];

                // Step 5: Build table rows from growthData
                $ids = array_column($growthData, 'kode_rekening_id');
                $kodeMap = !empty($ids)
                    ? KodeRekening::whereIn('id', $ids)->pluck('kode', 'id')->toArray()
                    : [];

                $tableRows = [];
                foreach ($growthData as $item) {
                    $row = [
                        'kode'       => $kodeMap[$item['kode_rekening_id']] ?? '-',
                        'nama'       => $item['nama'],
                        'avg_growth' => $item['growth_percentage'],
                    ];
                    for ($y = $this->startYear; $y <= $this->endYear; $y++) {
                        $realisasi = $item['yearly_data'][$y] ?? 0;
                        $row['tahun_' . $y] = [
                            'realisasi'  => $realisasi,
                            'target'     => 0,
                            'persentase' => 0,
                        ];
                    }
                    $tableRows[] = $row;
                }
                $this->tableData = $tableRows;
            }
            
            // Dispatch event to update charts
            $this->dispatch('chartDataUpdated');
            
            \Log::info('Trend analysis data loaded successfully');
            
        } catch (\Exception $e) {
            \Log::error('Failed to load trend analysis data: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            session()->flash('error', 'Gagal memuat data: ' . $e->getMessage());
            
            // Reset to default values on error
            $this->initializeData();
        } finally {
            // Always set loading to false
            $this->isLoading = false;
        }
    }
    
    private function getDefaultSummaryData()
    {
        return [
            'total_growth' => 0,
            'top_performers' => [],
            'declining_categories' => [],
            'average_achievement' => 0,
            'total_current_year' => 0,
            'total_start_year' => 0
        ];
    }
    
    private function loadParentOptions()
    {
        $this->parentOptions = [];
        
        try {
            if ($this->selectedLevel > 2) {
                $parentLevel = $this->selectedLevel - 1;
                $parents = KodeRekening::where('level', $parentLevel)
                    ->where('is_active', true)
                    ->orderBy('kode', 'asc')
                    ->get();
                    
                foreach ($parents as $parent) {
                    $this->parentOptions[] = [
                        'value' => $parent->kode,
                        'label' => $parent->kode . ' - ' . $parent->nama
                    ];
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to load parent options: ' . $e->getMessage());
        }
    }
    
    public function exportExcel()
    {
        try {
            $growthData = $this->trendService->getGrowthAnalysis(
                $this->selectedLevel,
                $this->selectedParentKode,
                $this->startYear,
                $this->endYear
            );

            // Build headers
            $headers = ['Kode', 'Nama'];
            for ($y = $this->startYear; $y <= $this->endYear; $y++) {
                $headers[] = 'Realisasi ' . $y;
            }
            $headers[] = 'Growth (%)';

            // Get kode strings
            $ids = array_column($growthData, 'kode_rekening_id');
            $kodeMap = !empty($ids)
                ? KodeRekening::whereIn('id', $ids)->pluck('kode', 'id')->toArray()
                : [];

            // Build rows
            $rows = [];
            foreach ($growthData as $item) {
                $row = [
                    $kodeMap[$item['kode_rekening_id']] ?? '-',
                    $item['nama'],
                ];
                for ($y = $this->startYear; $y <= $this->endYear; $y++) {
                    $row[] = $item['yearly_data'][$y] ?? 0;
                }
                $row[] = $item['growth_percentage'];
                $rows[] = $row;
            }

            $filename = 'analisis-tren-' . $this->startYear . '-' . $this->endYear . '.xlsx';

            return Excel::download(
                new TrendAnalysisExport(['headers' => $headers, 'data' => $rows]),
                $filename
            );
        } catch (\Exception $e) {
            session()->flash('error', 'Gagal export Excel: ' . $e->getMessage());
        }
    }
    
    public function exportPdf()
    {
        session()->flash('info', 'Export PDF dalam pengembangan');
    }
    
    public function render()
    {
        return view('livewire.tren-analisis')
            ->extends('layouts.app')
            ->section('content');
    }
    
    // Helper methods for view
    public function getGrowthClass($growth)
    {
        if ($growth > 10) return 'text-success';
        if ($growth < -10) return 'text-danger';
        return 'text-warning';
    }
    
    public function getGrowthIcon($growth)
    {
        if ($growth > 0) return 'bx-trending-up';
        if ($growth < 0) return 'bx-trending-down';
        return 'bx-minus';
    }
    
    public function formatCurrency($value)
    {
        return 'Rp ' . number_format($value, 0, ',', '.');
    }
    
    public function formatPercentage($value)
    {
        return number_format($value, 2, ',', '.') . '%';
    }
}