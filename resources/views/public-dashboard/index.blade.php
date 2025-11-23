<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <link rel="icon" href="{{ asset('images/silapat-favicon.png') }}">
    <title>SILAPAT - BPKPAD Banjarmasin</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/public-modern.css') }}">
    
    <style>


        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .dashboard-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .dashboard-header {
            flex-shrink: 0;
            height: 75px;
        }
        
        .info-banner-section {
            flex-shrink: 0;
            height: 42px;
        }
        
        .dashboard-content {
            flex: 1;
            overflow: hidden;
            padding: 0.75rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            min-height: 0;
        }
        
        .top-row {
    flex: 0 0 48%;     /* ← Dari 45% → 48% (lebih tinggi) */
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    min-height: 0;
}
        
        .bottom-row {
            flex: 1;
            min-height: 0;
        }
        
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 0.65rem;
            min-height: 0;
        }
        
        .card-big {
            flex: 0 0 auto;
        }
        
        .cards-small-container {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.65rem;
            min-height: 0;
        }
        
        .right-column {
            overflow: hidden;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .table-container {
            flex: 1;
            overflow-y: auto;
            border-radius: 0.75rem;
            background: white;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            min-height: 0;
            scroll-behavior: smooth;
        }
        
        .table-container:hover {
            scroll-behavior: auto;
        }
        
        .chart-container {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem 0.75rem 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        
        .dashboard-footer {
            flex-shrink: 0;
            height: 35px;
        }
        
        /* Gradient backgrounds */
        .gradient-purple {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .gradient-green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .gradient-cyan {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }
        
        .gradient-orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        /* Progress bar */
        .progress-bar {
            height: 5px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 2.5px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: white;
            border-radius: 2.5px;
            transition: width 0.3s ease;
        }
        
        /* Table styles */
        .table-skpd {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table-skpd thead {
            position: sticky;
            top: 0;
            background: #f9fafb;
            z-index: 10;
        }
        
        .table-skpd th {
    padding: 0.75rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.8rem;      // ← Lebih besar (13px)
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
    white-space: nowrap;
}
        
        
        .table-skpd td {
    padding: 0.8rem 1rem;
    font-size: 0.875rem;    // ← Lebih besar (14px)
    border-bottom: 1px solid #e5e7eb;
}
        
        .table-skpd tbody tr:hover {
            background: #f9fafb;
        }
        
        /* Logo styles */
        .logo-silapat {
            width: 150px;
            height: 150px;
            object-fit: contain;
        }
        
        /* Responsive */
        @media (max-width: 1280px) {
            .top-row {
                flex: 0 0 48%;
            }
        }
        
        @media (max-width: 1024px) {
            body {
                overflow: auto;
            }
            
            .dashboard-container {
                height: auto;
            }
            
            .dashboard-content {
                overflow-y: visible;
            }
            
            .top-row {
                grid-template-columns: 1fr;
                flex: 0 0 auto;
            }
            
            .cards-small-container {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                max-height: 400px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-content {
                padding: 0.75rem;
            }
            
            .cards-small-container {
                grid-template-columns: 1fr;
            }
        }

        /* FORCE FIX - Chart Alignment */
.bottom-row {
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
}

.chart-container {
    width: 100% !important;
    margin: 0 !important;
    padding-left: 1rem !important;
    padding-right: 1rem !important;
    box-sizing: border-box !important;
}

.top-row,
.bottom-row {
    padding-left: 0 !important;
    padding-right: 0 !important;
}
  </style>
</head>
<body class="bg-gray-50">
    <div class="dashboard-container" x-data="publicDashboard()">
        
        <!-- Header -->
        <div class="dashboard-header bg-white shadow-sm">
            <div class="px-5 py-2 h-full flex items-center justify-between">
                <!-- Logo & Title -->
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo SILAPAT" class="logo-silapat">
                    <div>
                        <h1 class="text-lg (20px) font-bold text-gray-800 leading-tight">Sistem Informasi Laporan Pendapatan Terpadu</h1>
                        <!-- <p class="text-xs text-gray-600 leading-tight mt-0.5">Sistem Informasi Laporan Pendapatan Terpadu</p> -->
                        <p class="text-[14px] text-gray-500 leading-tight mt-0.5">Badan Pengelolaan Keuangan, Pendapatan dan Aset Daerah Kota Banjarmasin</p>
                    </div>
                </div>
                
                <!-- Year Filter & Login -->
                <div class="flex items-center gap-2.5">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tahun Anggaran:</label>
                        <select 
                            x-model="selectedYear" 
                            @change="fetchAllData()"
                            class="px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                        </select>
                    </div>
                    
                    <button 
                        @click="fetchAllData()" 
                        class="px-3 py-1.5 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors mt-5"
                        title="Refresh Data"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                    
                    <a 
                        href="/login" 
                        class="px-4 py-1.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all shadow-md mt-5 flex items-center gap-2 text-sm font-medium"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                        Login
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Info Banner -->
        <div class="info-banner-section px-5">
            <div class="h-full bg-cyan-50 border border-cyan-200 rounded-lg px-4 flex items-center gap-2.5">
                <svg class="w-4 h-4 text-cyan-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1 flex items-center justify-between">
    <div class="flex items-center gap-1 text-sm">    <!-- ← Dari text-xs → text-sm -->
        <span class="text-gray-700">Data terakhir:</span>
        <span class="font-semibold text-cyan-900" x-text="lastUpdate"></span>
    </div>
    <div class="flex items-center gap-4 text-sm">    <!-- ← Dari text-xs → text-sm -->
        <div>
            <span class="text-gray-600">Total Transaksi:</span>
            <span class="font-semibold text-cyan-900 ml-1" x-text="totalTransaksi"></span>
        </div>
        <div>
            <span class="text-gray-600">SKPD:</span>
            <span class="font-semibold text-cyan-900 ml-1" x-text="skpdInput"></span>
        </div>
        <div>
            <span class="text-gray-600">Capaian:</span>
            <span class="font-semibold text-cyan-900 ml-1" x-text="capaianTotal"></span>
        </div>
    </div>
</div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="dashboard-content">
            
            <!-- Top Row: Cards (Left) + Table (Right) -->
            <div class="top-row">
                
                <!-- LEFT COLUMN: Cards -->
                <div class="left-column">
                    
                    <!-- Big Card: Total Pendapatan Daerah -->
                    <div class="card-big gradient-purple text-white rounded-xl p-4 shadow-lg">
                        <div class="flex items-start justify-between mb-2.5">
                            <div class="flex-1">
    <p class="text-white/90 text-lg (18px)) font-semibold mb-0.5">Total Pendapatan Daerah</p>
    <p class="text-base (14px) text-white/70">Realisasi vs Target</p>
</div>
                            <div class="bg-white/20 p-2 rounded-lg">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <div class="mb-2.5">
                            <h2 class="text-2xl font-bold mb-0.5" x-text="formatRupiahID(summary.total?.realisasi || 0)"></h2>
                            <p class="text-sm (14px) text-white/80">
                                <span x-text="formatPercentage(summary.total?.persentase || 0)"></span> dari target <span class="text-sm (14px)" x-text="formatRupiahID(summary.total?.target || 0)"></span>
                            </p>
                        </div>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" :style="`width: ${summary.total?.persentase || 0}%`"></div>
                        </div>
                    </div>
                    
                    <!-- Small Cards: PAD, Transfer, Lain-lain -->
                    <div class="cards-small-container">
                        
                        <!-- Card PAD -->
                        <div class="gradient-green text-white rounded-lg p-3 shadow">
                            <div class="flex items-start justify-between mb-2">
                               <p class="text-white/90 text-lg (18px) font-semibold flex-1">PAD</p>
                                <div class="bg-white/20 p-1.5 rounded">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <h3 class="text-base font-bold mb-1.5" x-text="formatRupiahID(summary.pad?.realisasi || 0)"></h3>
                            
                            <div class="progress-bar mb-1.5">
                                <div class="progress-fill" :style="`width: ${summary.pad?.persentase || 0}%`"></div>
                            </div>
                            
                            <p class="text-sm (14px) text-white/80">
                                <span class="font-semibold" x-text="formatPercentage(summary.pad?.persentase || 0)"></span> dari target
                            </p>
                        </div>
                        
                        <!-- Card Transfer -->
                        <div class="gradient-cyan text-white rounded-lg p-3 shadow">
                            <div class="flex items-start justify-between mb-2">
                               <p class="text-white/90 text-lg (18px) font-semibold flex-1">Transfer</p>
                                <div class="bg-white/20 p-1.5 rounded">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                    </svg>
                                </div>
                            </div>
                            
                            <h3 class="text-base font-bold mb-1.5" x-text="formatRupiahID(summary.transfer?.realisasi || 0)"></h3>
                            
                            <div class="progress-bar mb-1.5">
                                <div class="progress-fill" :style="`width: ${summary.transfer?.persentase || 0}%`"></div>
                            </div>
                            
                            <p class="text-sm (14px) text-white/80">
                                <span class="font-semibold" x-text="formatPercentage(summary.transfer?.persentase || 0)"></span> dari target
                            </p>
                        </div>
                        
                        <!-- Card Lain-lain -->
                        <div class="gradient-orange text-white rounded-lg p-3 shadow">
                            <div class="flex items-start justify-between mb-2">
                                <p class="text-white/90 text-lg (18px) font-semibold flex-1">Lain-lain</p>
                                <div class="bg-white/20 p-1.5 rounded">
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                </div>
                            </div>
                            
                           <h3 class="text-base font-bold mb-1.5" x-text="formatRupiahID(summary.lain?.realisasi || 0)"></h3>
                            
                            <div class="progress-bar mb-1.5">
                                <div class="progress-fill" :style="`width: ${summary.lain?.persentase || 0}%`"></div>
                            </div>
                            
                            <p class="text-sm (14px) text-white/80">
                                <span class="font-semibold" x-text="formatPercentage(summary.lain?.persentase || 0)"></span> dari target
                            </p>
                        </div>
                        
                    </div>
                </div>
                
                <!-- RIGHT COLUMN: Table SKPD -->
                <div class="right-column">
                    <div class="table-container" x-ref="tableContainer">
                        <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-200 sticky top-0 z-20">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800">Realisasi Penerimaan per SKPD</h3>
                                <span class="text-xs text-gray-600">Total: <span class="font-semibold" x-text="skpdData.length"></span> SKPD</span>
                            </div>
                        </div>
                        
                        <table class="table-skpd">
                            <thead>
                                <tr>
                                    <!-- <th class="w-8">NO</th> -->
                                    <th>NAMA SKPD</th>
                                    <th class="text-right w-48">REALISASI</th>
                                    <th class="text-center w-16">%</th>
                                    <!-- <th class="text-center w-20">STATUS</th> -->
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(skpd, index) in skpdData" :key="index">
                                    <tr>
                                        <!-- <td class="text-gray-500 text-center" x-text="index + 1"></td> -->
                                        <td class="font-medium text-gray-900" x-text="skpd.nama"></td>
                                        <td class="text-right font-semibold text-gray-900" x-text="formatRupiahID(skpd.realisasi)"></td>
                                        <td class="text-center">
                                            <span 
                                               class="px-2 py-1 rounded-full text-xs font-semibold inline-block"
                                                :class="skpd.persentase >= 85 ? 'bg-green-100 text-green-800' : skpd.persentase >= 70 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'"
                                                x-text="formatPercentage(skpd.persentase)"
                                            ></span>
                                        </td>
                                        <!-- <td class="text-center">
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold inline-block"
                                                :class="skpd.persentase >= 85 ? 'bg-yellow-400 text-white' : 'bg-red-500 text-white'"
                                                x-text="skpd.persentase >= 85 ? 'PROSES' : 'RENDAH'"
                                            ></span>
                                        </td> -->
                                    </tr>
                                </template>
                                
                                <!-- Loading State -->
                                <template x-if="loading && skpdData.length === 0">
                                    <tr>
                                        <td colspan="5" class="text-center py-8 text-gray-500">
                                            <div class="flex items-center justify-center gap-2">
                                                <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span class="text-sm">Memuat data...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                
                                <!-- Empty State -->
                                <template x-if="!loading && skpdData.length === 0">
                                    <tr>
                                        <td colspan="5" class="text-center py-8 text-gray-500 text-sm">
                                            Tidak ada data SKPD
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
            
            <!-- Bottom Row: Chart -->
            <div class="bottom-row">
                <div class="chart-container">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-lg font-semibold text-gray-800">Tren Penerimaan Bulanan</h3>
                        <span class="text-sm font-semibold text-gray-600">Tahun <span x-text="selectedYear"></span></span>
                    </div>
                    <div id="monthlyChart" class="flex-1"></div>
                </div>
            </div>
            
        </div>
        
        <!-- Footer -->
        <div class="dashboard-footer bg-gray-800 flex items-center justify-center px-5">
            <p class="text-xs text-gray-400">
                © 2025 Badan Pengelolaan Keuangan, Pendapatan dan Aset Daerah Kota Banjarmasin
                <span class="text-gray-500 mx-2">•</span>
                <span class="text-gray-300">Developed by Aulia Mayrina Rahmi</span>
            </p>
        </div>
    </div>
    
    <script>
        function publicDashboard() {
            return {
                selectedYear: 2025,
                loading: false,
                summary: {
                    total: null,
                    pad: null,
                    transfer: null,
                    lain: null
                },
                skpdData: [],
                monthlyChart: null,
                autoScrollInterval: null,
                isHovering: false,
                
                // Info banner
                lastUpdate: '13 November 2025',
                totalTransaksi: '15.686',
                skpdInput: '10',
                capaianTotal: '94.8%',
                
                init() {
                    this.fetchAllData();
                    this.startAutoScroll();
                },
                
                async fetchAllData() {
                    this.loading = true;
                    
                    try {
                        await Promise.all([
                            this.fetchSummary(),
                            this.fetchSkpdData(),
                            this.fetchMonthlyTrend()
                        ]);
                    } catch (error) {
                        console.error('Error fetching data:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                
                async fetchSummary() {
                    try {
                        const response = await fetch(`/api/public/summary?tahun=${this.selectedYear}`);
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            const data = result.data;
                            
                            // Map categories
                            if (data.categories && Array.isArray(data.categories)) {
                                this.summary = {
                                    total: data.categories.find(c => c.id === 'total') || {},
                                    pad: data.categories.find(c => c.id === 'pad') || {},
                                    transfer: data.categories.find(c => c.id === 'transfer') || {},
                                    lain: data.categories.find(c => c.id === 'lain') || {}
                                };
                            }
                            
                            // Update info banner
                            if (data.update_terakhir) {
                                this.lastUpdate = data.update_terakhir;
                            }
                            
                            if (data.total_transaksi) {
                                this.totalTransaksi = data.total_transaksi.toLocaleString('id-ID');
                            }
                            
                            if (data.skpd_count) {
                                this.skpdInput = data.skpd_count;
                            }
                            
                            if (data.persentase_capaian) {
                                this.capaianTotal = this.formatPercentage(data.persentase_capaian);
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching summary:', error);
                    }
                },
                
                async fetchSkpdData() {
                    try {
                        const response = await fetch(`/api/public/skpd-realisasi?tahun=${this.selectedYear}`);
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            this.skpdData = result.data;
                        }
                    } catch (error) {
                        console.error('Error fetching SKPD data:', error);
                    }
                },
                
                async fetchMonthlyTrend() {
                    try {
                        const response = await fetch(`/api/public/monthly-trend?tahun=${this.selectedYear}`);
                        const result = await response.json();
                        
                        if (result.success && result.data) {
                            this.renderMonthlyChart(result.data);
                        }
                    } catch (error) {
                        console.error('Error fetching monthly trend:', error);
                    }
                },
                
                renderMonthlyChart(data) {
                    const options = {
                        series: data.series || [],
                        chart: {
                            type: 'bar',
                            height: '100%',
                            toolbar: {
                                show: false
                            },
                            fontFamily: 'inherit'
                        },
                        plotOptions: {
                            bar: {
                                horizontal: false,
                                columnWidth: '70%',
                                borderRadius: 4
                            }
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
    categories: data.categories || [],
    labels: {
        style: {
            fontSize: '14px',      // ← Dari 10px → 12px
            fontWeight: 500,       // ← Lebih tebal
            colors: ['#374151']    // ← Warna lebih gelap
        }
    }
},
yaxis: {
    labels: {
        formatter: (value) => this.formatShortRupiah(value),
        style: {
            fontSize: '12px',      // ← Dari 10px → 12px
            fontWeight: 500,       // ← Lebih tebal
            colors: ['#374151']    // ← Warna lebih gelap
        }
    }
},
tooltip: {
    y: {
        formatter: (value) => this.formatRupiahID(value)
    },
    style: {
        fontSize: '13px',         // ← Dari 11px → 13px
        fontFamily: 'inherit'
    }
},
legend: {
    position: 'top',
    horizontalAlign: 'right',
    fontSize: '13px',             // ← Dari 10px → 12px
    fontWeight: 600,              // ← Lebih tebal
    markers: {
        width: 13,                // ← Dari 10px → 12px
        height: 13,               // ← Dari 10px → 12px
        radius: 3
    },
    labels: {
        colors: ['#374151']       // ← Warna lebih gelap
    }
},
                        colors: ['#10b981', '#06b6d4', '#f59e0b'],
                        grid: {
                            borderColor: '#e5e7eb',
                            strokeDashArray: 4,
                            xaxis: {
                                lines: {
                                    show: false
                                }
                            },
                            yaxis: {
                                lines: {
                                    show: true
                                }
                            },
                            padding: {
                                top: 0,
                                right: 10,
                                bottom: 0,
                                left: 10
                            }
                        }
                    };
                    
                    // Destroy existing chart
                    if (this.monthlyChart) {
                        this.monthlyChart.destroy();
                    }
                    
                    // Render new chart
                    this.monthlyChart = new ApexCharts(document.querySelector("#monthlyChart"), options);
                    this.monthlyChart.render();
                },
                
                // Auto-scroll table function
                startAutoScroll() {
                    const container = this.$refs.tableContainer;
                    
                    if (!container) return;
                    
                    // Pause on hover
                    container.addEventListener('mouseenter', () => {
                        this.isHovering = true;
                    });
                    
                    container.addEventListener('mouseleave', () => {
                        this.isHovering = false;
                    });
                    
                    // Auto scroll every 5 seconds
                    this.autoScrollInterval = setInterval(() => {
                        if (this.isHovering || this.skpdData.length === 0) return;
                        
                        const scrollHeight = container.scrollHeight;
                        const clientHeight = container.clientHeight;
                        const currentScroll = container.scrollTop;
                        
                        // Check if we're at the bottom
                        if (currentScroll + clientHeight >= scrollHeight - 10) {
                            // Scroll back to top
                            container.scrollTop = 0;
                        } else {
                            // Smooth scroll down by 60px
                            container.scrollTop += 60;
                        }
                    }, 5000); // 5 seconds
                },
                
                // Format Indonesian Rupiah with thousand separator (.) and decimal (,)
                formatRupiahID(value) {
                    if (!value) return 'Rp 0,00';
                    
                    const formatter = new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    
                    return 'Rp ' + formatter.format(value);
                },
                
                formatShortRupiah(value) {
                    if (!value) return 'Rp 0';
                    
                    if (value >= 1000000000000) {
                        return 'Rp ' + (value / 1000000000000).toFixed(2).replace('.', ',') + ' T';
                    } else if (value >= 1000000000) {
                        return 'Rp ' + (value / 1000000000).toFixed(2).replace('.', ',') + ' M';
                    } else if (value >= 1000000) {
                        return 'Rp ' + (value / 1000000).toFixed(2).replace('.', ',') + ' Jt';
                    }
                    
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                },
                
                formatPercentage(value) {
                    if (!value) return '0,00%';
                    return value.toFixed(2).replace('.', ',') + '%';
                }
            }
        }
    </script>
</body>
</html>