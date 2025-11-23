@extends('layouts.app')

@section('title', 'SILAPAT - BPKPAD Banjarmasin')

@section('content')
<div class="container-fluid">
    <!-- Header -->
   <div class="row mb-4">
    <div class="col-md-8">
        <!-- ✨ NEW: Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb mb-0" style="background: transparent; padding: 0;">
                <li class="breadcrumb-item">
                    <a href="#" onclick="resetToOverview(); return false;" style="color: #667eea; text-decoration: none;">
                        <i class="bx bx-home-alt"></i> Overview
                    </a>
                </li>
                <li class="breadcrumb-item" id="breadcrumbCategory" style="display:none;">
                    <a href="#" onclick="backToCategory(); return false;" id="breadcrumbCategoryLink" style="color: #667eea; text-decoration: none;"></a>
                </li>
                <li class="breadcrumb-item active" id="breadcrumbYear" style="display:none;" aria-current="page"></li>
            </ol>
        </nav>
        
        <h4 class="page-title mb-1">Trend Analysis Penerimaan</h4>
        <p class="text-muted mb-0">Analisis trend penerimaan daerah multi-tahun</p>
    </div>
        <div class="col-md-4 text-end">
            <button class="btn btn-primary" id="searchModalBtn">
                <i class="bx bx-search me-1"></i> Cari Kategori
            </button>
            <button class="btn btn-outline-danger" id="resetBtn" style="display: none;">
                <i class="bx bx-reset me-1"></i> Reset
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert" style="display: none;">
        <i class="bx bx-error-circle me-2"></i>
        <span id="errorMessage">Error message here</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <!-- Row 1: Chart & Analysis Cards -->
    <div class="row mb-4">
        <!-- Chart Section (70%) -->
        <div class="col-lg-8 mb-4 mb-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-0">
                            <span id="chartTitle">Overview - Semua Kategori</span>
                        </h5>
                        <small class="text-muted">
                            <span id="categoryTitle">Overview - Semua Kategori</span>
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <!-- Year Range Selector -->
                        <div class="year-range-selector">
                            <label class="form-label mb-1 small">Periode</label>
                            <div class="btn-group btn-group-sm" role="group" id="yearButtons">
                                <button type="button" class="btn btn-outline-primary" data-years="2">2 Tahun</button>
                                <button type="button" class="btn btn-primary" data-years="3">3 Tahun</button>
                                <button type="button" class="btn btn-outline-primary" data-years="5">5 Tahun</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- View Toggle & Month Selector -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="view-toggle">
                            <label class="form-label mb-2 small text-uppercase">Mode Tampilan</label>
                            <div class="btn-group" role="group" id="viewToggle">
                                <button type="button" class="btn btn-primary" data-view="yearly">
                                    <i class="bx bx-calendar"></i> Tahunan
                                </button>
                                <button type="button" class="btn btn-outline-primary" data-view="monthly">
                                    <i class="bx bx-calendar-check"></i> Bulanan
                                </button>
                            </div>
                        </div>
                        
                        <!-- Month Selector (hidden by default) -->
                        <div class="month-selector" id="monthSelector" style="display: none;">
                            <label class="form-label mb-2 small text-uppercase">Pilih Bulan</label>
                            <select class="form-select" id="monthSelect">
                                <option value="1">Januari</option>
                                <option value="2">Februari</option>
                                <option value="3">Maret</option>
                                <option value="4">April</option>
                                <option value="5">Mei</option>
                                <option value="6">Juni</option>
                                <option value="7">Juli</option>
                                <option value="8">Agustus</option>
                                <option value="9">September</option>
                                <option value="10">Oktober</option>
                                <option value="11">November</option>
                                <option value="12">Desember</option>
                            </select>
                        </div>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="loadingChart" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat data...</p>
                    </div>

                    <!-- Chart Container -->
                    <div id="trendChart"></div>
                    
                    <!-- ✨ NEW: Secondary Chart Container (Hidden by default) -->
                    <div id="trendChartSecondary" style="display:none; margin-top:30px;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <div>
                                    <h5 class="card-title mb-0">
                                        <i class="bx bx-line-chart text-primary"></i>
                                        Detail Bulanan <span id="selectedYearTitle" class="text-primary"></span>
                                    </h5>
                                    <small class="text-muted">Trend penerimaan per bulan</small>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="hideSecondaryChart()" title="Tutup detail">
                                    <i class="bx bx-x"></i> Tutup
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="monthlyDetailChart"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Chart Actions -->
                   
                </div>
            </div>
        </div>

        <!-- Analysis Cards Section (30%) -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="card-title mb-0">Analisis Pertumbuhan</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6 col-lg-12">
                            <div class="growth-card" data-tippy-content="Total pertumbuhan dari periode awal hingga akhir">
                                <div class="growth-card-icon">
                                    <i class="bx bx-trending-up"></i>
                                </div>
                                <label class="growth-card-label">PERTUMBUHAN</label>
                                <div id="totalGrowthValue" class="growth-card-value">
                                    <span class="value-text">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-12">
                            <div class="growth-card growth-card-2" data-tippy-content="Rata-rata pertumbuhan per periode">
                                <div class="growth-card-icon">
                                    <i class="bx bx-bar-chart"></i>
                                </div>
                                <label class="growth-card-label">RATA-RATA</label>
                                <div id="avgGrowthValue" class="growth-card-value">
                                    <span class="value-text">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-12">
                            <div class="growth-card growth-card-3" data-tippy-content="Periode dengan pertumbuhan tertinggi">
                                <div class="growth-card-icon">
                                    <i class="bx bx-calendar-star"></i>
                                </div>
                                <label class="growth-card-label">PERIODE TERBAIK</label>
                                <div id="bestPerformerValue" class="growth-card-value">
                                    <span class="value-text">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-12">
                            <div class="growth-card growth-card-4" data-tippy-content="Status trend berdasarkan analisis pertumbuhan">
                                <div class="growth-card-icon">
                                    <i class="bx bx-stats"></i>
                                </div>
                                <label class="growth-card-label">STATUS TREND</label>
                                <div id="trendStatusValue" class="growth-card-value">
                                    <span class="value-text">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Insights -->
                    <div id="growthInsights" class="mt-4">
                        <!-- Dynamic insights -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Detail Table (Full Width) -->
    <div class="row">
        <div class="col-12">
            <!-- Monthly Comparison Table (hidden by default) -->
            <div class="card mb-4" id="monthlyComparisonTable" style="display: none;">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        Tabel Perbandingan Bulanan - <span id="comparisonMonthName"></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light" id="monthlyTableHeader">
                                <!-- Dynamic headers -->
                            </thead>
                            <tbody id="monthlyTableBody">
                                <!-- Dynamic content -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Growth Detail Table -->
            <div class="card" id="growthTableContainer">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Detail Pertumbuhan</h5>
                    <!-- <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="exportTableToCSV()" data-tippy-content="Export ke CSV">
                            <i class="bx bx-download"></i> Export
                        </button>
                        <button class="btn btn-outline-primary" onclick="window.print()" data-tippy-content="Print halaman">
                            <i class="bx bx-printer"></i> Print
                        </button>
                    </div> -->
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="15%">Periode</th>
                                    <th width="25%" class="text-end">Nilai</th>
                                    <th width="15%" class="text-end">Growth</th>
                                    <th width="15%" class="text-center">Trend</th>
                                    <th width="30%">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody id="growthTableBody">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bx bx-bar-chart-alt-2 bx-lg mb-3 d-block"></i>
                                        Data akan ditampilkan setelah chart dimuat
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Hidden inputs for state management -->
    <input type="hidden" id="currentCategoryId" value="">
    <input type="hidden" id="currentYearRange" value="3">
    <input type="hidden" id="currentView" value="yearly">
    <input type="hidden" id="currentMonth" value="{{ date('n') }}">
    <input type="hidden" id="yearRangeText" value="3">
    
    <!-- ✨ NEW: Drill-down state -->
    <input type="hidden" id="selectedYear" value="">
    <input type="hidden" id="isDrillDown" value="false">
</div>

<!-- Search Modal -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cari Kategori Penerimaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" class="form-control" id="modalSearchInput" 
                           placeholder="Ketik kode atau nama kategori..." autocomplete="off">
                    <small class="text-muted">Minimal 2 karakter untuk mulai pencarian</small>
                </div>
                <div id="modalSearchResults">
                    <div class="text-center text-muted py-4">
                        <i class="bx bx-search bx-lg"></i>
                        <p class="mt-2">Mulai mengetik untuk mencari kategori</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Growth Cards - Enhanced Design with Better Contrast */
.growth-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    color: white;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    height: 100%;
    min-height: 120px;
}

.growth-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
    transform: rotate(45deg);
}

.growth-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

/* Different colors for each card with darker overlay for better contrast */
.growth-card-2 {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    box-shadow: 0 5px 15px rgba(240, 147, 251, 0.3);
}

.growth-card-3 {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.3);
}

.growth-card-4 {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    box-shadow: 0 5px 15px rgba(67, 233, 123, 0.3);
}

/* Special treatment for green card - darker background for better contrast */
/* Alternative: Use darker green gradient for status trend */
.growth-card-4.alt-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

/* Ensure all text in growth cards is white with strong shadow */
.growth-card * {
    color: #ffffff !important;
}

.growth-card .value-text,
.growth-card-label {
    color: #ffffff !important;
    font-weight: 800;
}

/* Extra shadow for green card text for better visibility */
.growth-card-4 .value-text,
.growth-card-4 .growth-card-label,
.growth-card-4 * {
    color: #ffffff !important;
    text-shadow: 0 2px 8px rgba(0,0,0,0.5), 0 1px 3px rgba(0,0,0,0.8);
}

/* Make sure any dynamic content is also white */
#totalGrowthValue *,
#avgGrowthValue *,
#bestPerformerValue *,
#trendStatusValue * {
    color: #ffffff !important;
}

.growth-card-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    z-index: 2;
}

.growth-card-icon i {
    font-size: 45px;
    color: rgba(255,255,255,0.3);
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2));
}

.growth-card-label {
    font-size: 13px;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    display: block;
    margin-bottom: 10px;
    font-weight: 700;
    text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    position: relative;
    z-index: 2;
}

.growth-card-value {
    position: relative;
    z-index: 2;
}

.growth-card-value .value-text {
    font-size: 32px;
    font-weight: 800;
    color: #ffffff;
    text-shadow: 0 2px 6px rgba(0,0,0,0.3);
    display: block;
    letter-spacing: -1px;
}

.growth-card-value .value-icon {
    font-size: 20px;
    vertical-align: middle;
    margin-left: 5px;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-5px); }
    60% { transform: translateY(-3px); }
}

/* Chart Container */
#trendChart {
    min-height: 500px;
    background: #fafbfc;
    border-radius: 10px;
    padding: 10px;
}

/* Table Styling */
#growthTableContainer {
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

#growthTableContainer thead th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    padding: 15px 12px;
    border-bottom: 2px solid #dee2e6;
    position: sticky;
    top: 0;
    z-index: 10;
}

#growthTableContainer tbody td {
    padding: 12px;
    vertical-align: middle;
    font-size: 14px;
}

#growthTableContainer tbody tr:hover {
    background-color: #f8f9fa;
}

/* Growth Value Styling */
.growth-positive { color: #10b981 !important; font-weight: 600; }
.growth-negative { color: #ef4444 !important; font-weight: 600; }
.growth-neutral { color: #6b7280 !important; font-weight: 600; }
.growth-extreme { color: #f59e0b !important; font-weight: 600; }

/* Trend Badges */
.trend-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 14px;
    border-radius: 25px;
    font-size: 12px;
    font-weight: 600;
    gap: 5px;
}

.trend-badge i {
    font-size: 16px;
}

.trend-up {
    background: linear-gradient(135deg, #d4f4dd 0%, #b8e6cc 100%);
    color: #0f5132;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.2);
}

.trend-down {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #7f1d1d;
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
}

.trend-stable {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
    box-shadow: 0 2px 8px rgba(107, 114, 128, 0.2);
}

/* Insight Cards */
.insight-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    border-radius: 10px;
    margin-bottom: 10px;
    font-size: 13px;
    animation: slideIn 0.4s ease;
    backdrop-filter: blur(10px);
}

.insight-card i {
    font-size: 24px;
    flex-shrink: 0;
}

.insight-card.success {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
    color: #064e3b;
    border-left: 4px solid #10b981;
}

.insight-card.warning {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
    color: #78350f;
    border-left: 4px solid #f59e0b;
}

.insight-card.danger {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
    color: #7f1d1d;
    border-left: 4px solid #ef4444;
}

/* Search Modal */
#searchModal .modal-content {
    border: none;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
}

#searchModal .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
    padding: 20px 30px;
    position: relative;
}

#searchModal .modal-title {
    color: #ffffff !important;
    font-weight: 600;
    font-size: 20px;
}

/* Custom close button with better visibility */
#searchModal .btn-close {
    background-color: rgba(255, 255, 255, 0.2);
    background-image: none;
    opacity: 1;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

#searchModal .btn-close::before,
#searchModal .btn-close::after {
    content: '';
    position: absolute;
    width: 18px;
    height: 2px;
    background-color: #ffffff;
    top: 50%;
    left: 50%;
    transform-origin: center;
}

#searchModal .btn-close::before {
    transform: translate(-50%, -50%) rotate(45deg);
}

#searchModal .btn-close::after {
    transform: translate(-50%, -50%) rotate(-45deg);
}

#searchModal .btn-close:hover {
    background-color: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

#searchModal .btn-close:focus {
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.5);
}

#modalSearchInput {
    font-size: 16px;
    padding: 15px 25px;
    border: 2px solid #e5e7eb;
    border-radius: 50px;
    transition: all 0.3s;
    background: #f9fafb;
}

#modalSearchInput:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    background: white;
}

.search-result-item {
    padding: 15px 20px;
    border: 2px solid #f3f4f6;
    border-radius: 12px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
    position: relative;
    overflow: hidden;
}

.search-result-item::before {
    content: '→';
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #667eea;
    opacity: 0;
    transition: all 0.3s;
}

.search-result-item:hover {
    background: linear-gradient(135deg, #f0f4ff 0%, #e6edff 100%);
    border-color: #667eea;
    transform: translateX(10px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
}

.search-result-item:hover::before {
    opacity: 1;
    right: 15px;
}

.result-kode {
    font-weight: 700;
    color: #667eea;
    font-size: 15px;
}

.result-nama {
    color: #4b5563;
    font-size: 13px;
    margin-top: 5px;
}

/* Loading States */
#loadingChart {
    background-color: rgba(255, 255, 255, 0.95);
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}

/* Animations */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Animate data updates */
.growth-card[data-animated="true"] {
    animation: slideIn 0.3s ease;
}

#growthTableBody tr[data-animated="true"] {
    animation: fadeIn 0.3s ease;
}

/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
}

/* Responsive */
@media (max-width: 992px) {
    .growth-card {
        min-height: 100px;
        padding: 15px;
    }
    
    .growth-card-value .value-text {
        font-size: 24px;
    }
    
    .growth-card-icon i {
        font-size: 32px;
    }
}

@media (max-width: 768px) {
    #trendChart {
        min-height: 350px;
    }
    
    .growth-card {
        min-height: 90px;
    }
    
    .growth-card-value .value-text {
        font-size: 20px;
    }
    
    /* Hide keterangan column on mobile */
    #growthTableContainer th:last-child,
    #growthTableContainer td:last-child {
        display: none;
    }
    
    #growthTableContainer thead th {
        font-size: 11px;
        padding: 10px 8px;
    }
    
    #growthTableContainer tbody td {
        font-size: 12px;
        padding: 10px 8px;
    }
}

/* Print Styles */
@media print {
    .btn-group,
    #searchModalBtn,
    #resetBtn,
    #yearButtons,
    #viewToggle,
    #monthSelector {
        display: none !important;
    }
    
    .growth-card {
        background: #f3f4f6 !important;
        color: #000 !important;
        box-shadow: none !important;
        border: 1px solid #e5e7eb !important;
    }
    
    .growth-card-value .value-text {
        color: #000 !important;
    }
    
    .card {
        box-shadow: none !important;
        border: 1px solid #dee2e6 !important;
    }
}

/* Error Alert */
#errorAlert {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid #ef4444;
    color: #7f1d1d;
    border-radius: 10px;
    padding: 15px 20px;
    animation: shake 0.5s;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
    20%, 40%, 60%, 80% { transform: translateX(5px); }
}

/* Success Alert */
.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left: 4px solid #10b981;
    color: #064e3b;
    border-radius: 10px;
    padding: 15px 20px;
}

/* Chart Action Buttons */
.btn-outline-secondary {
    border-color: #e5e7eb;
    color: #6b7280;
}

.btn-outline-secondary:hover {
    background-color: #f3f4f6;
    border-color: #d1d5db;
    color: #4b5563;
}

/* Month selector enhancement */
#monthSelect {
    border-radius: 25px;
    padding: 8px 20px;
    font-size: 14px;
    border: 2px solid #e5e7eb;
    background-color: #f9fafb;
    transition: all 0.3s;
}

#monthSelect:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    background-color: white;
}

/* Year buttons enhancement */
#yearButtons .btn {
    transition: all 0.3s;
}

#yearButtons .btn.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

#yearButtons .btn.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}


#trendChartSecondary {
    animation: slideInUp 0.5s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#trendChartSecondary .card {
    border: 2px solid #e3e6f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

#trendChartSecondary .card-header {
    background: linear-gradient(135deg, #f8f9fc 0%, #eef2f7 100%);
    border-bottom: 2px solid #e3e6f0;
}

/* Clickable bar indication */
.apexcharts-bar-area.clickable {
    cursor: pointer;
    transition: all 0.2s ease;
}

.apexcharts-bar-area.clickable:hover {
    filter: brightness(1.2);
    stroke: #667eea;
    stroke-width: 2px;
}

/* Highlighted bar (selected year) */
.apexcharts-bar-area.highlighted {
    filter: brightness(1.3);
    stroke: #667eea;
    stroke-width: 3px;
    stroke-dasharray: 4;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

/* Breadcrumb styling enhancements */
.breadcrumb-item a {
    color: #667eea;
    text-decoration: none;
    transition: all 0.2s;
}

.breadcrumb-item a:hover {
    color: #764ba2;
    text-decoration: underline;
}

.breadcrumb-item.active {
    color: #6c757d;
    font-weight: 600;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #adb5bd;
    font-size: 1.2em;
}

/* Chart title badge for year */
#selectedYearTitle {
    font-size: 1.1em;
    font-weight: 700;
}

/* Secondary chart container loading state */
#trendChartSecondary .card-body {
    min-height: 400px;
    position: relative;
}

#monthlyDetailChart {
    min-height: 380px;
}

/* Tooltip for clickable bars */
.chart-bar-tooltip {
    position: absolute;
    background: rgba(102, 126, 234, 0.95);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    pointer-events: none;
    z-index: 1000;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.chart-bar-tooltip::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 5px solid transparent;
    border-right: 5px solid transparent;
    border-top: 5px solid rgba(102, 126, 234, 0.95);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #trendChartSecondary {
        margin-top: 20px;
    }
    
    #trendChartSecondary .card-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    #trendChartSecondary .card-header button {
        margin-top: 10px;
        width: 100%;
    }
    
    .breadcrumb {
        font-size: 0.85em;
    }
}

/* Print styles */
@media print {
    #trendChartSecondary .card-header button {
        display: none !important;
    }
    
    .breadcrumb {
        display: none !important;
    }
}
</style>
@endpush

@push('scripts')
<!-- ApexCharts -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>

<!-- Tippy.js for Tooltips -->
<script src="https://unpkg.com/@popperjs/core@2"></script>
<script src="https://unpkg.com/tippy.js@6"></script>

<!-- Trend Analysis Script -->
<script src="{{ asset('js/trend-analysis.js') }}?v={{ time() }}"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize search modal button
    const searchModalBtn = document.getElementById('searchModalBtn');
    if (searchModalBtn) {
        searchModalBtn.addEventListener('click', function() {
            const modalEl = document.getElementById('searchModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            }
        });
    }
    
    // Initialize tooltips
    if (typeof tippy !== 'undefined') {
        tippy('[data-tippy-content]', {
            theme: 'light',
            placement: 'bottom',
            animation: 'scale'
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K untuk search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('searchModalBtn').click();
        }
        
        // ESC untuk reset
        if (e.key === 'Escape' && document.getElementById('currentCategoryId').value) {
            const resetBtn = document.getElementById('resetBtn');
            if (resetBtn && resetBtn.style.display !== 'none') {
                resetBtn.click();
            }
        }
    });
    
    // Export to CSV function
    window.exportTableToCSV = function() {
        const table = document.querySelector('#growthTableBody');
        if (!table || !table.children.length) {
            alert('Tidak ada data untuk di-export');
            return;
        }
        
        let csv = 'Periode,Nilai,Growth,Trend,Keterangan\n';
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cols = row.querySelectorAll('td');
            if (cols.length >= 5) {
                const periode = cols[0].textContent.trim();
                const nilai = cols[1].textContent.trim();
                const growth = cols[2].textContent.trim();
                const trend = cols[3].textContent.trim();
                const keterangan = cols[4].textContent.trim();
                csv += `"${periode}","${nilai}","${growth}","${trend}","${keterangan}"\n`;
            }
        });
        
        // Download CSV
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'trend-analysis-' + new Date().toISOString().split('T')[0] + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    };
    
    // Animate data updates
    window.animateDataUpdate = function() {
        // Animate growth cards
        document.querySelectorAll('.growth-card').forEach((card, index) => {
            card.setAttribute('data-animated', 'true');
            setTimeout(() => {
                card.removeAttribute('data-animated');
            }, 500);
        });
        
        // Animate table rows
        setTimeout(() => {
            document.querySelectorAll('#growthTableBody tr').forEach((row, index) => {
                row.setAttribute('data-animated', 'true');
                setTimeout(() => {
                    row.removeAttribute('data-animated');
                }, 500);
            });
        }, 200);
    };
    
    // Show loading in table
    window.showTableLoading = function() {
        const tbody = document.getElementById('growthTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-3">
                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="ms-2">Menghitung pertumbuhan...</span>
                    </td>
                </tr>
            `;
        }
    };
    
    // Enhanced format currency function with full scale support
    window.formatCurrencyEnhanced = function(value) {
        if (!value || value === 0) return 'Rp 0';
        
        const absValue = Math.abs(value);
        const isNegative = value < 0;
        let formatted = '';
        
        // Format dengan skala lengkap
        if (absValue >= 1000000000000) { // Triliun
            const triliun = absValue / 1000000000000;
            if (triliun >= 1000) {
                // Ribuan Triliun
                formatted = 'Rp ' + (triliun / 1000).toFixed(2).replace('.', ',') + ' Ribu T';
            } else {
                formatted = 'Rp ' + triliun.toFixed(2).replace('.', ',') + ' T';
            }
        } else if (absValue >= 1000000000) { // Miliar
            const miliar = absValue / 1000000000;
            if (miliar >= 1000) {
                // Ribuan Miliar (mendekati triliun)
                formatted = 'Rp ' + (miliar / 1000).toFixed(2).replace('.', ',') + ' Ribu M';
            } else {
                formatted = 'Rp ' + miliar.toFixed(2).replace('.', ',') + ' M';
            }
        } else if (absValue >= 1000000) { // Juta
            const juta = absValue / 1000000;
            if (juta >= 1000) {
                // Ribuan Juta (mendekati miliar)
                formatted = 'Rp ' + (juta / 1000).toFixed(2).replace('.', ',') + ' Ribu Jt';
            } else {
                formatted = 'Rp ' + juta.toFixed(2).replace('.', ',') + ' Jt';
            }
        } else if (absValue >= 1000) { // Ribu
            formatted = 'Rp ' + (absValue / 1000).toFixed(2).replace('.', ',') + ' Rb';
        } else {
            // Di bawah ribu, tampilkan full number
            formatted = 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(absValue);
        }
        
        return isNegative ? '(' + formatted + ')' : formatted;
    };
    
    // Alternative: Format with automatic unit selection
    window.formatCurrencyAuto = function(value) {
        if (!value || value === 0) return 'Rp 0';
        
        const units = [
            { value: 1e12, suffix: ' T' },      // Triliun
            { value: 1e9, suffix: ' M' },       // Miliar  
            { value: 1e6, suffix: ' Jt' },      // Juta
            { value: 1e3, suffix: ' Rb' }       // Ribu
        ];
        
        const absValue = Math.abs(value);
        const isNegative = value < 0;
        
        for (const unit of units) {
            if (absValue >= unit.value) {
                const formatted = (absValue / unit.value).toFixed(2).replace('.', ',');
                const result = 'Rp ' + formatted + unit.suffix;
                return isNegative ? '(' + result + ')' : result;
            }
        }
        
        // Fallback untuk nilai kecil
        const formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(absValue);
        return isNegative ? '(' + formatted + ')' : formatted;
    };
    
    // Format untuk tooltip (lebih detail)
    window.formatCurrencyTooltip = function(value) {
        if (!value || value === 0) return 'Rp 0';
        
        // Full number dengan thousand separator
        const fullNumber = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(Math.abs(value));
        
        // Short format
        const shortFormat = formatCurrencyAuto(value);
        
        // Return both formats
        return `${shortFormat}<br><small class="text-muted">${fullNumber}</small>`;
    };
    
    // Add visual feedback when hovering chart
    const trendChart = document.getElementById('trendChart');
    if (trendChart) {
        trendChart.addEventListener('mouseenter', function() {
            this.style.boxShadow = '0 4px 20px rgba(0,0,0,0.1)';
        });
        
        trendChart.addEventListener('mouseleave', function() {
            this.style.boxShadow = 'none';
        });
    }
    
    // Smooth scroll to table when clicking on growth cards
    document.querySelectorAll('.growth-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', function() {
            const table = document.getElementById('growthTableContainer');
            if (table) {
                table.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

});

 setTimeout(function() {
        const monthSelect = document.getElementById('monthSelect');
        const currentMonth = new Date().getMonth() + 1;
        
        if (monthSelect) {
            monthSelect.value = currentMonth;
            console.log('Month selector initialized to:', currentMonth);
            
            // Re-attach event listener
            monthSelect.removeEventListener('change', handleMonthChange);
            monthSelect.addEventListener('change', function(e) {
                if (typeof handleMonthChange === 'function') {
                    handleMonthChange(e);
                } else {
                    console.error('handleMonthChange function not found');
                }
            });
        }
    }, 1000);
</script>

<script>
// FORCE RE-INIT SEARCH BUTTON
(function() {
    'use strict';
    
    function initSearchButton() {
        console.log('🔧 Force initializing search button...');
        
        const searchBtn = document.getElementById('searchModalBtn');
        const modalEl = document.getElementById('searchModal');
        
        if (!searchBtn || !modalEl) {
            console.error('❌ Button or modal not found');
            setTimeout(initSearchButton, 500);
            return;
        }
        
        console.log('✅ Found button and modal');
        
        // Remove ALL existing event listeners by cloning
        const newBtn = searchBtn.cloneNode(true);
        searchBtn.parentNode.replaceChild(newBtn, searchBtn);
        
        // Add fresh click handler
        newBtn.onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('🔘 Search button clicked!');
            
            try {
                if (typeof bootstrap === 'undefined') {
                    console.error('❌ Bootstrap not loaded');
                    return;
                }
                
                const modal = new bootstrap.Modal(modalEl, {
                    backdrop: true,
                    keyboard: true,
                    focus: true
                });
                
                modal.show();
                console.log('✅ Modal shown');
                
                // Focus search input when modal opens
                modalEl.addEventListener('shown.bs.modal', function() {
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.focus();
                        console.log('✅ Search input focused');
                    }
                }, { once: true });
                
            } catch (error) {
                console.error('❌ Error showing modal:', error);
            }
        };
        
        console.log('✅ Search button handler attached');
    }
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(initSearchButton, 1000);
        });
    } else {
        setTimeout(initSearchButton, 1000);
    }
})();
</script>



@endpush