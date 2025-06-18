/**
 * Trend Analysis JavaScript - Fixed Version
 * Fixed API endpoints to match Laravel routes
 */
// Check if ApexCharts is loaded
if (typeof ApexCharts === 'undefined') {
    console.error('ApexCharts is not loaded! Please include ApexCharts library before this script.');
}
// Global variables
let chart = null;
let searchTimeout = null;
let currentCategoryId = '';
let currentYearRange = 3;
let searchModal = null;
let currentView = 'yearly';
let currentMonth = new Date().getMonth() + 1;

// API endpoints - Fixed to match your Laravel setup
const API_BASE = '/api/trend';  // Changed from /api/trend-analysis
const API_OVERVIEW = `${API_BASE}/overview`;
const API_CATEGORY = `${API_BASE}/category`;
const API_SEARCH = `${API_BASE}/search`;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing trend analysis...');
    console.log('API endpoints:', {
        base: API_BASE,
        overview: API_OVERVIEW,
        category: API_CATEGORY,
        search: API_SEARCH
    });
    
    initializeModal();
    initializeEventListeners();
    initializeMonthlyFeatures();
    loadInitialData();
});

/**
 * Initialize Bootstrap modal
 */
function initializeModal() {
    const modalElement = document.getElementById('searchModal');
    if (modalElement) {
        searchModal = new bootstrap.Modal(modalElement);
        
        modalElement.addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalSearchInput').value = '';
            document.getElementById('modalSearchResults').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="bx bx-search bx-lg"></i>
                    <p class="mt-2">Mulai mengetik untuk mencari kategori</p>
                </div>
            `;
        });
        
        modalElement.addEventListener('shown.bs.modal', function() {
            document.getElementById('modalSearchInput').focus();
        });
    }
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Year range buttons
    const yearButtons = document.querySelectorAll('#yearButtons button');
    yearButtons.forEach(button => {
        button.addEventListener('click', handleYearChange);
    });
    
    // Modal search input
    const modalSearchInput = document.getElementById('modalSearchInput');
    if (modalSearchInput) {
        modalSearchInput.addEventListener('input', handleModalSearchInput);
    }
    
    // Reset button
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', handleReset);
    }
}

/**
 * Initialize monthly features
 */
function initializeMonthlyFeatures() {
    const viewButtons = document.querySelectorAll('#viewToggle button');
    viewButtons.forEach(button => {
        button.addEventListener('click', handleViewToggle);
    });
    
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.value = currentMonth;
        monthSelect.addEventListener('change', handleMonthChange);
    }
}

/**
 * Load initial data
 */
async function loadInitialData() {
    await loadChartData('overview');
}

/**
 * Load chart data from API
 */
async function loadChartData(type, categoryId = null) {
    console.log('Loading chart data:', type, categoryId, 'View:', currentView);
    
    showLoading();
    
    try {
        let url;
        if (type === 'overview') {
            url = `${API_OVERVIEW}?years=${currentYearRange}&view=${currentView}`;
            if (currentView === 'monthly') {
                url += `&month=${currentMonth}`;
            }
        } else {
            url = `${API_CATEGORY}/${categoryId}?years=${currentYearRange}&view=${currentView}`;
            if (currentView === 'monthly') {
                url += `&month=${currentMonth}`;
            }
        }
        
        console.log('Fetching from:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        
        console.log('Response status:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Response error:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API response:', result);
        
        if (result.success) {
            if (!result.data) {
                console.warn('No data in response');
                showError('Tidak ada data untuk ditampilkan');
                return;
            }
            
            // Check if we have valid chart data
            if (!result.data.categories || !result.data.series) {
                console.warn('Invalid data structure:', result.data);
                // Try to use empty arrays as fallback
                result.data.categories = result.data.categories || [];
                result.data.series = result.data.series || [];
            }
            
            if (currentView === 'monthly') {
                renderMonthlyChart(result.data);
                renderMonthlyTable(result.data);
                if (result.data.summary) {
                    updateMonthlySummary(result.data.summary);
                }
            } else {
                renderChart(result.data);
                const monthlyTable = document.getElementById('monthlyComparisonTable');
                if (monthlyTable) {
                    monthlyTable.style.display = 'none';
                }
            }
            
            // Update summary if exists
            if (result.data.summary) {
                updateSummary(result.data.summary);
            }
            
            hideError();
        } else {
            showError(result.message || 'Gagal memuat data');
        }
    } catch (error) {
        console.error('Load data error:', error);
        showError('Terjadi kesalahan: ' + error.message);
    } finally {
        hideLoading();
    }
}

/**
 * Render chart for yearly view
 */
/**
 * Render chart for yearly view
 */
function renderChart(data) {
    console.log('Rendering chart with data:', data);
    
    // Validasi data
    if (!data || !data.series || !data.categories) {
        console.error('Invalid chart data');
        data = {
            categories: [],
            series: []
        };
    }
    
    // Pastikan container ada
    const chartContainer = document.querySelector("#trendChart");
    if (!chartContainer) {
        console.error('Chart container not found');
        return;
    }
    
    // Destroy existing chart
    if (chart) {
        try {
            chart.destroy();
            chart = null;
        } catch (e) {
            console.error('Error destroying chart:', e);
        }
    }
    
    // Clear container
    chartContainer.innerHTML = '';
    
    // Chart options
    const options = {
        series: data.series || [],
        chart: {
            type: 'line',
            height: 450,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            },
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    selection: false,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            }
        },
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        markers: {
            size: 5,
            hover: {
                size: 7
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: data.categories || [],
            title: {
                text: 'Tahun',
                style: {
                    fontSize: '14px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            title: {
                text: 'Jumlah Penerimaan',
                style: {
                    fontSize: '14px',
                    fontWeight: 600
                }
            },
            labels: {
                formatter: function(val) {
                    if (val === 0) return 'Rp 0';
                    if (val >= 1000000000) {
                        return 'Rp ' + (val / 1000000000).toFixed(1) + ' M';
                    } else if (val >= 1000000) {
                        return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
                    }
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val) {
                    if (!val || val === 0) return 'Rp 0';
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                }
            }
        },
        grid: {
            borderColor: '#e7e7e7',
            strokeDashArray: 5
        },
        noData: {
            text: 'Tidak ada data untuk ditampilkan',
            align: 'center',
            verticalAlign: 'middle',
            offsetX: 0,
            offsetY: 0,
            style: {
                color: '#999',
                fontSize: '16px'
            }
        }
    };
    
    // Create new chart
    try {
        chart = new ApexCharts(chartContainer, options);
        chart.render();
        
        // Calculate growth analysis if we have data
        if (data.series && data.series.length > 0) {
            calculateGrowthAnalysis(data);
        } else {
            resetGrowthAnalysis();
        }
    } catch (error) {
        console.error('Error creating chart:', error);
    }
}

/**
 * Handle view toggle
 */
function handleViewToggle(event) {
    const button = event.target.closest('button');
    const view = button.dataset.view;
    
    if (view === currentView) return;
    
    console.log('View toggled to:', view);
    
    // Update UI
    document.querySelectorAll('#viewToggle button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-primary');
    
    // Show/hide month selector
    const monthSelector = document.getElementById('monthSelector');
    const monthlyTable = document.getElementById('monthlyComparisonTable');
    
    if (view === 'monthly') {
        if (monthSelector) monthSelector.style.display = 'block';
        if (monthlyTable) monthlyTable.style.display = 'block';
    } else {
        if (monthSelector) monthSelector.style.display = 'none';
        if (monthlyTable) monthlyTable.style.display = 'none';
    }
    
    currentView = view;
    
    // Reload data
    if (currentCategoryId) {
        loadChartData('category', currentCategoryId);
    } else {
        loadChartData('overview');
    }
}

/**
 * Handle month change
 */
function handleMonthChange(event) {
    currentMonth = parseInt(event.target.value);
    console.log('Month changed to:', currentMonth);
    
    if (currentCategoryId) {
        loadChartData('category', currentCategoryId);
    } else {
        loadChartData('overview');
    }
}

/**
 * Handle year range change
 */
function handleYearChange(event) {
    const button = event.target;
    const years = parseInt(button.dataset.years);
    
    console.log('Year range changed to:', years);
    
    // Update UI
    document.querySelectorAll('#yearButtons button').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-outline-primary');
    });
    button.classList.remove('btn-outline-primary');
    button.classList.add('btn-primary');
    
    currentYearRange = years;
    
    // Reload data
    if (currentCategoryId) {
        loadChartData('category', currentCategoryId);
    } else {
        loadChartData('overview');
    }
}

/**
 * Handle modal search input
 */
function handleModalSearchInput(event) {
    const searchTerm = event.target.value.trim();
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    if (searchTerm.length < 2) {
        document.getElementById('modalSearchResults').innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bx bx-search bx-lg"></i>
                <p class="mt-2">Mulai mengetik untuk mencari kategori</p>
            </div>
        `;
        return;
    }
    
    document.getElementById('modalSearchResults').innerHTML = `
        <div class="search-loading">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Mencari...</p>
        </div>
    `;
    
    searchTimeout = setTimeout(() => {
        searchCategoriesModal(searchTerm);
    }, 300);
}

/**
 * Search categories
 */
async function searchCategoriesModal(searchTerm) {
    try {
        const response = await fetch(`${API_SEARCH}?q=${encodeURIComponent(searchTerm)}`);
        const result = await response.json();
        
        if (result.success && result.data.length > 0) {
            displayModalSearchResults(result.data);
        } else {
            displayModalSearchResults([]);
        }
    } catch (error) {
        console.error('Search error:', error);
        document.getElementById('modalSearchResults').innerHTML = `
            <div class="text-center text-danger py-4">
                <i class="bx bx-error-circle bx-lg"></i>
                <p class="mt-2">Terjadi kesalahan saat mencari</p>
            </div>
        `;
    }
}

/**
 * Display search results
 */
function displayModalSearchResults(results) {
    const container = document.getElementById('modalSearchResults');
    
    if (results.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bx bx-search-alt bx-lg"></i>
                <p class="mt-2">Tidak ada hasil ditemukan</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    results.forEach(result => {
        html += `
            <div class="search-result-item" data-id="${result.id}" data-nama="${result.nama}">
                <div class="result-kode">${result.kode}</div>
                <div class="result-nama">${result.nama}</div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    container.querySelectorAll('.search-result-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            selectCategoryFromModal({ id, nama });
        });
    });
}

/**
 * Select category from modal
 */
function selectCategoryFromModal(category) {
    console.log('Category selected:', category);
    
    currentCategoryId = category.id;
    
    // Update UI elements if they exist
    const categoryTitle = document.getElementById('categoryTitle');
    if (categoryTitle) {
        categoryTitle.textContent = category.nama;
    }
    
    const chartTitle = document.getElementById('chartTitle');
    if (chartTitle) {
        chartTitle.textContent = category.nama;
    }
    
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.style.display = 'inline-block';
    }
    
    // Hide modal
    if (searchModal) {
        searchModal.hide();
    }
    
    // Load category data
    loadChartData('category', category.id);
}

/**
 * Handle reset
 */
function handleReset() {
    currentCategoryId = '';
    document.getElementById('categoryTitle').textContent = 'Overview - Semua Kategori';
    document.getElementById('chartTitle').textContent = 'Overview - Semua Kategori';
    
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.style.display = 'none';
    }
    
    loadChartData('overview');
}

/**
 * Render monthly chart
 */
function renderMonthlyChart(data) {
    console.log('Rendering monthly chart:', data);
    
    if (!data || !data.series || !data.categories) {
        data = {
            categories: [],
            series: []
        };
    }
    
    if (chart) {
        chart.destroy();
    }
    
    const options = {
        series: data.series || [],
        chart: {
            type: 'bar',
            height: 450,
            toolbar: {
                show: true
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '65%',
                endingShape: 'rounded'
            }
        },
        dataLabels: {
            enabled: false
        },
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00'],
        xaxis: {
            categories: data.categories || [],
            title: {
                text: 'Tahun'
            }
        },
        yaxis: {
            title: {
                text: `Penerimaan Bulan ${data.monthName || ''}`
            },
            labels: {
                formatter: function(val) {
                    if (val === 0) return 'Rp 0';
                    if (val >= 1000000000) {
                        return 'Rp ' + (val / 1000000000).toFixed(1) + ' M';
                    }
                    return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left'
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function(val) {
                    if (!val || val === 0) return 'Rp 0';
                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                }
            }
        }
    };
    
    chart = new ApexCharts(document.querySelector("#trendChart"), options);
    chart.render();
}

/**
 * Render monthly table
 */
function renderMonthlyTable(data) {
    console.log('Rendering monthly table:', data);
    // Implementation for monthly table
}

/**
 * Update summary
 */
function updateSummary(summary) {
    console.log('Updating summary:', summary);
    // Implementation for summary update
}

/**
 * Update monthly summary
 */
function updateMonthlySummary(summary) {
    console.log('Updating monthly summary:', summary);
    // Implementation for monthly summary
}

/**
 * Calculate growth analysis from chart data
 */
function calculateGrowthAnalysis(data) {
    console.log('Calculating growth analysis...', data);
    
    if (!data.series || data.series.length === 0) {
        resetGrowthAnalysis();
        return;
    }
    
    const years = data.categories || [];
    
    // Jika hanya satu series (single category)
    if (data.series.length === 1) {
        const serie = data.series[0];
        const values = serie.data || [];
        
        if (values.length < 2) {
            resetGrowthAnalysis();
            return;
        }
        
        // Calculate growth between first and last year
        const firstValue = values[0];
        const lastValue = values[values.length - 1];
        
        let totalGrowth = 0;
        if (firstValue > 0) {
            totalGrowth = ((lastValue - firstValue) / firstValue) * 100;
        } else if (lastValue > 0) {
            totalGrowth = 100;
        }
        
        // Calculate year-over-year growth rates
        const growthRates = [];
        for (let i = 1; i < values.length; i++) {
            if (values[i-1] > 0) {
                const growth = ((values[i] - values[i-1]) / values[i-1]) * 100;
                growthRates.push(growth);
            }
        }
        
        // Calculate average growth (CAGR)
        let avgGrowth = 0;
        if (firstValue > 0 && values.length > 1) {
            const years = values.length - 1;
            avgGrowth = (Math.pow(lastValue / firstValue, 1/years) - 1) * 100;
        }
        
        // Find best performing year
        let maxValue = Math.max(...values);
        let bestYearIndex = values.indexOf(maxValue);
        let bestYear = years[bestYearIndex] || '-';
        
        // Update summary cards
        updateSummaryCards({
            totalGrowth: totalGrowth,
            avgGrowth: avgGrowth,
            bestPerformer: bestYear,
            trendStatus: determineTrendStatus(totalGrowth)
        });
        
        // Create detail table
        createSingleCategoryTable(years, values, serie.name);
        
    } else {
        // Multiple series (overview mode)
        let totalFirstYear = 0;
        let totalLastYear = 0;
        const seriesGrowth = [];
        
        data.series.forEach(serie => {
            const values = serie.data || [];
            if (values.length > 0) {
                const firstValue = values[0] || 0;
                const lastValue = values[values.length - 1] || 0;
                
                totalFirstYear += firstValue;
                totalLastYear += lastValue;
                
                let growth = 0;
                if (firstValue > 0) {
                    growth = ((lastValue - firstValue) / firstValue) * 100;
                }
                
                seriesGrowth.push({
                    name: serie.name,
                    growth: growth,
                    firstValue: firstValue,
                    lastValue: lastValue
                });
            }
        });
        
        // Calculate total growth
        let totalGrowth = 0;
        if (totalFirstYear > 0) {
            totalGrowth = ((totalLastYear - totalFirstYear) / totalFirstYear) * 100;
        }
        
        // Find best performer
        let bestPerformer = { name: '-', growth: -Infinity };
        seriesGrowth.forEach(item => {
            if (item.growth > bestPerformer.growth) {
                bestPerformer = { name: item.name, growth: item.growth };
            }
        });
        
        // Calculate average growth
        const avgGrowth = seriesGrowth.length > 0 
            ? seriesGrowth.reduce((sum, item) => sum + item.growth, 0) / seriesGrowth.length
            : 0;
        
        // Update summary cards
        updateSummaryCards({
            totalGrowth: totalGrowth,
            avgGrowth: avgGrowth,
            bestPerformer: bestPerformer.name,
            trendStatus: determineTrendStatus(totalGrowth)
        });
        
        // Create overview table
        createOverviewTable(seriesGrowth);
    }
}

/**
 * Create table for single category analysis
 */
function createSingleCategoryTable(years, values, categoryName) {
    const container = document.getElementById('growthTableContainer');
    const tbody = document.getElementById('growthTableBody');
    
    if (!container || !tbody) return;
    
    container.style.display = 'block';
    tbody.innerHTML = '';
    
    // Add header row
    const headerRow = document.createElement('tr');
    headerRow.innerHTML = `
        <td colspan="5" class="text-center fw-bold bg-light">${categoryName}</td>
    `;
    tbody.appendChild(headerRow);
    
    // Add year rows
    for (let i = 0; i < years.length; i++) {
        const row = document.createElement('tr');
        const value = values[i] || 0;
        
        let growthCell = '<td class="text-end text-muted">-</td>';
        let trendCell = '<td class="text-center">-</td>';
        let descCell = '<td>Tahun dasar</td>';
        
        if (i > 0) {
            const prevValue = values[i-1] || 0;
            let growth = 0;
            
            if (prevValue > 0) {
                growth = ((value - prevValue) / prevValue) * 100;
            } else if (value > 0) {
                growth = 100;
            }
            
            growthCell = `<td class="text-end ${getGrowthClass(growth)}">${formatGrowthValue(growth)}</td>`;
            trendCell = `<td class="text-center">${getTrendBadge(growth)}</td>`;
            descCell = `<td>${getGrowthDescription(growth)}</td>`;
        }
        
        row.innerHTML = `
            <td><strong>${years[i]}</strong></td>
            <td class="text-end">${formatCurrency(value)}</td>
            ${growthCell}
            ${trendCell}
            ${descCell}
        `;
        
        tbody.appendChild(row);
    }
}

/**
 * Create overview table for multiple categories
 */
function createOverviewTable(seriesGrowth) {
    const container = document.getElementById('growthTableContainer');
    const tbody = document.getElementById('growthTableBody');
    
    if (!container || !tbody) return;
    
    container.style.display = 'block';
    tbody.innerHTML = '';
    
    // Sort by growth descending
    seriesGrowth.sort((a, b) => b.growth - a.growth);
    
    seriesGrowth.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>${item.name}</strong></td>
            <td class="text-end">${formatCurrency(item.lastValue)}</td>
            <td class="text-end ${getGrowthClass(item.growth)}">${formatGrowthValue(item.growth)}</td>
            <td class="text-center">${getTrendBadge(item.growth)}</td>
            <td>${getGrowthDescription(item.growth)}</td>
        `;
        tbody.appendChild(row);
    });
}

/**
 * Update summary cards dengan animasi
 */
function updateSummaryCards(data) {
    // Total Growth
    const totalGrowthEl = document.getElementById('totalGrowthValue');
    if (totalGrowthEl) {
        totalGrowthEl.innerHTML = formatGrowthValue(data.totalGrowth);
        totalGrowthEl.className = 'growth-card-value ' + getGrowthClass(data.totalGrowth);
    }
    
    // Average Growth
    const avgGrowthEl = document.getElementById('avgGrowthValue');
    if (avgGrowthEl) {
        avgGrowthEl.innerHTML = formatGrowthValue(data.avgGrowth);
        avgGrowthEl.className = 'growth-card-value ' + getGrowthClass(data.avgGrowth);
    }
    
    // Best Performer
    const bestPerformerEl = document.getElementById('bestPerformerValue');
    if (bestPerformerEl) {
        bestPerformerEl.textContent = data.bestPerformer;
        bestPerformerEl.className = 'growth-card-value';
    }
    
    // Trend Status
    const statusEl = document.getElementById('trendStatusValue');
    if (statusEl) {
        statusEl.innerHTML = data.trendStatus;
        statusEl.className = 'growth-card-value';
    }
}

/**
 * Helper function to format growth value
 */
function formatGrowthValue(growth) {
    const value = growth.toFixed(1);
    const icon = growth > 0 ? '↑' : growth < 0 ? '↓' : '→';
    return `${value}% ${icon}`;
}

/**
 * Helper function to get growth class
 */
function getGrowthClass(growth) {
    if (growth > 0) return 'growth-positive';
    if (growth < 0) return 'growth-negative';
    return 'growth-neutral';
}

/**
 * Helper function to get trend badge
 */
function getTrendBadge(growth) {
    if (growth > 0) {
        return '<span class="trend-badge trend-up"><i class="bx bx-trending-up me-1"></i>Naik</span>';
    } else if (growth < 0) {
        return '<span class="trend-badge trend-down"><i class="bx bx-trending-down me-1"></i>Turun</span>';
    }
    return '<span class="trend-badge trend-stable"><i class="bx bx-minus me-1"></i>Stabil</span>';
}

/**
 * Helper function to determine trend status
 */
function determineTrendStatus(growth) {
    if (growth > 10) return '<span class="text-success">Sangat Baik</span>';
    if (growth > 0) return '<span class="text-info">Positif</span>';
    if (growth === 0) return '<span class="text-muted">Stabil</span>';
    if (growth > -10) return '<span class="text-warning">Perlu Perhatian</span>';
    return '<span class="text-danger">Kritis</span>';
}

/**
 * Helper function to get growth description
 */
function getGrowthDescription(growth) {
    if (growth > 20) return 'Pertumbuhan sangat tinggi';
    if (growth > 10) return 'Pertumbuhan baik';
    if (growth > 0) return 'Pertumbuhan moderat';
    if (growth === 0) return 'Tidak ada perubahan';
    if (growth > -10) return 'Penurunan ringan';
    return 'Penurunan signifikan';
}

/**
 * Reset growth analysis
 */
function resetGrowthAnalysis() {
    updateSummaryCards({
        totalGrowth: 0,
        avgGrowth: 0,
        bestPerformer: '-',
        trendStatus: '-'
    });
    
    const container = document.getElementById('growthTableContainer');
    if (container) container.style.display = 'none';
}

/**
 * Update summary cards
 */
function updateSummaryCards(data) {
    const elements = {
        'totalGrowthValue': formatGrowthValue(data.totalGrowth),
        'avgGrowthValue': formatGrowthValue(data.avgGrowth),
        'bestPerformerValue': data.bestPerformer,
        'trendStatusValue': data.trendStatus
    };
    
    Object.entries(elements).forEach(([id, value]) => {
        const el = document.getElementById(id);
        if (el) {
            if (id.includes('Growth')) {
                el.innerHTML = value;
            } else {
                el.innerHTML = value;
            }
        }
    });
}

/**
 * Reset growth analysis
 */
function resetGrowthAnalysis() {
    updateSummaryCards({
        totalGrowth: 0,
        avgGrowth: 0,
        bestPerformer: '-',
        trendStatus: '-'
    });
}

/**
 * Helper functions
 */
function formatGrowthValue(growth) {
    return `${growth.toFixed(1)}%`;
}

function formatCurrency(value) {
    if (!value || value === 0) return 'Rp 0';
    
    // Untuk nilai sangat besar
    if (value >= 1000000000000) { // Trilliun
        return 'Rp ' + (value / 1000000000000).toFixed(2) + ' T';
    } else if (value >= 1000000000) { // Milliar
        return 'Rp ' + (value / 1000000000).toFixed(2) + ' M';
    } else if (value >= 1000000) { // Juta
        return 'Rp ' + (value / 1000000).toFixed(2) + ' Jt';
    } else if (value >= 1000) { // Ribu
        return 'Rp ' + (value / 1000).toFixed(2) + ' Rb';
    }
    
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
}

function showLoading() {
    const loadingEl = document.getElementById('loadingChart');
    if (loadingEl) loadingEl.style.display = 'flex';
}

function hideLoading() {
    const loadingEl = document.getElementById('loadingChart');
    if (loadingEl) loadingEl.style.display = 'none';
}

function showError(message) {
    const errorMsg = document.getElementById('errorMessage');
    const errorAlert = document.getElementById('errorAlert');
    
    if (errorMsg) errorMsg.textContent = message;
    if (errorAlert) errorAlert.style.display = 'block';
}

function hideError() {
    const errorAlert = document.getElementById('errorAlert');
    if (errorAlert) errorAlert.style.display = 'none';
}

// Export for debugging
window.trendAnalysisDebug = {
    getCurrentView: () => currentView,
    getCurrentMonth: () => currentMonth,
    getCurrentYearRange: () => currentYearRange,
    getCurrentCategoryId: () => currentCategoryId,
    getAPIEndpoints: () => ({
        base: API_BASE,
        overview: API_OVERVIEW,
        category: API_CATEGORY,
        search: API_SEARCH
    }),
    testAPI: async () => {
        const url = `${API_OVERVIEW}?years=${currentYearRange}&view=${currentView}`;
        console.log('Testing API:', url);
        try {
            const response = await fetch(url);
            console.log('Response status:', response.status);
            const text = await response.text();
            console.log('Response text:', text);
            try {
                const data = JSON.parse(text);
                console.log('Parsed data:', data);
                return data;
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                return text;
            }
        } catch (error) {
            console.error('API Test Failed:', error);
            return error;
        }
    }
};