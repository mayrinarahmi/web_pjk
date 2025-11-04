<div class="dashboard-wrapper" wire:init="loadDashboardData">
    <!-- Dashboard Title and Year Selector -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Dashboard Penerimaan Pajak Daerah</h4>
        <div class="d-flex align-items-center">
            <label class="text-muted me-2">Tahun Anggaran:</label>
            <select class="form-select form-select-sm d-inline-block w-auto" 
                    wire:model.lazy="selectedTahunAnggaran"
                    wire:change="loadDashboardData"
                    wire:key="tahun-selector-{{ now() }}">
                <option value="">-- Pilih Tahun --</option>
                @foreach($tahunAnggaranList as $ta)
                    <option value="{{ $ta->id }}" 
                            @if($ta->id == $selectedTahunAnggaran) selected @endif>
                        {{ $ta->tahun }} - {{ strtoupper($ta->jenis_anggaran) }}
                        @if($ta->is_active)
                            <span>(AKTIF)</span>
                        @endif
                    </option>
                @endforeach
            </select>
            
            <!-- Loading indicator -->
            <div wire:loading wire:target="selectedTahunAnggaran, loadDashboardData" class="ms-2">
                <span class="spinner-border spinner-border-sm text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================= -->
    <!-- TAMBAHAN BARU: Info Box dengan Tanggal Data -->
    <!-- ========================================= -->
    <!-- Info Tanggal Data Penerimaan -->
@if($latestPenerimaanDateFormatted)
<div class="alert alert-info py-2 mb-3">
    <div class="d-flex align-items-center">
        <i class="bx bx-calendar fs-5 me-2"></i>
        <div>
            Data penerimaan sampai dengan: 
            <strong class="text-primary">{{ $latestPenerimaanDateFormatted }}</strong>
        </div>
    </div>
</div>
@endif

    <!-- Main Content dengan Loading State -->
    <div wire:loading.class="opacity-50" wire:target="loadDashboardData">
        <!-- Cards Row -->
        <div class="row g-3 mb-4">
            <!-- Total Pendapatan Daerah -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 123, 255, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Total Pendapatan Daerah</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-2 rounded">
                                <i class="fas fa-chart-line text-primary"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 {{ $totalPendapatan['realisasi'] < 0 ? 'text-danger' : 'text-primary' }}">
                            Rp {{ number_format($totalPendapatan['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(0, 123, 255, 0.1);">
                            <div class="progress-bar {{ $totalPendapatan['persentase'] < 0 ? 'bg-danger' : 'bg-primary' }}" 
                                 style="width: {{ min(abs($totalPendapatan['persentase']), 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-3 small">
                            <span class="{{ $totalPendapatan['persentase'] < 0 ? 'text-danger' : 'text-primary' }} fw-bold">
                                {{ number_format($totalPendapatan['persentase'], 2) }}% dari target
                            </span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Pagu Anggaran: Rp {{ number_format($totalPendapatan['pagu'], 0, ',', '.') }}</div>
                            <div>Target: Rp {{ number_format($totalPendapatan['target'], 0, ',', '.') }}</div>
                            <div>Kurang dari Target: Rp {{ number_format($totalPendapatan['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAD -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Pendapatan Asli Daerah (PAD)</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded">
                                <i class="fas fa-money-bill-wave text-success"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 {{ $pad['realisasi'] < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($pad['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(40, 167, 69, 0.1);">
                            <div class="progress-bar {{ $pad['persentase'] < 0 ? 'bg-danger' : 'bg-success' }}" 
                                 style="width: {{ min(abs($pad['persentase']), 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-3 small">
                            <span class="{{ $pad['persentase'] < 0 ? 'text-danger' : 'text-success' }} fw-bold">
                                {{ number_format($pad['persentase'], 2) }}% dari target
                            </span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Pagu Anggaran: Rp {{ number_format($pad['pagu'], 0, ',', '.') }}</div>
                            <div>Target: Rp {{ number_format($pad['target'], 0, ',', '.') }}</div>
                            <div>Kurang dari Target: Rp {{ number_format($pad['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Pendapatan Transfer</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-info bg-opacity-10 p-2 rounded">
                                <i class="fas fa-exchange-alt text-info"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 {{ $transfer['realisasi'] < 0 ? 'text-danger' : 'text-info' }}">
                            Rp {{ number_format($transfer['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(23, 162, 184, 0.1);">
                            <div class="progress-bar {{ $transfer['persentase'] < 0 ? 'bg-danger' : 'bg-info' }}" 
                                 style="width: {{ min(abs($transfer['persentase']), 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-3 small">
                            <span class="{{ $transfer['persentase'] < 0 ? 'text-danger' : 'text-info' }} fw-bold">
                                {{ number_format($transfer['persentase'], 2) }}% dari target
                            </span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Pagu Anggaran: Rp {{ number_format($transfer['pagu'], 0, ',', '.') }}</div>
                            <div>Target: Rp {{ number_format($transfer['target'], 0, ',', '.') }}</div>
                            <div>Kurang dari Target: Rp {{ number_format($transfer['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lain-lain -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Lain-lain Pendapatan Daerah Yang Sah</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-wallet text-warning"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 {{ $lainLain['realisasi'] < 0 ? 'text-danger' : 'text-warning' }}">
                            Rp {{ number_format($lainLain['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(255, 193, 7, 0.1);">
                            <div class="progress-bar {{ $lainLain['persentase'] < 0 ? 'bg-danger' : 'bg-warning' }}" 
                                 style="width: {{ min(abs($lainLain['persentase']), 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-3 small">
                            <span class="{{ $lainLain['persentase'] < 0 ? 'text-danger' : 'text-warning' }} fw-bold">
                                {{ number_format($lainLain['persentase'], 2) }}% dari target
                            </span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Pagu Anggaran: Rp {{ number_format($lainLain['pagu'], 0, ',', '.') }}</div>
                            <div>Target: Rp {{ number_format($lainLain['target'], 0, ',', '.') }}</div>
                            <div>Kurang dari Target: Rp {{ number_format($lainLain['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Tren Penerimaan Bulanan</h5>
                <div id="chart" style="height: 350px;" wire:ignore></div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Realisasi per Kategori</h5>
                <p class="text-muted small">Persentase Target: {{ number_format(($currentQuarter == 1 ? 15 : ($currentQuarter == 2 ? 40 : ($currentQuarter == 3 ? 70 : 100))), 0) }}%</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>KATEGORI</th>
                                <th class="text-end">PAGU ANGGARAN</th>
                                <th class="text-end">TARGET</th>
                                <th class="text-end">REALISASI</th>
                                <th class="text-end">KURANG DR TARGET</th>
                                <th class="text-center">PERSENTASE</th>
                                <th class="text-center" style="width: 150px;">PROGRESS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kategoris as $kategori)
                            <tr>
                                <td>{{ $kategori['nama'] }}</td>
                                <td class="text-end">Rp {{ number_format($kategori['pagu'], 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($kategori['target'], 0, ',', '.') }}</td>
                                <td class="text-end {{ $kategori['realisasi'] < 0 ? 'text-danger fw-bold' : '' }}">
                                    Rp {{ number_format($kategori['realisasi'], 0, ',', '.') }}
                                </td>
                                <td class="text-end {{ $kategori['kurang'] < 0 ? 'text-success fw-bold' : '' }}">
                                    @if($kategori['kurang'] < 0)
                                        <span title="Melebihi target">
                                            +Rp {{ number_format(abs($kategori['kurang']), 0, ',', '.') }}
                                        </span>
                                    @else
                                        Rp {{ number_format($kategori['kurang'], 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($kategori['persentase'] < 0)
                                        <span class="badge bg-danger">
                                            {{ number_format($kategori['persentase'], 1) }}%
                                        </span>
                                    @else
                                        <span class="badge {{ $kategori['persentase'] >= 100 ? 'bg-success' : ($kategori['persentase'] >= 70 ? 'bg-warning' : ($kategori['persentase'] >= 50 ? 'bg-info' : 'bg-danger')) }}">
                                            {{ number_format($kategori['persentase'], 1) }}%
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        @if($kategori['persentase'] < 0)
                                            <div class="progress-bar bg-danger" 
                                                 style="width: {{ min(abs($kategori['persentase']), 100) }}%"
                                                 title="Nilai negatif: {{ number_format($kategori['persentase'], 1) }}%">
                                            </div>
                                        @else
                                            <div class="progress-bar {{ $kategori['persentase'] >= 100 ? 'bg-success' : ($kategori['persentase'] >= 70 ? 'bg-warning' : ($kategori['persentase'] >= 50 ? 'bg-info' : 'bg-primary')) }}" 
                                                 style="width: {{ min($kategori['persentase'], 100) }}%">
                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Tidak ada data untuk tahun anggaran yang dipilih
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="loadDashboardData" 
         class="position-fixed top-0 start-0 w-100 h-100 justify-content-center align-items-center" 
         style="background: rgba(255,255,255,0.8); z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-2" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-muted h5">Memuat data dashboard...</div>
        </div>
    </div>

    <!-- Styles -->
    <style>
    /* Additional styling for better visual */
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
    }

    /* Icon styling */
    .card-body i {
        font-size: 1.2rem;
    }

    /* Progress bar smooth animation */
    .progress-bar {
        transition: width 0.6s ease;
    }

    /* Table row hover effect */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }

    /* Badge styling */
    .badge {
        padding: 0.35em 0.65em;
        font-weight: 500;
    }

    /* Loading state opacity */
    .opacity-50 {
        opacity: 0.5;
        pointer-events: none;
    }
    </style>

    <!-- Scripts -->
    @push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    let dashboardChart = null;

    document.addEventListener('DOMContentLoaded', function () {
        // Initial chart render dengan data dari server
        initChart(@json($chartData));
    });

    // Listen for Livewire updates (Livewire v3)
    document.addEventListener('livewire:initialized', () => {
        // Listen untuk event refreshChart dengan data
        Livewire.on('refreshChart', (event) => {
            // Extract data dari event (Livewire v3 format)
            const chartData = event.chartData || event[0]?.chartData || event;
            
            if (chartData && chartData.categories && chartData.series) {
                initChart(chartData);
            }
        });
    });

    function initChart(chartData) {
        // Validasi data
        if (!chartData || !chartData.categories || !chartData.series) {
            console.error('Invalid chart data:', chartData);
            return;
        }

        // Destroy existing chart if any
        if (dashboardChart) {
            dashboardChart.destroy();
            dashboardChart = null;
        }

        var options = {
            series: chartData.series,
            chart: {
                type: 'bar',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    endingShape: 'rounded'
                },
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                show: true,
                width: 2,
                colors: ['transparent']
            },
            xaxis: {
                categories: chartData.categories,
            },
            yaxis: {
                title: {
                    text: 'Rupiah (Rp)'
                },
                labels: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            fill: {
                opacity: 1
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            colors: ['#28a745', '#17a2b8', '#ffc107'],
            legend: {
                position: 'bottom',
                horizontalAlign: 'center'
            }
        };

        // Create new chart
        dashboardChart = new ApexCharts(document.querySelector("#chart"), options);
        dashboardChart.render();
    }
</script>
@endpush
</div>