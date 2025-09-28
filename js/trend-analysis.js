/**
 * Trend Analysis JavaScript - FIXED VERSION
 * Support all levels and better error handling
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
 
// API endpoints
const API_BASE = '/api/trend';
const API_OVERVIEW = `${API_BASE}/overview`;
const API_CATEGORY = `${API_BASE}/category`;
const API_SEARCH = `${API_BASE}/search`;
const API_CLEAR_CACHE = `${API_BASE}/clear-cache`;
 
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
 
    // Add clear cache button if in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        addClearCacheButton();
    }
});
 
/**
 * Add clear cache button for development
 */
function addClearCacheButton() {
    const headerRow = document.querySelector('.row.mb-4 .col-md-4.text-end');
    if (headerRow) {
        const clearCacheBtn = document.createElement('button');
        clearCacheBtn.className = 'btn btn-warning ms-2';
        clearCacheBtn.innerHTML = '<i class="bx bx-refresh me-1"></i> Clear Cache';
        clearCacheBtn.onclick = clearCache;
        headerRow.appendChild(clearCacheBtn);
    }
}
 
/**
 * Clear cache function
 */
async function clearCache() {
    try {
        const response = await fetch(API_CLEAR_CACHE, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        });
 
        const result = await response.json();
 
        if (result.success) {
            alert('Cache berhasil dihapus! Halaman akan dimuat ulang.');
            window.location.reload();
        } else {
            alert('Gagal menghapus cache: ' + result.message);
        }
    } catch (error) {
        console.error('Error clearing cache:', error);
        alert('Error: ' + error.message);
    }
}
 
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
 * Load initial data with nocache option
 */
async function loadInitialData() {
    // Add nocache parameter for first load to ensure fresh data
    await loadChartData('overview', null, true);
}
 
/**
 * Load chart data from API
 */
async function loadChartData(type, categoryId = null, noCache = false) {
    console.log('Loading chart data:', type, categoryId, 'View:', currentView, 'NoCache:', noCache);
 
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
 
        // Add nocache parameter if needed
        if (noCache) {
            url += '&nocache=1';
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
                showNoDataMessage();
                return;
            }
 
            // Check if we have valid chart data
            if (!result.data.categories || !result.data.series) {
                console.warn('Invalid data structure:', result.data);
                result.data.categories = result.data.categories || [];
                result.data.series = result.data.series || [];
            }
 
            // Check if series is empty
            if (result.data.series.length === 0) {
                showNoDataMessage();
                hideLoading();
                return;
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
 
            // Update category info if exists
            if (result.data.categoryInfo) {
                updateCategoryInfo(result.data.categoryInfo);
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
 * Show no data message
 */
function showNoDataMessage() {
    const chartContainer = document.querySelector("#trendChart");
    if (chartContainer) {
        chartContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="bx bx-bar-chart-alt-2 bx-lg text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Tidak Ada Data</h5>
                <p class="text-muted">Tidak ada data penerimaan untuk periode yang dipilih</p>
                <button class="btn btn-primary mt-3" onclick="loadChartData('overview', null, true)">
                    <i class="bx bx-refresh me-1"></i> Muat Ulang
                </button>
            </div>
        `;
    }
 
    // Reset summary cards
    resetGrowthAnalysis();
}
 
/**
 * Update category info display
 */
function updateCategoryInfo(categoryInfo) {
    if (categoryInfo) {
        const levelBadge = `<span class="badge bg-info ms-2">Level ${categoryInfo.level}</span>`;
 
        const categoryTitle = document.getElementById('categoryTitle');
        if (categoryTitle) {
            categoryTitle.innerHTML = `${categoryInfo.nama} ${levelBadge}`;
        }
 
        const chartTitle = document.getElementById('chartTitle');
        if (chartTitle) {
            chartTitle.innerHTML = `${categoryInfo.nama} ${levelBadge}`;
        }
    }
}
 
/**
 * Render chart for yearly view
 */
function renderChart(data) {
    console.log('Rendering chart with data:', data);
 
    // Validasi data
    if (!data || !data.series || !data.categories) {
        console.error('Invalid chart data');
        showNoDataMessage();
        return;
    }
 
    // Check if series is empty
    if (data.series.length === 0) {
        showNoDataMessage();
        return;
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
 
    // Determine chart type based on number of series
    const chartType = data.series.length > 5 ? 'bar' : 'line';
 
    // Chart options
    const options = {
        series: data.series || [],
        chart: {
            type: chartType,
            height: 450,
            stacked: false,
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
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00', '#8592a3', '#f97316', '#06b6d4', '#ec4899', '#10b981'],
        stroke: {
            curve: 'smooth',
            width: chartType === 'line' ? 3 : 1
        },
        markers: {
            size: chartType === 'line' ? 5 : 0,
            hover: {
                size: chartType === 'line' ? 7 : 0
            }
        },
        dataLabels: {
            enabled: false
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '65%',
                endingShape: 'rounded'
            }
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
                    return formatCurrencyShort(val);
                }
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
            floating: false,
            offsetY: -10,
            itemMargin: {
                horizontal: 10,
                vertical: 5
            }
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
        }
    };
 
    // Create new chart
    try {
        chart = new ApexCharts(chartContainer, options);
        chart.render();
 
        // Calculate growth analysis
        calculateGrowthAnalysis(data);
    } catch (error) {
        console.error('Error creating chart:', error);
        showError('Gagal membuat chart: ' + error.message);
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
                <p class="mt-2">Mulai mengetik untuk mencari kategori (minimal 2 karakter)</p>
            </div>
        `;
        return;
    }
 
    document.getElementById('modalSearchResults').innerHTML = `
        <div class="text-center py-4">
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
 * Search categories - now supports all levels
 */
async function searchCategoriesModal(searchTerm) {
    try {
        const response = await fetch(`${API_SEARCH}?q=${encodeURIComponent(searchTerm)}`);
        const result = await response.json();
 
        console.log('Search results:', result);
 
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
 * Display search results with level information
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
 
    // Group results by level for better organization
    const groupedByLevel = {};
    results.forEach(result => {
        if (!groupedByLevel[result.level]) {
            groupedByLevel[result.level] = [];
        }
        groupedByLevel[result.level].push(result);
    });
 
    let html = '';
 
    // Display results grouped by level
    Object.keys(groupedByLevel).sort().forEach(level => {
        html += `<div class="mb-3">`;
        html += `<h6 class="text-muted mb-2">Level ${level}</h6>`;
 
        groupedByLevel[level].forEach(result => {
            const levelColor = getLevelColor(result.level);
            html += `
                <div class="search-result-item mb-2" data-id="${result.id}" data-nama="${result.nama}" data-level="${result.level}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="result-kode">${result.kode}</div>
                            <div class="result-nama">${result.nama}</div>
                        </div>
                        <span class="badge bg-${levelColor}">Level ${result.level}</span>
                    </div>
                </div>
            `;
        });
 
        html += `</div>`;
    });
 
    container.innerHTML = html;
 
    // Add click handlers
    container.querySelectorAll('.search-result-item').forEach(item => {
        item.addEventListener('click', function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const level = this.dataset.level;
            selectCategoryFromModal({ id, nama, level });
        });
    });
}
 
/**
 * Get color for level badge
 */
function getLevelColor(level) {
    const colors = {
        1: 'primary',
        2: 'success',
        3: 'info',
        4: 'warning',
        5: 'secondary',
        6: 'danger'
    };
    return colors[level] || 'secondary';
}
 
/**
 * Select category from modal
 */
function selectCategoryFromModal(category) {
    console.log('Category selected:', category);
 
    currentCategoryId = category.id;
 
    // Update UI with level information
    const levelBadge = `<span class="badge bg-${getLevelColor(category.level)} ms-2">Level ${category.level}</span>`;
 
    const categoryTitle = document.getElementById('categoryTitle');
    if (categoryTitle) {
        categoryTitle.innerHTML = `${category.nama} ${levelBadge}`;
    }
 
    const chartTitle = document.getElementById('chartTitle');
    if (chartTitle) {
        chartTitle.innerHTML = `${category.nama} ${levelBadge}`;
    }
 
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.style.display = 'inline-block';
    }
 
    // Hide modal
    if (searchModal) {
        searchModal.hide();
    }
 
    // Load category data with nocache for fresh data
    loadChartData('category', category.id, true);
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
 
    loadChartData('overview', null, true);
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
 * Render monthly chart
 */
function renderMonthlyChart(data) {
    console.log('Rendering monthly chart:', data);
 
    if (!data || !data.series || !data.categories) {
        showNoDataMessage();
        return;
    }
 
    if (data.series.length === 0) {
        showNoDataMessage();
        return;
    }
 
    const chartContainer = document.querySelector("#trendChart");
    if (!chartContainer) {
        return;
    }
 
    if (chart) {
        chart.destroy();
        chart = null;
    }
 
    chartContainer.innerHTML = '';
 
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
                    return formatCurrencyShort(val);
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
 
    try {
        chart = new ApexCharts(chartContainer, options);
        chart.render();
    } catch (error) {
        console.error('Error creating monthly chart:', error);
    }
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
    let tableData = [];
    let overallGrowth = 0;
    let bestPerformer = { name: '-', value: 0 };
 
    // Calculate total values per year
    const yearlyTotals = {};
    years.forEach(year => {
        yearlyTotals[year] = 0;
    });
 
    data.series.forEach(serie => {
        serie.data.forEach((value, index) => {
            if (years[index]) {
                yearlyTotals[years[index]] += value || 0;
            }
        });
    });
 
    // Convert to array for easier processing
    const totalsArray = years.map(year => yearlyTotals[year]);
 
    // Calculate growth between first and last year
    if (totalsArray.length >= 2) {
        const firstValue = totalsArray[0];
        const lastValue = totalsArray[totalsArray.length - 1];
 
        if (firstValue > 0) {
            overallGrowth = ((lastValue - firstValue) / firstValue) * 100;
        } else if (lastValue > 0) {
            overallGrowth = 100;
        }
    }
 
    // Calculate year-over-year growth
    for (let i = 0; i < years.length; i++) {
        const value = totalsArray[i];
        let growth = 0;
        let trend = 'stable';
        let description = 'Tahun dasar';
 
        if (i > 0) {
            const prevValue = totalsArray[i - 1];
            if (prevValue > 0) {
                growth = ((value - prevValue) / prevValue) * 100;
            } else if (value > 0) {
                growth = 100;
            }
 
            trend = growth > 0 ? 'up' : growth < 0 ? 'down' : 'stable';
            description = getGrowthDescription(growth);
        }
 
        // Track best performing year
        if (value > bestPerformer.value) {
            bestPerformer = { name: years[i], value: value };
        }
 
        tableData.push({
            year: years[i],
            value: value,
            growth: growth,
            trend: trend,
            description: description
        });
    }
 
    // Calculate average growth (CAGR)
    let avgGrowth = 0;
    if (totalsArray[0] > 0 && totalsArray.length > 1) {
        const years = totalsArray.length - 1;
        avgGrowth = (Math.pow(totalsArray[totalsArray.length - 1] / totalsArray[0], 1/years) - 1) * 100;
    }
 
    // Update summary cards
    updateSummaryCards({
        totalGrowth: overallGrowth,
        avgGrowth: avgGrowth,
        bestPerformer: bestPerformer.name,
        trendStatus: determineTrendStatus(overallGrowth)
    });
 
    // Update growth table
    updateGrowthTable(tableData);
}
 
/**
 * Update growth table
 */
function updateGrowthTable(data) {
    const tbody = document.getElementById('growthTableBody');
    if (!tbody) return;
 
    tbody.innerHTML = '';
 
    data.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.year}</strong></td>
            <td class="text-end">${formatCurrency(row.value)}</td>
            <td class="text-end ${getGrowthClass(row.growth)}">${formatGrowthValue(row.growth)}</td>
            <td class="text-center">${getTrendBadge(row.trend)}</td>
            <td>${row.description}</td>
        `;
        tbody.appendChild(tr);
    });
}
 
/**
 * Render monthly table
 */
function renderMonthlyTable(data) {
    console.log('Rendering monthly table:', data);
 
    const comparisonTable = document.getElementById('monthlyComparisonTable');
    if (!comparisonTable) return;
 
    const monthName = data.monthName || 'Bulan';
    const monthNameEl = document.getElementById('comparisonMonthName');
    if (monthNameEl) {
        monthNameEl.textContent = monthName;
    }
 
    const tableHeader = document.getElementById('monthlyTableHeader');
    const tableBody = document.getElementById('monthlyTableBody');
 
    if (!tableHeader || !tableBody) return;
 
    // Build header
    let headerHTML = '<tr><th>Kategori</th>';
    data.categories.forEach(year => {
        headerHTML += `<th class="text-end">${year}</th>`;
    });
    headerHTML += '<th class="text-end">Growth</th></tr>';
    tableHeader.innerHTML = headerHTML;
 
    // Build body
    tableBody.innerHTML = '';
    data.series.forEach(serie => {
        const tr = document.createElement('tr');
        let rowHTML = `<td>${serie.name}</td>`;
 
        serie.data.forEach(value => {
            rowHTML += `<td class="text-end">${formatCurrency(value)}</td>`;
        });
 
        // Calculate growth
        let growth = 0;
        if (serie.data.length >= 2) {
            const first = serie.data[0];
            const last = serie.data[serie.data.length - 1];
            if (first > 0) {
                growth = ((last - first) / first) * 100;
            }
        }
 
        rowHTML += `<td class="text-end ${getGrowthClass(growth)}">${formatGrowthValue(growth)}</td>`;
        tr.innerHTML = rowHTML;
        tableBody.appendChild(tr);
    });
}
 
/**
 * Update summary
 */
function updateSummary(summary) {
    console.log('Updating summary:', summary);
 
    if (!summary) return;
 
    // Update growth insights
    const insightsContainer = document.getElementById('growthInsights');
    if (insightsContainer) {
        let insightsHTML = '';
 
        // Top performers
        if (summary.top_performers && summary.top_performers.length > 0) {
            insightsHTML += '<div class="insight-card success mb-2">';
            insightsHTML += '<i class="bx bx-trending-up"></i>';
            insightsHTML += '<div>';
            insightsHTML += '<strong>Top Performers:</strong><br>';
            summary.top_performers.forEach((item, index) => {
                if (index < 3) {
                    insightsHTML += `${index + 1}. ${item.nama} (${item.growth.toFixed(1)}%)<br>`;
                }
            });
            insightsHTML += '</div></div>';
        }
 
        // Declining categories
        if (summary.declining_categories && summary.declining_categories.length > 0) {
            insightsHTML += '<div class="insight-card warning">';
            insightsHTML += '<i class="bx bx-trending-down"></i>';
            insightsHTML += '<div>';
            insightsHTML += '<strong>Perlu Perhatian:</strong><br>';
            summary.declining_categories.forEach((item, index) => {
                if (index < 3) {
                    insightsHTML += `${item.nama} (${item.growth.toFixed(1)}%)<br>`;
                }
            });
            insightsHTML += '</div></div>';
        }
 
        insightsContainer.innerHTML = insightsHTML;
    }
}
 
/**
 * Update monthly summary
 */
function updateMonthlySummary(summary) {
    console.log('Updating monthly summary:', summary);
 
    if (!summary) return;
 
    // Similar to updateSummary but for monthly data
    updateSummary(summary);
}
 
/**
 * Update summary cards
 */
function updateSummaryCards(data) {
    // Total Growth
    const totalGrowthEl = document.getElementById('totalGrowthValue');
    if (totalGrowthEl) {
        const icon = data.totalGrowth > 0 ? '↑' : data.totalGrowth < 0 ? '↓' : '→';
        totalGrowthEl.innerHTML = `<span class="value-text">${data.totalGrowth.toFixed(1)}% ${icon}</span>`;
    }
 
    // Average Growth
    const avgGrowthEl = document.getElementById('avgGrowthValue');
    if (avgGrowthEl) {
        const icon = data.avgGrowth > 0 ? '↑' : data.avgGrowth < 0 ? '↓' : '→';
        avgGrowthEl.innerHTML = `<span class="value-text">${data.avgGrowth.toFixed(1)}% ${icon}</span>`;
    }
 
    // Best Performer
    const bestPerformerEl = document.getElementById('bestPerformerValue');
    if (bestPerformerEl) {
        bestPerformerEl.innerHTML = `<span class="value-text">${data.bestPerformer}</span>`;
    }
 
    // Trend Status
    const statusEl = document.getElementById('trendStatusValue');
    if (statusEl) {
        statusEl.innerHTML = `<span class="value-text">${data.trendStatus}</span>`;
    }
}
 
/**
 * Reset growth analysis
 */
function resetGrowthAnalysis() {
    updateSummaryCards({
        totalGrowth: 0,
        avgGrowth: 0,
        bestPerformer: '-',
        trendStatus: '<span class="text-muted">-</span>'
    });
 
    const tbody = document.getElementById('growthTableBody');
    if (tbody) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-5">
                    <i class="bx bx-bar-chart-alt-2 bx-lg mb-3 d-block"></i>
                    Tidak ada data untuk ditampilkan
                </td>
            </tr>
        `;
    }
}
 
/**
 * Helper functions
 */
function formatGrowthValue(growth) {
    return `${growth.toFixed(1)}%`;
}
 
function getGrowthClass(growth) {
    if (growth > 0) return 'growth-positive text-success';
    if (growth < 0) return 'growth-negative text-danger';
    return 'growth-neutral text-muted';
}
 
function getTrendBadge(trend) {
    if (trend === 'up') {
        return '<span class="trend-badge trend-up"><i class="bx bx-trending-up"></i> Naik</span>';
    } else if (trend === 'down') {
        return '<span class="trend-badge trend-down"><i class="bx bx-trending-down"></i> Turun</span>';
    }
    return '<span class="trend-badge trend-stable"><i class="bx bx-minus"></i> Stabil</span>';
}
 
function determineTrendStatus(growth) {
    if (growth > 10) return '<span class="text-success fw-bold">Sangat Baik</span>';
    if (growth > 0) return '<span class="text-info fw-bold">Positif</span>';
    if (growth === 0) return '<span class="text-muted">Stabil</span>';
    if (growth > -10) return '<span class="text-warning fw-bold">Perlu Perhatian</span>';
    return '<span class="text-danger fw-bold">Kritis</span>';
}
 
function getGrowthDescription(growth) {
    if (growth > 20) return 'Pertumbuhan sangat tinggi';
    if (growth > 10) return 'Pertumbuhan baik';
    if (growth > 0) return 'Pertumbuhan moderat';
    if (growth === 0) return 'Tidak ada perubahan';
    if (growth > -10) return 'Penurunan ringan';
    return 'Penurunan signifikan';
}
 
function formatCurrency(value) {
    if (!value || value === 0) return 'Rp 0';
 
    if (value >= 1000000000000) { // Trilliun
        return 'Rp ' + (value / 1000000000000).toFixed(2).replace('.', ',') + ' T';
    } else if (value >= 1000000000) { // Milliar
        return 'Rp ' + (value / 1000000000).toFixed(2).replace('.', ',') + ' M';
    } else if (value >= 1000000) { // Juta
        return 'Rp ' + (value / 1000000).toFixed(2).replace('.', ',') + ' Jt';
    } else if (value >= 1000) { // Ribu
        return 'Rp ' + (value / 1000).toFixed(2).replace('.', ',') + ' Rb';
    }
 
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
}
 
function formatCurrencyShort(val) {
    if (val === 0) return 'Rp 0';
    if (val >= 1000000000) {
        return 'Rp ' + (val / 1000000000).toFixed(1) + ' M';
    } else if (val >= 1000000) {
        return 'Rp ' + (val / 1000000).toFixed(1) + ' Jt';
    } else if (val >= 1000) {
        return 'Rp ' + (val / 1000).toFixed(1) + ' Rb';
    }
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
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
        search: API_SEARCH,
        clearCache: API_CLEAR_CACHE
    }),
    testAPI: async () => {
        const url = `${API_OVERVIEW}?years=${currentYearRange}&view=${currentView}&nocache=1`;
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
    },
    testSearch: async (query) => {
        const url = `${API_SEARCH}?q=${encodeURIComponent(query)}`;
        console.log('Testing search:', url);
        try {
            const response = await fetch(url);
            const result = await response.json();
            console.log('Search results:', result);
            return result;
        } catch (error) {
            console.error('Search test failed:', error);
            return error;
        }
    },
    testCategory: async (categoryId) => {
        const url = `${API_CATEGORY}/${categoryId}?years=${currentYearRange}&view=${currentView}`;
        console.log('Testing category:', url);
        try {
            const response = await fetch(url);
            const result = await response.json();
            console.log('Category data:', result);
            return result;
        } catch (error) {
            console.error('Category test failed:', error);
            return error;
        }
    },
    forceReload: () => {
        console.log('Force reloading with no cache...');
        loadChartData('overview', null, true);
    },
    checkViews: async () => {
        console.log('Checking database views...');
        const views = [
            'v_penerimaan_yearly',
            'v_penerimaan_monthly',
            'v_monthly_growth_rate',
            'v_growth_rate',
            'v_trend_summary'
        ];
 
        console.log('Expected views:', views);
        console.log('Check these views exist in your database');
        return views;
    },
    reloadWithNoCache: () => {
        loadChartData('overview', null, true);
    }
};
 
// Auto-initialize on page load
console.log('Trend Analysis initialized. Use window.trendAnalysisDebug for debugging');
 
// Export individual functions for testing
window.trendAnalysisFunctions = {
    formatCurrency,
    formatCurrencyShort,
    formatGrowthValue,
    getGrowthClass,
    getTrendBadge,
    determineTrendStatus,
    getGrowthDescription,
    getLevelColor,
    calculateGrowthAnalysis,
    updateGrowthTable,
    updateSummaryCards,
    resetGrowthAnalysis
};
 
// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K untuk search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchBtn = document.getElementById('searchModalBtn');
        if (searchBtn) searchBtn.click();
    }
 
    // Ctrl/Cmd + R untuk reset (jika ada category selected)
    if ((e.ctrlKey || e.metaKey) && e.key === 'r' && currentCategoryId) {
        e.preventDefault();
        handleReset();
    }
 
    // Ctrl/Cmd + Shift + C untuk clear cache (dev only)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            e.preventDefault();
            clearCache();
        }
    }
});