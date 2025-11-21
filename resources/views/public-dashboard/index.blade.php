@extends('layouts.public')

@section('title', 'Dashboard Pendapatan Daerah ')

@section('content')
<div x-data="publicDashboard()" x-init="init()">
    
    <!-- Hero Section -->
    <div class="row mb-4" data-aos="fade-down">
        <div class="col-12">
            <div class="card-modern p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="text-gradient mb-2" style="font-size: 2rem; font-weight: 700;">
                            Dashboard Pendapatan Daerah
                        </h1>
                        <p class="text-muted mb-3">
                            <i class='bx bx-map me-1'></i>
                            Kota Banjarmasin - Transparansi Pengelolaan Keuangan Daerah
                        </p>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="badge badge-modern bg-primary text-white">
                                <i class='bx bx-calendar me-1'></i>
                                <span x-text="'Tahun ' + selectedYear"></span>
                            </div>
                            <div class="badge badge-modern" style="background: rgba(102, 126, 234, 0.1); color: var(--primary-color);">
                                <i class='bx bx-time me-1'></i>
                                <span x-text="'Update: ' + summary.update_terakhir"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <label class="form-label text-muted small mb-2">Filter Tahun</label>
                        <select class="form-select" x-model="selectedYear" @change="loadData()" style="max-width: 200px; margin-left: auto;">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards - 4 Category Breakdown -->
    <div class="row g-3 mb-4">
        <!-- Card 1: Total Pendapatan Daerah -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="category-card">
                <div class="category-card-header">
                    <div class="category-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class='bx bx-bar-chart-alt-2'></i>
                    </div>
                    <div class="category-card-title">Total Pendapatan Daerah</div>
                    <div class="category-card-subtitle">Realisasi vs Target</div>
                </div>
                <div class="category-card-body">
                    <div class="category-card-amount" x-text="formatCurrencyFull(summary.total?.realisasi || 0)"></div>
                    <div class="progress-modern mt-2" style="height: 8px;">
                        <div class="progress-bar-gradient" 
                             :style="'width: ' + Math.min(summary.total?.persentase || 0, 100) + '%'">
                        </div>
                    </div>
                    <div class="category-card-details mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Target:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.total?.target || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Kurang:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.total?.kurang || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Persentase:</span>
                            <span class="badge" 
                                  :class="(summary.total?.persentase || 0) >= 100 ? 'badge-success' : (summary.total?.persentase || 0) >= 70 ? 'badge-warning' : 'badge-danger'"
                                  x-text="(summary.total?.persentase || 0).toFixed(2) + '%'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 2: Pendapatan Asli Daerah (PAD) -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="category-card">
                <div class="category-card-header">
                    <div class="category-card-icon" style="background: linear-gradient(135deg, #71dd37 0%, #56ca00 100%);">
                        <i class='bx bx-wallet'></i>
                    </div>
                    <div class="category-card-title">Pendapatan Asli Daerah</div>
                    <div class="category-card-subtitle">Realisasi vs Target</div>
                </div>
                <div class="category-card-body">
                    <div class="category-card-amount" x-text="formatCurrencyFull(summary.pad?.realisasi || 0)"></div>
                    <div class="progress-modern mt-2" style="height: 8px;">
                        <div class="progress-bar" 
                             style="background: linear-gradient(135deg, #71dd37 0%, #56ca00 100%);"
                             :style="'width: ' + Math.min(summary.pad?.persentase || 0, 100) + '%'">
                        </div>
                    </div>
                    <div class="category-card-details mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Target:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.pad?.target || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Kurang:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.pad?.kurang || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Persentase:</span>
                            <span class="badge" 
                                  :class="(summary.pad?.persentase || 0) >= 100 ? 'badge-success' : (summary.pad?.persentase || 0) >= 70 ? 'badge-warning' : 'badge-danger'"
                                  x-text="(summary.pad?.persentase || 0).toFixed(2) + '%'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Pendapatan Transfer -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="category-card">
                <div class="category-card-header">
                    <div class="category-card-icon" style="background: linear-gradient(135deg, #03c3ec 0%, #0288d1 100%);">
                        <i class='bx bx-transfer'></i>
                    </div>
                    <div class="category-card-title">Pendapatan Transfer</div>
                    <div class="category-card-subtitle">Realisasi vs Target</div>
                </div>
                <div class="category-card-body">
                    <div class="category-card-amount" x-text="formatCurrencyFull(summary.transfer?.realisasi || 0)"></div>
                    <div class="progress-modern mt-2" style="height: 8px;">
                        <div class="progress-bar" 
                             style="background: linear-gradient(135deg, #03c3ec 0%, #0288d1 100%);"
                             :style="'width: ' + Math.min(summary.transfer?.persentase || 0, 100) + '%'">
                        </div>
                    </div>
                    <div class="category-card-details mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Target:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.transfer?.target || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Kurang:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.transfer?.kurang || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Persentase:</span>
                            <span class="badge" 
                                  :class="(summary.transfer?.persentase || 0) >= 100 ? 'badge-success' : (summary.transfer?.persentase || 0) >= 70 ? 'badge-warning' : 'badge-danger'"
                                  x-text="(summary.transfer?.persentase || 0).toFixed(2) + '%'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Lain-Lain Pendapatan -->
        <div class="col-lg-3 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="category-card">
                <div class="category-card-header">
                    <div class="category-card-icon" style="background: linear-gradient(135deg, #ffab00 0%, #ff6f00 100%);">
                        <i class='bx bx-collection'></i>
                    </div>
                    <div class="category-card-title">Lain-Lain Pendapatan</div>
                    <div class="category-card-subtitle">Realisasi vs Target</div>
                </div>
                <div class="category-card-body">
                    <div class="category-card-amount" x-text="formatCurrencyFull(summary.lain_lain?.realisasi || 0)"></div>
                    <div class="progress-modern mt-2" style="height: 8px;">
                        <div class="progress-bar" 
                             style="background: linear-gradient(135deg, #ffab00 0%, #ff6f00 100%);"
                             :style="'width: ' + Math.min(summary.lain_lain?.persentase || 0, 100) + '%'">
                        </div>
                    </div>
                    <div class="category-card-details mt-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Target:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.lain_lain?.target || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Kurang:</span>
                            <span class="fw-semibold small" x-text="formatCurrencyFull(summary.lain_lain?.kurang || 0)"></span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Persentase:</span>
                            <span class="badge" 
                                  :class="(summary.lain_lain?.persentase || 0) >= 100 ? 'badge-success' : (summary.lain_lain?.persentase || 0) >= 70 ? 'badge-warning' : 'badge-danger'"
                                  x-text="(summary.lain_lain?.persentase || 0).toFixed(2) + '%'">
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Trend Chart -->
    <div class="row mb-4">
        <div class="col-12" data-aos="fade-up" data-aos-delay="500">
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Trend Penerimaan Bulanan</h3>
                        <p class="chart-subtitle mb-0">Breakdown per kategori pendapatan</p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" @click="refreshData()">
                            <i class='bx bx-refresh'></i>
                        </button>
                    </div>
                </div>
                <div id="monthlyTrendChart"></div>
            </div>
        </div>
    </div>

    <!-- Top Categories & Year Comparison -->
    <div class="row g-3 mb-4">
        <!-- Top 5 Categories -->
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="600">
            <div class="card-modern p-4">
                <h5 class="mb-4">
                    <i class='bx bx-chart text-primary me-2'></i>
                    Top 5 Sumber Pendapatan
                </h5>
                <template x-for="(item, index) in topCategories" :key="index">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2" x-text="index + 1" style="width: 24px; height: 24px; display: flex; align-items: center; justify-content: center;"></span>
                                <span class="fw-semibold small" x-text="item.nama"></span>
                            </div>
                            <span class="text-primary fw-bold" x-text="formatCurrencyShort(item.realisasi)"></span>
                        </div>
                        <div class="progress-modern">
                            <div class="progress-bar-gradient" 
                                 :style="'width: ' + item.persentase + '%'">
                            </div>
                        </div>
                        <div class="small text-muted mt-1" x-text="item.persentase.toFixed(1) + '% dari total'"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Year Comparison -->
        <div class="col-lg-6" data-aos="fade-up" data-aos-delay="700">
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h5 class="chart-title mb-0">Perbandingan Antar Tahun</h5>
                        <p class="chart-subtitle mb-0">3 Tahun Terakhir</p>
                    </div>
                </div>
                <div id="yearComparisonChart"></div>
            </div>
        </div>
    </div>

    <!-- SKPD Realisasi Table -->
    <div class="row">
        <div class="col-12" data-aos="fade-up" data-aos-delay="800">
            <div class="card-modern p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">
                            <i class='bx bx-building text-primary me-2'></i>
                            Realisasi Penerimaan per SKPD
                        </h5>
                        <p class="text-muted small mb-0">Total: <span x-text="skpdData.length"></span> SKPD</p>
                    </div>
                    <button class="btn btn-sm btn-primary-gradient" @click="exportTableToCSV('#skpdTable', 'realisasi-skpd.csv')">
                        <i class='bx bx-download me-1'></i> Export CSV
                    </button>
                </div>

                <!-- Search -->
                <div class="mb-3">
                    <input type="text" 
                           class="form-control" 
                           placeholder="Cari SKPD..."
                           x-model="searchSkpd"
                           style="max-width: 300px;">
                </div>

                <div class="table-responsive">
                    <table class="table-modern" id="skpdTable">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="12%">Kode</th>
                                <th width="35%">Nama SKPD</th>
                                <th width="15%" class="text-end">Target</th>
                                <th width="15%" class="text-end">Realisasi</th>
                                <th width="10%" class="text-center">%</th>
                                <th width="8%" class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(skpd, index) in filteredSkpdData" :key="index">
                                <tr>
                                    <td x-text="index + 1"></td>
                                    <td x-text="skpd.kode"></td>
                                    <td x-text="skpd.nama"></td>
                                    <td class="text-end" x-text="formatCurrencyShort(skpd.target)"></td>
                                    <td class="text-end" x-text="formatCurrencyShort(skpd.realisasi)"></td>
                                    <td class="text-center">
                                        <span class="badge" 
                                              :class="skpd.persentase >= 100 ? 'badge-success' : skpd.persentase >= 70 ? 'badge-warning' : 'badge-danger'"
                                              x-text="skpd.persentase.toFixed(1) + '%'">
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" 
                                              :class="skpd.persentase >= 100 ? 'badge-success' : skpd.persentase >= 70 ? 'badge-warning' : 'badge-danger'"
                                              x-text="skpd.status">
                                        </span>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="filteredSkpdData.length === 0">
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class='bx bx-search bx-lg d-block mb-2'></i>
                                        Tidak ada data SKPD
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div x-show="loading" 
         x-transition
         class="loading-overlay">
        <div class="spinner-modern"></div>
        <div class="loading-text">Memuat data...</div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function publicDashboard() {
    return {
        // State
        selectedYear: {{ $tahun }},
        loading: false,
        searchSkpd: '',
        
        // Data - Updated structure for 4 categories
        summary: {
            total: {
                realisasi: 0,
                target: 0,
                kurang: 0,
                persentase: 0
            },
            pad: {
                realisasi: 0,
                target: 0,
                kurang: 0,
                persentase: 0
            },
            transfer: {
                realisasi: 0,
                target: 0,
                kurang: 0,
                persentase: 0
            },
            lain_lain: {
                realisasi: 0,
                target: 0,
                kurang: 0,
                persentase: 0
            },
            update_terakhir: '-',
            tahun: {{ $tahun }}
        },
        skpdData: [],
        topCategories: [],
        
        // Charts
        monthlyChart: null,
        yearChart: null,
        
        // Init
        async init() {
            console.log('Initializing Public Dashboard...');
            await this.loadData();
        },
        
        // Load all data
        async loadData() {
            this.loading = true;
            
            try {
                await Promise.all([
                    this.loadSummary(),
                    this.loadSkpdData(),
                    this.loadTopCategories(),
                    this.loadMonthlyTrend(),
                    this.loadYearComparison()
                ]);
            } catch (error) {
                console.error('Load data error:', error);
                this.showToast('Gagal memuat data', 'error');
            } finally {
                this.loading = false;
            }
        },
        
        // Load summary
        async loadSummary() {
            const response = await fetch(`/api/public/summary?tahun=${this.selectedYear}`);
            const result = await response.json();
            
            if (result.success) {
                this.summary = result.data;
            }
        },
        
        // Load SKPD data
        async loadSkpdData() {
            const response = await fetch(`/api/public/skpd-realisasi?tahun=${this.selectedYear}`);
            const result = await response.json();
            
            if (result.success) {
                this.skpdData = result.data;
            }
        },
        
        // Load top categories
        async loadTopCategories() {
            const response = await fetch(`/api/public/top-categories?tahun=${this.selectedYear}&limit=5`);
            const result = await response.json();
            
            if (result.success) {
                this.topCategories = result.data;
            }
        },
        
        // Load monthly trend
        async loadMonthlyTrend() {
            const response = await fetch(`/api/public/monthly-trend?tahun=${this.selectedYear}`);
            const result = await response.json();
            
            if (result.success) {
                this.renderMonthlyChart(result.data);
            }
        },
        
        // Load year comparison
        async loadYearComparison() {
            const response = await fetch(`/api/public/yearly-comparison?tahun=${this.selectedYear}&years=3`);
            const result = await response.json();
            
            if (result.success) {
                this.renderYearChart(result.data);
            }
        },
        
        // Render monthly chart
        renderMonthlyChart(data) {
            if (this.monthlyChart) {
                this.monthlyChart.destroy();
            }
            
            const options = {
                series: data.series,
                chart: {
                    type: 'bar',
                    height: 350,
                    stacked: false,
                    ...window.publicChartDefaults.chart
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        endingShape: 'rounded'
                    }
                },
                xaxis: {
                    categories: data.categories
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return window.formatCurrency(val, { useShortFormat: true });
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return window.formatCurrency(val);
                        }
                    }
                },
                colors: ['#667eea', '#71dd37', '#ffab00'],
                ...window.publicChartDefaults
            };
            
            this.monthlyChart = new ApexCharts(document.querySelector("#monthlyTrendChart"), options);
            this.monthlyChart.render();
        },
        
        // Render year chart
        renderYearChart(data) {
            if (this.yearChart) {
                this.yearChart.destroy();
            }
            
            const options = {
                series: data.series,
                chart: {
                    type: 'bar',
                    height: 280,
                    ...window.publicChartDefaults.chart
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '50%',
                        endingShape: 'rounded'
                    }
                },
                xaxis: {
                    categories: data.categories
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            return window.formatCurrency(val, { useShortFormat: true });
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return window.formatCurrency(val);
                        }
                    }
                },
                colors: ['#667eea'],
                ...window.publicChartDefaults
            };
            
            this.yearChart = new ApexCharts(document.querySelector("#yearComparisonChart"), options);
            this.yearChart.render();
        },
        
        // Refresh data
        async refreshData() {
            await this.loadData();
            this.showToast('Data berhasil dimuat ulang', 'success');
        },
        
        // Computed: Filtered SKPD
        get filteredSkpdData() {
            if (!this.searchSkpd) return this.skpdData;
            
            const search = this.searchSkpd.toLowerCase();
            return this.skpdData.filter(skpd => 
                skpd.nama.toLowerCase().includes(search) ||
                skpd.kode.toLowerCase().includes(search)
            );
        },
        
        // Helpers
        formatCurrency(value) {
            return window.formatCurrency(value);
        },
        
        formatCurrencyShort(value) {
            return window.formatCurrency(value, { useShortFormat: true });
        },
        
        // ✨ NEW: Format currency FULL (no short format)
        formatCurrencyFull(value) {
            if (!value || value === 0) return 'Rp 0';
            
            const formatted = new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(Math.abs(value));
            
            return 'Rp ' + formatted;
        },
        
        showToast(message, type) {
            if (typeof window.showToast === 'function') {
                window.showToast(message, type);
            }
        },
        
        exportTableToCSV(selector, filename) {
            if (typeof window.exportTableToCSV === 'function') {
                window.exportTableToCSV(selector, filename);
            }
        }
    }
}
</script>
@endpush
