@extends('layouts.public')

@section('title', 'Trend Analysis Pendapatan - Kota Banjarmasin')

@section('content')
<div x-data="publicTrendAnalysis()" x-init="init()">
    
    <!-- Hero Section -->
    <div class="row mb-4" data-aos="fade-down">
        <div class="col-12">
            <div class="card-modern p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8">
                        <h1 class="text-gradient mb-2" style="font-size: 2rem; font-weight: 700;">
                            <i class='bx bx-trending-up me-2'></i>
                            Trend Analysis Pendapatan
                        </h1>
                        <p class="text-muted mb-3">
                            <i class='bx bx-line-chart me-1'></i>
                            Analisis Pola dan Tren Penerimaan Daerah Kota Banjarmasin
                        </p>
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="badge badge-modern bg-primary text-white">
                                <i class='bx bx-calendar me-1'></i>
                                <span x-text="'Multi-Year Analysis'"></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                        <a href="{{ route('public.dashboard') }}" class="btn btn-outline-primary">
                            <i class='bx bx-arrow-back me-1'></i>
                            Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section dengan Cascading -->
    <div class="row mb-4" data-aos="fade-up">
        <div class="col-12">
            <div class="card-modern p-3">
                <!-- Row 1: Tahun -->
                <div class="row align-items-center g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Tahun Awal</label>
                        <select class="form-select" x-model="startYear" @change="loadData()">
                            <option value="2020">2020</option>
                            <option value="2021">2021</option>
                            <option value="2022">2022</option>
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-muted mb-1">Tahun Akhir</label>
                        <select class="form-select" x-model="endYear" @change="loadData()">
                            <option value="2023">2023</option>
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                        </select>
                    </div>
                </div>
                
                <!-- Row 2: Cascading Filter -->
                <div class="cascading-filter-wrapper">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="form-label small text-muted mb-0">
                            <i class='bx bx-filter-alt me-1'></i>
                            Filter Kategori Hierarki
                        </label>
                        <button class="btn btn-sm btn-outline-danger" 
                                x-show="selectedLevel2" 
                                @click="resetFilter()"
                                style="display: none;">
                            <i class='bx bx-x'></i> Reset
                        </button>
                    </div>
                    
                    <div class="row g-2">
                        <!-- Level 2 -->
                        <div class="col-md-12">
                            <select class="form-select form-select-sm" x-model="selectedLevel2" @change="onLevel2Change()">
                                <option value="">Level 2 - Semua Kategori</option>
                                <template x-for="item in level2Options" :key="item.id">
                                    <option :value="item.id" x-text="item.kode + ' - ' + item.nama"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Level 3 -->
                        <div class="col-md-12" x-show="selectedLevel2" style="display: none;">
                            <select class="form-select form-select-sm" x-model="selectedLevel3" @change="onLevel3Change()">
                                <option value="">Level 3 - Semua Sub Kategori</option>
                                <template x-for="item in level3Options" :key="item.id">
                                    <option :value="item.id" x-text="item.kode + ' - ' + item.nama"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Level 4 -->
                        <div class="col-md-12" x-show="selectedLevel3" style="display: none;">
                            <select class="form-select form-select-sm" x-model="selectedLevel4" @change="onLevel4Change()">
                                <option value="">Level 4 - Pilih Detail</option>
                                <template x-for="item in level4Options" :key="item.id">
                                    <option :value="item.id" x-text="item.kode + ' - ' + item.nama"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Level 5 -->
                        <div class="col-md-12" x-show="selectedLevel4" style="display: none;">
                            <select class="form-select form-select-sm" x-model="selectedLevel5" @change="onLevel5Change()">
                                <option value="">Level 5 - Pilih Detail</option>
                                <template x-for="item in level5Options" :key="item.id">
                                    <option :value="item.id" x-text="item.kode + ' - ' + item.nama"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Level 6 -->
                        <div class="col-md-12" x-show="selectedLevel5" style="display: none;">
                            <select class="form-select form-select-sm" x-model="selectedLevel6" @change="onLevel6Change()">
                                <option value="">Level 6 - Pilih Detail Akhir</option>
                                <template x-for="item in level6Options" :key="item.id">
                                    <option :value="item.id" x-text="item.kode + ' - ' + item.nama"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row mb-4">
        <div class="col-12" data-aos="fade-up" data-aos-delay="500">
            <div class="chart-container">
                <div class="chart-header">
                    <div>
                        <h3 class="chart-title">Perbandingan Antar Tahun</h3>
                        <p class="chart-subtitle mb-0" x-text="getCategoryBreadcrumb()"></p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" @click="refreshData()">
                            <i class='bx bx-refresh'></i>
                        </button>
                    </div>
                </div>
                <div id="multiYearChart"></div>
            </div>
        </div>
    </div>

    <!-- Growth Table -->
    <div class="row">
        <div class="col-12" data-aos="fade-up" data-aos-delay="800">
            <div class="card-modern p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">
                            <i class='bx bx-table text-primary me-2'></i>
                            Tabel Growth Rate Year-over-Year
                        </h5>
                        <p class="text-muted small mb-0">Persentase pertumbuhan dibanding tahun sebelumnya</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table-modern">
                        <thead>
                            <tr>
                                <th width="20%">Tahun</th>
                                <th width="30%" class="text-end">Total Penerimaan</th>
                                <th width="20%" class="text-end">Growth Rate</th>
                                <th width="30%">Trend</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(item, index) in growthData" :key="index">
                                <tr>
                                    <td>
                                        <span class="fw-bold" x-text="item.year"></span>
                                    </td>
                                    <td class="text-end" x-text="formatCurrencyFull(item.total)"></td>
                                    <td class="text-end">
                                        <span class="badge" 
                                              :class="item.growth >= 0 ? 'badge-success' : 'badge-danger'"
                                              x-text="formatPercentage(item.growth)">
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress-modern">
                                            <div class="progress-bar-gradient" 
                                                 :style="'width: ' + Math.min(Math.abs(item.growth), 100) + '%'">
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="growthData.length === 0">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class='bx bx-data bx-lg d-block mb-2'></i>
                                        Tidak ada data
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
        <div class="loading-text">Memuat data trend...</div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function publicTrendAnalysis() {
    return {
        // State
        startYear: 2023,
        endYear: 2025,
        loading: false,
        
        // Cascading filter state
        selectedLevel2: '',
        selectedLevel3: '',
        selectedLevel4: '',
        selectedLevel5: '',
        selectedLevel6: '',
        
        // Options for each level
        level2Options: [],
        level3Options: [],
        level4Options: [],
        level5Options: [],
        level6Options: [],
        
        // Data
        stats: {
            avgGrowth: 0,
            highestGrowth: 0,
            highestYear: '-',
            bestMonth: '-',
            avgMonthly: 0
        },
        growthData: [],
        
        // Charts
        multiYearChart: null,
        
        // Init
        async init() {
            console.log('Initializing Public Trend Analysis...');
            await this.loadLevel2Options();
            await this.loadData();
        },
        
        // Load Level 2 options (4.1, 4.2, 4.3)
        async loadLevel2Options() {
            try {
                const response = await fetch('/api/public/kode-rekening-tree');
                const result = await response.json();
                
                if (result.success) {
                    this.level2Options = result.data;
                }
            } catch (error) {
                console.error('Load Level 2 error:', error);
            }
        },
        
        // Level 2 changed
        async onLevel2Change() {
            this.selectedLevel3 = '';
            this.selectedLevel4 = '';
            this.selectedLevel5 = '';
            this.selectedLevel6 = '';
            this.level3Options = [];
            this.level4Options = [];
            this.level5Options = [];
            this.level6Options = [];
            
            if (this.selectedLevel2) {
                await this.loadLevel3Options(this.selectedLevel2);
            }
            
            await this.loadData();
        },
        
        // Load Level 3 options
        async loadLevel3Options(parentId) {
            try {
                const response = await fetch(`/api/public/kode-rekening-tree?parent_id=${parentId}`);
                const result = await response.json();
                
                if (result.success) {
                    this.level3Options = result.data;
                }
            } catch (error) {
                console.error('Load Level 3 error:', error);
            }
        },
        
        // Level 3 changed
        async onLevel3Change() {
            this.selectedLevel4 = '';
            this.selectedLevel5 = '';
            this.selectedLevel6 = '';
            this.level4Options = [];
            this.level5Options = [];
            this.level6Options = [];
            
            if (this.selectedLevel3) {
                await this.loadLevel4Options(this.selectedLevel3);
            }
            
            await this.loadData();
        },
        
        // Load Level 4 options
        async loadLevel4Options(parentId) {
            try {
                const response = await fetch(`/api/public/kode-rekening-tree?parent_id=${parentId}`);
                const result = await response.json();
                
                if (result.success) {
                    this.level4Options = result.data;
                }
            } catch (error) {
                console.error('Load Level 4 error:', error);
            }
        },
        
        // Level 4 changed
        async onLevel4Change() {
            this.selectedLevel5 = '';
            this.selectedLevel6 = '';
            this.level5Options = [];
            this.level6Options = [];
            
            if (this.selectedLevel4) {
                await this.loadLevel5Options(this.selectedLevel4);
            }
            
            await this.loadData();
        },
        
        // Load Level 5 options
        async loadLevel5Options(parentId) {
            try {
                const response = await fetch(`/api/public/kode-rekening-tree?parent_id=${parentId}`);
                const result = await response.json();
                
                if (result.success) {
                    this.level5Options = result.data;
                }
            } catch (error) {
                console.error('Load Level 5 error:', error);
            }
        },
        
        // Level 5 changed
        async onLevel5Change() {
            this.selectedLevel6 = '';
            this.level6Options = [];
            
            if (this.selectedLevel5) {
                await this.loadLevel6Options(this.selectedLevel5);
            }
            
            await this.loadData();
        },
        
        // Load Level 6 options
        async loadLevel6Options(parentId) {
            try {
                const response = await fetch(`/api/public/kode-rekening-tree?parent_id=${parentId}`);
                const result = await response.json();
                
                if (result.success) {
                    this.level6Options = result.data;
                }
            } catch (error) {
                console.error('Load Level 6 error:', error);
            }
        },
        
        // Level 6 changed
        async onLevel6Change() {
            await this.loadData();
        },
        
        // Get selected category ID (lowest level selected)
        getSelectedCategoryId() {
            if (this.selectedLevel6) return this.selectedLevel6;
            if (this.selectedLevel5) return this.selectedLevel5;
            if (this.selectedLevel4) return this.selectedLevel4;
            if (this.selectedLevel3) return this.selectedLevel3;
            if (this.selectedLevel2) return this.selectedLevel2;
            return null;
        },
        
        // Get breadcrumb text
        getCategoryBreadcrumb() {
            const parts = [];
            
            if (this.selectedLevel2) {
                const item = this.level2Options.find(x => x.id == this.selectedLevel2);
                if (item) parts.push(item.nama);
            }
            if (this.selectedLevel3) {
                const item = this.level3Options.find(x => x.id == this.selectedLevel3);
                if (item) parts.push(item.nama);
            }
            if (this.selectedLevel4) {
                const item = this.level4Options.find(x => x.id == this.selectedLevel4);
                if (item) parts.push(item.nama);
            }
            if (this.selectedLevel5) {
                const item = this.level5Options.find(x => x.id == this.selectedLevel5);
                if (item) parts.push(item.nama);
            }
            if (this.selectedLevel6) {
                const item = this.level6Options.find(x => x.id == this.selectedLevel6);
                if (item) parts.push(item.nama);
            }
            
            return parts.length > 0 ? parts.join(' > ') : 'Semua Kategori';
        },
        
        // Reset filter
        async resetFilter() {
            this.selectedLevel2 = '';
            this.selectedLevel3 = '';
            this.selectedLevel4 = '';
            this.selectedLevel5 = '';
            this.selectedLevel6 = '';
            this.level3Options = [];
            this.level4Options = [];
            this.level5Options = [];
            this.level6Options = [];
            
            await this.loadData();
        },
        
        // Load all data
        async loadData() {
            this.loading = true;
            
            try {
                const categoryId = this.getSelectedCategoryId();
                const years = this.endYear - this.startYear + 1;
                
                let url;
                if (categoryId) {
                    url = `/api/public/category-trend/${categoryId}?years=${years}&view=yearly`;
                } else {
                    url = `/api/public/yearly-comparison?tahun=${this.endYear}&years=${years}`;
                }
                
                const response = await fetch(url);
                const result = await response.json();
                
                if (result.success) {
                    this.renderMultiYearChart(result.data);
                    this.processGrowthData(result.data);
                }
            } catch (error) {
                console.error('Load data error:', error);
            } finally {
                this.loading = false;
            }
        },
        
        // Render multi-year chart
        renderMultiYearChart(data) {
            if (this.multiYearChart) {
                this.multiYearChart.destroy();
            }
            
            const options = {
                series: data.series || [],
                chart: {
                    type: 'bar',
                    height: 320, // ✅ FIXED: 320px (was 350px)
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
                    categories: data.categories || []
                },
                yaxis: {
                    labels: {
                        formatter: function(val) {
                            // ✅ Y-axis: SHORT format (OK)
                            return window.formatCurrency(val, { useShortFormat: true });
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            // ✅ Tooltip: FULL format (FIXED)
                            if (!val || val === 0) return 'Rp 0';
                            return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                        }
                    }
                },
                colors: ['#667eea'],
                ...window.publicChartDefaults
            };
            
            this.multiYearChart = new ApexCharts(document.querySelector("#multiYearChart"), options);
            this.multiYearChart.render();
        },
        
        // Process growth data
        processGrowthData(data) {
            const years = data.categories || [];
            const totals = data.series && data.series[0] ? data.series[0].data : [];
            
            this.growthData = years.map((year, index) => {
                let growth = 0;
                if (index > 0 && totals[index - 1] > 0) {
                    growth = ((totals[index] - totals[index - 1]) / totals[index - 1]) * 100;
                }
                
                return {
                    year: year,
                    total: totals[index] || 0,
                    growth: growth
                };
            });
        },
        
        // Refresh data
        async refreshData() {
            await this.loadData();
        },
        
        // Helpers
        formatCurrencyFull(value) {
            if (!value || value === 0) return 'Rp 0';
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
        },
        
        formatPercentage(value, decimals = 1) {
            return value.toFixed(decimals) + '%';
        }
    }
}
</script>
@endpush
