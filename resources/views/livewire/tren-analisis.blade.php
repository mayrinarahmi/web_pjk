@extends('layouts.app')

@section('content')
<div wire:init="loadInitialData">
    {{-- Header Card --}}
    <div class="card bg-primary text-white mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="card-title text-white mb-1">Analisis Tren Penerimaan Multi-Tahun</h4>
                    <p class="mb-0 opacity-75">
                        Analisis performa penerimaan periode {{ $startYear }} - {{ $endYear }}
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-light btn-sm" wire:click="exportExcel">
                            <i class="bx bx-spreadsheet me-1"></i> Excel
                        </button>
                        <button type="button" class="btn btn-light btn-sm" wire:click="exportPdf">
                            <i class="bx bx-file me-1"></i> PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Section --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                {{-- Year Range --}}
                <div class="col-md-3">
                    <label class="form-label">Rentang Tahun</label>
                    <select class="form-select" wire:model="yearRange">
                        <option value="2">2 Tahun Terakhir</option>
                        <option value="3">3 Tahun Terakhir</option>
                        <option value="4">4 Tahun Terakhir</option>
                        <option value="5">5 Tahun Terakhir</option>
                    </select>
                </div>

                {{-- Level Selection --}}
                <div class="col-md-3">
                    <label class="form-label">Level Analisis</label>
                    <select class="form-select" wire:model="selectedLevel">
                        @foreach($levelOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Parent Filter --}}
                <div class="col-md-4">
                    <label class="form-label">Filter Kategori</label>
                    <select class="form-select" wire:model="selectedParentKode" 
                            @if(empty($parentOptions)) disabled @endif>
                        <option value="">Semua Kategori</option>
                        @foreach($parentOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Refresh Button --}}
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" wire:click="refreshData" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            <i class="bx bx-refresh me-1"></i> Refresh
                        </span>
                        <span wire:loading>
                            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                            Loading...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row mb-4">
        {{-- Total Growth Card --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="bx bx-trending-up bx-md"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Pertumbuhan Total</small>
                            <div class="d-flex align-items-baseline">
                                <h4 class="mb-0 {{ $this->getGrowthClass($summaryData['total_growth'] ?? 0) }}">
                                    {{ $this->formatPercentage($summaryData['total_growth'] ?? 0) }}
                                </h4>
                                <i class="bx {{ $this->getGrowthIcon($summaryData['total_growth'] ?? 0) }} ms-1"></i>
                            </div>
                            <small class="text-muted">{{ $startYear }} - {{ $endYear }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Performer Card --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="bx bx-trophy bx-md"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Top Performer</small>
                            @if(!empty($summaryData['top_performers'][0]))
                                <h6 class="mb-0">{{ $summaryData['top_performers'][0]['nama'] }}</h6>
                                <small class="text-success">
                                    +{{ $this->formatPercentage($summaryData['top_performers'][0]['avg_growth']) }}
                                </small>
                            @else
                                <h6 class="mb-0">-</h6>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Risk Categories Card --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="bx bx-error-alt bx-md"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Kategori Berisiko</small>
                            <h4 class="mb-0">{{ count($summaryData['declining_categories'] ?? []) }}</h4>
                            <small class="text-muted">Penurunan > 10%</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Average Achievement Card --}}
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <div class="avatar flex-shrink-0 me-3">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="bx bx-bar-chart-alt-2 bx-md"></i>
                            </span>
                        </div>
                        <div>
                            <small class="text-muted d-block">Rata-rata Capaian</small>
                            <h4 class="mb-0">{{ $this->formatPercentage($summaryData['average_achievement'] ?? 0) }}</h4>
                            <small class="text-muted">Tahun {{ $endYear }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row mb-4">
        {{-- Trend Line Chart --}}
        <div class="col-md-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Tren Penerimaan Per Kategori</h5>
                </div>
                <div class="card-body">
                    <div id="trendLineChart" wire:ignore></div>
                </div>
            </div>
        </div>

        {{-- Growth Bar Chart --}}
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pertumbuhan Tahunan</h5>
                </div>
                <div class="card-body">
                    <div id="growthBarChart" wire:ignore></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data Table --}}
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Detail Perbandingan Multi-Tahun</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th rowspan="2" class="align-middle">Kode</th>
                            <th rowspan="2" class="align-middle">Uraian</th>
                            @for($year = $startYear; $year <= $endYear; $year++)
                                <th colspan="3" class="text-center border-start">{{ $year }}</th>
                            @endfor
                            <th rowspan="2" class="align-middle text-center border-start">Rata-rata<br>Growth</th>
                        </tr>
                        <tr>
                            @for($year = $startYear; $year <= $endYear; $year++)
                                <th class="text-end border-start">Realisasi</th>
                                <th class="text-end">Target</th>
                                <th class="text-center">%</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tableData as $row)
                            <tr>
                                <td>{{ $row['kode'] }}</td>
                                <td>{{ $row['nama'] }}</td>
                                @for($year = $startYear; $year <= $endYear; $year++)
                                    @php
                                        $yearData = $row['tahun_' . $year] ?? ['realisasi' => 0, 'target' => 0, 'persentase' => 0];
                                    @endphp
                                    <td class="text-end border-start">{{ $this->formatCurrency($yearData['realisasi']) }}</td>
                                    <td class="text-end">{{ $this->formatCurrency($yearData['target']) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-label-{{ $yearData['persentase'] >= 100 ? 'success' : ($yearData['persentase'] >= 75 ? 'warning' : 'danger') }}">
                                            {{ $this->formatPercentage($yearData['persentase']) }}
                                        </span>
                                    </td>
                                @endfor
                                <td class="text-center border-start">
                                    <span class="fw-semibold {{ $this->getGrowthClass($row['avg_growth']) }}">
                                        {{ $this->formatPercentage($row['avg_growth']) }}
                                        <i class="bx {{ $this->getGrowthIcon($row['avg_growth']) }} ms-1"></i>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + (($endYear - $startYear + 1) * 3) + 1 }}" class="text-center">
                                    Tidak ada data untuk ditampilkan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Loading Overlay --}}
    <div wire:loading.delay.longer wire:target="loadData,refreshData,loadInitialData" class="position-fixed top-0 start-0 w-100 h-100 bg-white bg-opacity-75 d-flex justify-content-center align-items-center" style="z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mb-0">Memuat data analisis...</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wait for Livewire to be ready
        if (typeof Livewire !== 'undefined') {
            Livewire.on('chartDataUpdated', () => {
                setTimeout(() => {
                    initializeTrendChart();
                    initializeGrowthChart();
                }, 100);
            });
        }
        
        // Initialize charts on first load
        setTimeout(() => {
            initializeTrendChart();
            initializeGrowthChart();
        }, 500);
    });
    
    let trendChart, growthChart;
    
    function initializeTrendChart() {
        // Destroy existing chart if any
        if (trendChart) {
            trendChart.destroy();
        }
        
        const chartElement = document.querySelector("#trendLineChart");
        if (!chartElement) return;
        
        const chartData = @json($chartData ?? ['categories' => [], 'series' => []]);
        
        const options = {
            series: chartData.series || [],
            chart: {
                type: 'line',
                height: 350,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                }
            },
            colors: ['#696cff', '#8592a3', '#71dd37', '#ff3e1d', '#ffab00'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            grid: {
                borderColor: '#e7e7e7',
                strokeDashArray: 5
            },
            xaxis: {
                categories: chartData.categories || [],
                labels: {
                    style: {
                        colors: '#697a8d'
                    }
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#697a8d'
                    },
                    formatter: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                            notation: 'compact',
                            compactDisplay: 'short'
                        }).format(value);
                    }
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(value) {
                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            noData: {
                text: 'Tidak ada data untuk ditampilkan',
                align: 'center',
                verticalAlign: 'middle',
                offsetX: 0,
                offsetY: 0,
                style: {
                    color: '#697a8d',
                    fontSize: '14px'
                }
            }
        };
        
        trendChart = new ApexCharts(chartElement, options);
        trendChart.render();
    }
    
    function initializeGrowthChart() {
        // Destroy existing chart if any
        if (growthChart) {
            growthChart.destroy();
        }
        
        const chartElement = document.querySelector("#growthBarChart");
        if (!chartElement) return;
        
        const growthData = @json($growthChartData ?? ['categories' => [], 'series' => []]);
        
        const options = {
            series: growthData.series || [],
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            colors: ['#71dd37', '#ffab00', '#ff3e1d'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded',
                    dataLabels: {
                        position: 'top'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) {
                    return val ? val.toFixed(1) + '%' : '0%';
                },
                offsetY: -20,
                style: {
                    fontSize: '12px',
                    colors: ['#304758']
                }
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: growthData.categories || [],
                labels: {
                    style: {
                        colors: '#697a8d'
                    },
                    rotate: -45,
                    rotateAlways: true,
                    trim: true,
                    maxHeight: 80
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#697a8d'
                    },
                    formatter: function(val) {
                        return val ? val.toFixed(0) + '%' : '0%';
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return val ? val.toFixed(2) + '%' : '0%';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left'
            },
            noData: {
                text: 'Tidak ada data untuk ditampilkan',
                align: 'center',
                verticalAlign: 'middle',
                offsetX: 0,
                offsetY: 0,
                style: {
                    color: '#697a8d',
                    fontSize: '14px'
                }
            }
        };
        
        growthChart = new ApexCharts(chartElement, options);
        growthChart.render();
    }
</script>
@endpush

@push('styles')
<style>
    .table th {
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .bg-opacity-75 {
        background-color: rgba(255, 255, 255, 0.75) !important;
    }
    
    .apexcharts-menu-item:hover {
        background-color: #f3f4f6;
    }
</style>
@endpush