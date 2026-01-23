<div class="dashboard-wrapper" wire:init="loadDashboardData">
    <!-- Dashboard Header -->
    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h3 class="dashboard-title mb-1">Dashboard Penerimaan Pajak Daerah</h3>
                <p class="text-muted mb-0">
                    <i class="bx bx-building-house me-1"></i> {{ $userInfo }}
                </p>
            </div>
            <div class="col-lg-6">
                <div class="d-flex justify-content-lg-end align-items-center gap-3">
                    <!-- Year Selector -->
                    <div class="d-flex align-items-center">
                        <label class="text-muted me-2 small">Tahun Anggaran:</label>
                        <select class="form-select form-select-sm shadow-sm" 
                                wire:model.live="selectedTahunAnggaran"
                                style="min-width: 200px;">
                            <option value="">-- Pilih Tahun --</option>
                            @foreach($tahunAnggaranList as $ta)
                                <option value="{{ $ta->id }}">
                                    {{ $ta->tahun }} - {{ strtoupper($ta->jenis_anggaran) }}
                                    @if($ta->is_active)
                                        (AKTIF)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Refresh Button -->
                    <button wire:click="refreshData" 
                            class="btn btn-sm btn-outline-primary"
                            title="Refresh Data">
                        <i class="bx bx-refresh"></i>
                    </button>
                    
                    <!-- Loading indicator -->
                    <div wire:loading wire:target="selectedTahunAnggaran, loadDashboardData, refreshData">
                        <span class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert/Info Boxes -->
    <div class="row mb-3">
        <div class="col-12">
            @if($latestPenerimaanDateFormatted)
            <div class="alert alert-info py-2 mb-3 shadow-sm border-0">
                <div class="d-flex align-items-center">
                    <div class="alert-icon bg-info bg-opacity-10 p-2 rounded-circle me-3">
                        <i class="bx bx-calendar text-info fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <small class="text-muted d-block">Data penerimaan terakhir:</small>
                        <strong class="text-info">{{ $latestPenerimaanDateFormatted }}</strong>
                    </div>
                    <div class="d-flex gap-4 text-center">
                        <div>
                            <small class="text-muted d-block">Total Transaksi</small>
                            <strong class="text-info">{{ number_format($totalTransaksi, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">SKPD Input</small>
                            <strong class="text-info">{{ number_format($totalSkpd, 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <small class="text-muted d-block">Capaian Total</small>
                            <strong class="text-info">{{ number_format($persentaseCapaian, 1) }}%</strong>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-warning py-2 mb-3 shadow-sm border-0">
                <div class="d-flex align-items-center">
                    <div class="alert-icon bg-warning bg-opacity-10 p-2 rounded-circle me-3">
                        <i class="bx bx-error text-warning fs-5"></i>
                    </div>
                    <div>
                        <strong>Belum ada data penerimaan</strong>
                        <small class="d-block text-muted">Silakan input data penerimaan terlebih dahulu</small>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Main Content dengan Loading State -->
    <div wire:loading.class="opacity-50" wire:target="loadDashboardData">
        <!-- Cards Row - TANPA PAGU ANGGARAN -->
        <div class="row g-3 mb-4">
            <!-- Total Pendapatan Daerah -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-card" style="background: linear-gradient(135deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 123, 255, 0.05) 100%);">
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
                        
                        <h3 class="mb-3 text-primary">
                            Rp {{ number_format($totalPendapatan['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(0, 123, 255, 0.1);">
                            <div class="progress-bar bg-primary" 
                                 style="width: {{ min($totalPendapatan['persentase'], 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-2 small">
                            <span class="text-primary fw-bold">
                                {{ number_format($totalPendapatan['persentase'], 2) }}%
                            </span>
                            <span class="text-muted">dari target</span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Target: Rp {{ number_format($totalPendapatan['target'], 0, ',', '.') }}</div>
                            <div>Kurang: Rp {{ number_format($totalPendapatan['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAD -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-card" style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(40, 167, 69, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Pendapatan Asli Daerah</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded">
                                <i class="fas fa-money-bill-wave text-success"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 text-success">
                            Rp {{ number_format($pad['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(40, 167, 69, 0.1);">
                            <div class="progress-bar bg-success" 
                                 style="width: {{ min($pad['persentase'], 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-2 small">
                            <span class="text-success fw-bold">
                                {{ number_format($pad['persentase'], 2) }}%
                            </span>
                            <span class="text-muted">dari target</span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Target: Rp {{ number_format($pad['target'], 0, ',', '.') }}</div>
                            <div>Kurang: Rp {{ number_format($pad['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transfer -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-card" style="background: linear-gradient(135deg, rgba(23, 162, 184, 0.1) 0%, rgba(23, 162, 184, 0.05) 100%);">
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
                        
                        <h3 class="mb-3 text-info">
                            Rp {{ number_format($transfer['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(23, 162, 184, 0.1);">
                            <div class="progress-bar bg-info" 
                                 style="width: {{ min($transfer['persentase'], 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-2 small">
                            <span class="text-info fw-bold">
                                {{ number_format($transfer['persentase'], 2) }}%
                            </span>
                            <span class="text-muted">dari target</span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Target: Rp {{ number_format($transfer['target'], 0, ',', '.') }}</div>
                            <div>Kurang: Rp {{ number_format($transfer['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lain-lain -->
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 border-0 shadow-sm hover-card" style="background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="text-muted mb-1">Lain-lain Pendapatan</h6>
                                <p class="text-muted small mb-0">Realisasi vs Target</p>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded">
                                <i class="fas fa-wallet text-warning"></i>
                            </div>
                        </div>
                        
                        <h3 class="mb-3 text-warning">
                            Rp {{ number_format($lainLain['realisasi'], 0, ',', '.') }}
                        </h3>
                        
                        <div class="progress mb-2" style="height: 8px; background-color: rgba(255, 193, 7, 0.1);">
                            <div class="progress-bar bg-warning" 
                                 style="width: {{ min($lainLain['persentase'], 100) }}%">
                            </div>
                        </div>
                        
                        <p class="mb-2 small">
                            <span class="text-warning fw-bold">
                                {{ number_format($lainLain['persentase'], 2) }}%
                            </span>
                            <span class="text-muted">dari target</span>
                        </p>
                        
                        <div class="small text-muted">
                            <div>Target: Rp {{ number_format($lainLain['target'], 0, ',', '.') }}</div>
                            <div>Kurang: Rp {{ number_format($lainLain['kurang'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

       <!-- Chart Section -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <h5 class="card-title mb-4">Tren Penerimaan Bulanan</h5>
        <div id="chart" style="height: 350px;" wire:ignore></div>
    </div>
</div>

<!-- TAMBAHAN: Chart Breakdown PAD -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="card-title mb-1">Breakdown Pendapatan Asli Daerah (PAD)</h5>
                <p class="text-muted small mb-0">Rincian per Kategori PAD</p>
            </div>
            <div class="badge bg-success bg-opacity-10 text-success">
                Level 3 - Detail PAD
            </div>
        </div>
        <div id="chartPadBreakdown" style="height: 380px;" wire:ignore></div>
    </div>
</div>


        <!-- Table Section - TANPA KOLOM PAGU -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Realisasi per Kategori</h5>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>KATEGORI</th>
                                <th class="text-end">TARGET</th>
                                <th class="text-end">REALISASI</th>
                                <th class="text-end">KURANG DARI TARGET</th>
                                <th class="text-center">PERSENTASE</th>
                                <th class="text-center" style="width: 150px;">PROGRESS</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kategoris as $kategori)
                            <tr>
                                <td class="fw-semibold">{{ $kategori['nama'] }}</td>
                                <td class="text-end">Rp {{ number_format($kategori['target'], 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($kategori['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if($kategori['kurang'] < 0)
                                        <span class="text-success" title="Melebihi target">
                                            +Rp {{ number_format(abs($kategori['kurang']), 0, ',', '.') }}
                                        </span>
                                    @else
                                        Rp {{ number_format($kategori['kurang'], 0, ',', '.') }}
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $kategori['persentase'] >= 100 ? 'bg-success' : ($kategori['persentase'] >= 70 ? 'bg-warning' : ($kategori['persentase'] >= 50 ? 'bg-info' : 'bg-danger')) }}">
                                        {{ number_format($kategori['persentase'], 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar {{ $kategori['persentase'] >= 100 ? 'bg-success' : ($kategori['persentase'] >= 70 ? 'bg-warning' : ($kategori['persentase'] >= 50 ? 'bg-info' : 'bg-primary')) }}" 
                                             style="width: {{ min($kategori['persentase'], 100) }}%">
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Tidak ada data untuk tahun anggaran yang dipilih
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- LANJUTAN DARI PART 1 -->
        
        <!-- TAMBAHAN: Data Per SKPD (Hanya untuk Super Admin/Kepala Badan) -->
        @if($canSeeAllSkpd && count($dataPerSkpd) > 0)
        
        <!-- Top & Bottom Performers -->
        <div class="row mb-4">
            <!-- Top 5 SKPD -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-success bg-opacity-10 border-0">
                        <h6 class="mb-0 text-success">
                            <i class="fas fa-trophy me-2"></i>Top 5 SKPD - Capaian Tertinggi
                        </h6>
                    </div>
                    <div class="card-body">
                        @forelse($topSkpd as $index => $skpd)
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-success me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">{{ $skpd['nama'] }}</div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-success" 
                                         style="width: {{ min($skpd['persentase'], 100) }}%">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <div class="fw-bold text-success">{{ number_format($skpd['persentase'], 1) }}%</div>
                                <div class="text-muted small">Rp {{ number_format($skpd['realisasi'] / 1000000, 0, ',', '.') }} Jt</div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center mb-0">Belum ada data realisasi</p>
                        @endforelse
                    </div>
                </div>
            </div>
            
            <!-- Bottom 5 SKPD -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-warning bg-opacity-10 border-0">
                        <h6 class="mb-0 text-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Bottom 5 SKPD - Perlu Perhatian
                        </h6>
                    </div>
                    <div class="card-body">
                        @forelse($bottomSkpd as $index => $skpd)
                        <div class="d-flex align-items-center mb-3">
                            <div class="badge bg-warning me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                {{ $index + 1 }}
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">{{ $skpd['nama'] }}</div>
                                <div class="progress mt-1" style="height: 5px;">
                                    <div class="progress-bar bg-warning" 
                                         style="width: {{ min($skpd['persentase'], 100) }}%">
                                    </div>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <div class="fw-bold text-warning">{{ number_format($skpd['persentase'], 1) }}%</div>
                                <div class="text-muted small">Rp {{ number_format($skpd['realisasi'] / 1000000, 0, ',', '.') }} Jt</div>
                            </div>
                        </div>
                        @empty
                        <p class="text-muted text-center mb-0">Belum ada data realisasi</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabel Realisasi Per SKPD -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="card-title mb-0">Realisasi Penerimaan per SKPD</h5>
                    <small class="text-muted">Total: {{ count($dataPerSkpd) }} SKPD</small>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover" id="tabelSkpd">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Kode</th>
                                <th width="35%">Nama SKPD</th>
                                <th class="text-end" width="15%">Target</th>
                                <th class="text-end" width="15%">Realisasi</th>
                                <th class="text-center" width="10%">%</th>
                                <th class="text-center" width="10%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataPerSkpd as $index => $skpd)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $skpd['kode'] }}</td>
                                <td class="fw-semibold">{{ $skpd['nama'] }}</td>
                                <td class="text-end">Rp {{ number_format($skpd['target'], 0, ',', '.') }}</td>
                                <td class="text-end">Rp {{ number_format($skpd['realisasi'], 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <span class="badge {{ $skpd['persentase'] >= 100 ? 'bg-success' : ($skpd['persentase'] >= 70 ? 'bg-warning' : ($skpd['persentase'] >= 50 ? 'bg-info' : 'bg-danger')) }}">
                                        {{ number_format($skpd['persentase'], 1) }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($skpd['persentase'] >= 100)
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Tercapai
                                        </span>
                                    @elseif($skpd['persentase'] >= 70)
                                        <span class="badge bg-warning">
                                            <i class="fas fa-clock me-1"></i>Proses
                                        </span>
                                    @else
                                        <span class="badge bg-danger">
                                            <i class="fas fa-exclamation-circle me-1"></i>Rendah
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Tidak ada data SKPD dengan pagu anggaran
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
        <!-- END Data Per SKPD -->
        
    </div>
    <!-- End Main Content -->

    <!-- Loading Overlay -->
    <div wire:loading.flex wire:target="loadDashboardData" 
         class="position-fixed top-0 start-0 w-100 h-100 justify-content-center align-items-center" 
         style="background: rgba(255,255,255,0.9); z-index: 9999;">
        <div class="text-center">
            <div class="spinner-border text-primary mb-2" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="text-muted h5">Memuat data dashboard...</div>
        </div>
    </div>

    <!-- Styles -->
    <style>
    /* Card hover effect */
    .hover-card {
        transition: all 0.3s ease;
    }
    
    .hover-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.15) !important;
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
    
    /* Alert icon */
    .alert-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Table header */
    .table-light th {
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }
    </style>

    <!-- Scripts -->
   @push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    let dashboardChart = null;
    let padBreakdownChart = null; // TAMBAHAN: Variable untuk chart breakdown PAD

    document.addEventListener('DOMContentLoaded', function () {
        // Initial chart render dengan data dari server
        initChart(@json($chartData));
        
        // TAMBAHAN: Initial chart PAD breakdown
        initChartPadBreakdown(@json($chartDataPadBreakdown));
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
        
        // TAMBAHAN: Listen untuk event refreshChartPadBreakdown
        Livewire.on('refreshChartPadBreakdown', (event) => {
            const chartData = event.chartData || event[0]?.chartData || event;
            
            if (chartData && chartData.categories && chartData.series) {
                initChartPadBreakdown(chartData);
            }
        });
        
        // Listen untuk data refreshed event
        Livewire.on('dataRefreshed', () => {
            console.log('Dashboard data refreshed');
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
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: false,
                        zoomin: false,
                        zoomout: false,
                        pan: false,
                        reset: false
                    }
                }
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '65%',
                    endingShape: 'rounded',
                    dataLabels: {
                        position: 'top'
                    }
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
                        if (val >= 1000000000) {
                            return "Rp " + (val / 1000000000).toFixed(1) + " M";
                        } else if (val >= 1000000) {
                            return "Rp " + (val / 1000000).toFixed(0) + " Jt";
                        } else {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                        }
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

    // TAMBAHAN: Function untuk initialize chart breakdown PAD
    function initChartPadBreakdown(chartData) {
        // Validasi data
        if (!chartData || !chartData.categories || !chartData.series) {
            console.error('Invalid PAD breakdown chart data:', chartData);
            return;
        }

        // Destroy existing chart if any
        if (padBreakdownChart) {
            padBreakdownChart.destroy();
            padBreakdownChart = null;
        }

        var options = {
            series: chartData.series,
            chart: {
                type: 'area',
                height: 380,
                stacked: false,
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: false,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                zoom: {
                    enabled: true,
                    type: 'x'
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            xaxis: {
                categories: chartData.categories,
                labels: {
                    style: {
                        fontSize: '12px'
                    }
                }
            },
            yaxis: {
                title: {
                    text: 'Rupiah (Rp)',
                    style: {
                        fontSize: '12px'
                    }
                },
                labels: {
                    formatter: function (val) {
                        if (val >= 1000000000) {
                            return "Rp " + (val / 1000000000).toFixed(1) + " M";
                        } else if (val >= 1000000) {
                            return "Rp " + (val / 1000000).toFixed(0) + " Jt";
                        } else if (val >= 1000) {
                            return "Rp " + (val / 1000).toFixed(0) + " Rb";
                        } else {
                            return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                        }
                    },
                    style: {
                        fontSize: '11px'
                    }
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    opacityFrom: 0.6,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (val) {
                        return "Rp " + new Intl.NumberFormat('id-ID').format(val);
                    }
                }
            },
            colors: ['#007bff', '#6f42c1', '#fd7e14', '#20c997'],
            legend: {
                position: 'bottom',
                horizontalAlign: 'center',
                fontSize: '13px',
                markers: {
                    width: 12,
                    height: 12,
                    radius: 3
                },
                itemMargin: {
                    horizontal: 10,
                    vertical: 5
                }
            },
            grid: {
                borderColor: '#e7e7e7',
                strokeDashArray: 3
            }
        };

        // Create new chart
        padBreakdownChart = new ApexCharts(document.querySelector("#chartPadBreakdown"), options);
        padBreakdownChart.render();
    }
</script>
@endpush
</div>