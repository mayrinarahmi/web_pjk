/**
 * Trend Analysis JavaScript - FIXED VERSION 2.0
 * Fixed: Race Condition, State Management, Chart Types, Request Handling
 */

// Check if ApexCharts is loaded
if (typeof ApexCharts === 'undefined') {
    console.error('ApexCharts is not loaded! Please include ApexCharts library before this script.');
}

// ========================================
// GLOBAL VARIABLES & STATE MANAGEMENT
// ========================================

let chart = null;
let chartSecondary = null; // ✨ NEW: Secondary chart instance
let searchTimeout = null;
let searchModal = null;

// Request management untuk handle race condition
let currentRequestController = null;
let requestTimestamp = 0;
let pendingRequests = new Map();

// State management object - single source of truth
const appState = {
    view: 'yearly',
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
    yearRange: 3,
    categoryId: '',
    categoryName: '',
    categoryLevel: null,
    isLoading: false,
    selectedYear: null,        // ✨ NEW: Year selected for drill-down
    isDrillDown: false         // ✨ NEW: Drill-down mode flag
};

// Previous state untuk comparison
let previousState = { ...appState };

// API endpoints
const API_BASE = '/api/trend';
const API_OVERVIEW = `${API_BASE}/overview`;
const API_CATEGORY = `${API_BASE}/category`;
const API_SEARCH = `${API_BASE}/search`;
const API_CLEAR_CACHE = `${API_BASE}/clear-cache`;
const API_MONTHLY_DETAIL = `${API_BASE}/category`; // ✨ NEW: Will be used as /category/{id}/monthly/{year}

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing trend analysis v2.0...');
    console.log('Initial state:', appState);
    
    initializeModal();
    initializeEventListeners();
    initializeMonthlyFeatures();
    syncUIWithState();
    loadInitialData();
    
    // Add dev tools if in development
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        addDevelopmentTools();
    }
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
 * Initialize all event listeners with debouncing
 */
function initializeEventListeners() {
    // Year range buttons
    document.querySelectorAll('#yearButtons button').forEach(button => {
        button.addEventListener('click', debounce(handleYearChange, 300));
    });
    
    // View toggle buttons
    document.querySelectorAll('#viewToggle button').forEach(button => {
        button.addEventListener('click', debounce(handleViewToggle, 300));
    });
    
    // Month selector
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.addEventListener('change', debounce(handleMonthChange, 300));
    }
    
    // Modal search input
    const modalSearchInput = document.getElementById('modalSearchInput');
    if (modalSearchInput) {
        modalSearchInput.addEventListener('input', handleModalSearchInput);
    }
    
    // Reset button
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', debounce(handleReset, 300));
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
}

/**
 * Initialize monthly view features
 */
function initializeMonthlyFeatures() {
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.value = appState.month;
    }
    
    // Sync month selector visibility
    syncMonthSelectorVisibility();
}

// ========================================
// STATE MANAGEMENT FUNCTIONS
// ========================================

/**
 * Update application state and trigger necessary updates
 */
function updateState(updates, forceReload = true) {
    console.log('State update requested:', updates);
    
    // Store previous state
    previousState = { ...appState };
    
    // Apply updates
    let hasChanges = false;
    for (const [key, value] of Object.entries(updates)) {
        if (appState[key] !== value) {
            appState[key] = value;
            hasChanges = true;
        }
    }
    
    if (!hasChanges && !forceReload) {
        console.log('No state changes, skipping update');
        return;
    }
    
    console.log('New state:', appState);
    
    // Sync UI with new state
    syncUIWithState();
    
    // Cancel any pending requests
    cancelPendingRequests();
    
    // Reload data if needed
    if (forceReload) {
        if (appState.categoryId) {
            loadChartData('category', appState.categoryId);
        } else {
            loadChartData('overview');
        }
    }
}

/**
 * Sync UI elements with current state
 */
function syncUIWithState() {
    // Year range buttons
    document.querySelectorAll('#yearButtons button').forEach(btn => {
        const isActive = parseInt(btn.dataset.years) === appState.yearRange;
        btn.classList.toggle('btn-primary', isActive);
        btn.classList.toggle('btn-outline-primary', !isActive);
    });
    
    // View toggle buttons
    document.querySelectorAll('#viewToggle button').forEach(btn => {
        const isActive = btn.dataset.view === appState.view;
        btn.classList.toggle('btn-primary', isActive);
        btn.classList.toggle('btn-outline-primary', !isActive);
    });
    
    // Month selector
    const monthSelect = document.getElementById('monthSelect');
    if (monthSelect) {
        monthSelect.value = appState.month;
    }
    
    // Month selector visibility
    syncMonthSelectorVisibility();
    
    // Category title
    updateCategoryTitle();
    
    // Reset button visibility
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.style.display = appState.categoryId ? 'inline-block' : 'none';
    }

    // ✨ NEW: Update breadcrumb visibility
    updateBreadcrumb();
}

/**
 * Sync month selector visibility based on view mode
 */
function syncMonthSelectorVisibility() {
    const monthSelector = document.getElementById('monthSelector');
    const monthlyTable = document.getElementById('monthlyComparisonTable');
    
    if (appState.view === 'monthly') {
        if (monthSelector) monthSelector.style.display = 'block';
        if (monthlyTable) monthlyTable.style.display = 'block';
    } else {
        if (monthSelector) monthSelector.style.display = 'none';
        if (monthlyTable) monthlyTable.style.display = 'none';
    }
}

/**
 * Update category title display
 */
function updateCategoryTitle() {
    const categoryTitle = document.getElementById('categoryTitle');
    const chartTitle = document.getElementById('chartTitle');
    
    if (appState.categoryId && appState.categoryName) {
        const levelBadge = appState.categoryLevel ? 
            `<span class="badge bg-${getLevelColor(appState.categoryLevel)} ms-2">Level ${appState.categoryLevel}</span>` : '';
        const titleHtml = `${appState.categoryName}${levelBadge}`;
        
        if (categoryTitle) categoryTitle.innerHTML = titleHtml;
        if (chartTitle) chartTitle.innerHTML = titleHtml;
    } else {
        const defaultTitle = 'Overview - Semua Kategori';
        if (categoryTitle) categoryTitle.textContent = defaultTitle;
        if (chartTitle) chartTitle.textContent = defaultTitle;
    }
}

// ========================================
// REQUEST MANAGEMENT
// ========================================

/**
 * Cancel all pending requests
 */
function cancelPendingRequests() {
    if (currentRequestController) {
        currentRequestController.abort();
        currentRequestController = null;
    }
    
    // Cancel any other pending requests
    pendingRequests.forEach((controller, id) => {
        controller.abort();
    });
    pendingRequests.clear();
}

/**
 * Set loading state for UI elements
 */
function setLoadingState(isLoading) {
    appState.isLoading = isLoading;
    
    // Show/hide loading indicator
    const loadingEl = document.getElementById('loadingChart');
    if (loadingEl) {
        loadingEl.style.display = isLoading ? 'flex' : 'none';
    }
    
    // Disable/enable controls
    const controls = document.querySelectorAll('#yearButtons button, #viewToggle button, #monthSelect, #resetBtn');
    controls.forEach(el => {
        el.disabled = isLoading;
    });
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Debounce function to limit rapid calls
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
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

// ========================================
// DATA LOADING FUNCTIONS
// ========================================

/**
 * Load initial data
 */
async function loadInitialData() {
    await loadChartData('overview', null, true);
}

/**
 * Main function to load chart data with proper request management
 */
async function loadChartData(type, categoryId = null, noCache = false) {
    console.log('Loading chart data:', {
        type,
        categoryId,
        view: appState.view,
        month: appState.month,
        yearRange: appState.yearRange,
        noCache
    });
    
    // Cancel previous requests
    cancelPendingRequests();
    
    // Create new AbortController
    currentRequestController = new AbortController();
    const thisRequestTimestamp = Date.now();
    requestTimestamp = thisRequestTimestamp;
    
    // Set loading state
    setLoadingState(true);
    
    try {
        // Build URL based on type
        let url;
        if (type === 'overview') {
            url = `${API_OVERVIEW}?years=${appState.yearRange}&view=${appState.view}`;
            if (appState.view === 'monthly' && appState.month) {
                url += `&month=${appState.month}`;
            }
        } else {
            url = `${API_CATEGORY}/${categoryId}?years=${appState.yearRange}&view=${appState.view}`;
            if (appState.view === 'monthly' && appState.month) {
                url += `&month=${appState.month}`;
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
            },
            signal: currentRequestController.signal
        });
        
        // Check if this is still the latest request
        if (thisRequestTimestamp !== requestTimestamp) {
            console.log('Request outdated, ignoring response');
            return;
        }
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('API response:', result);
        
        if (result.success) {
            processChartData(result.data);
        } else {
            showError(result.message || 'Gagal memuat data');
        }
        
    } catch (error) {
        if (error.name === 'AbortError') {
            console.log('Request was cancelled');
        } else {
            console.error('Load data error:', error);
            showError('Terjadi kesalahan: ' + error.message);
        }
    } finally {
        setLoadingState(false);
    }
}

/**
 * Process and render chart data based on current view
 */
function processChartData(data) {
    if (!data) {
        showNoDataMessage();
        return;
    }
    
    // Validate data structure
    if (!data.categories || !data.series) {
        console.warn('Invalid data structure:', data);
        showNoDataMessage();
        return;
    }
    
    // Check if series is empty
    if (data.series.length === 0) {
        showNoDataMessage();
        return;
    }
    
    // Hide error messages
    hideError();
    
    // Render based on view mode
    if (appState.view === 'monthly') {
        renderMonthlyChart(data);
        renderMonthlyTable(data);
        if (data.summary) {
            updateMonthlySummary(data.summary);
        }
    } else {
        renderYearlyChart(data);
        // Hide monthly table in yearly view
        const monthlyTable = document.getElementById('monthlyComparisonTable');
        if (monthlyTable) {
            monthlyTable.style.display = 'none';
        }
    }
    
    // Update summary if exists
    if (data.summary) {
        updateSummary(data.summary);
    }
    
    // Update category info if exists
    if (data.categoryInfo) {
        updateCategoryInfo(data.categoryInfo);
    }
}

// ========================================
// CHART RENDERING FUNCTIONS
// ========================================

/**
 * Determine chart type based on view mode and filters
 */
function determineChartType() {
    if (appState.view === 'yearly') {
        return 'bar'; // Always bar chart for yearly comparison
    } else if (appState.view === 'monthly') {
        // Monthly view: check if specific month is selected
        if (appState.month && appState.month !== '') {
            return 'bar'; // Bar chart for month comparison across years
        } else {
            return 'line'; // Line chart for 12-month trend
        }
    }
    return 'bar'; // Default to bar
}

/**
 * Safely destroy existing chart
 */
function destroyExistingChart() {
    if (chart) {
        try {
            chart.destroy();
            chart = null;
        } catch (e) {
            console.error('Error destroying chart:', e);
            chart = null;
        }
    }
    
    // Clear the container
    const chartContainer = document.querySelector("#trendChart");
    if (chartContainer) {
        chartContainer.innerHTML = '';
    }
}

/**
 * Render chart for yearly view (always bar chart)
 */
function renderYearlyChart(data) {
    console.log('Rendering yearly chart with data:', data);
    
    // Destroy existing chart
    destroyExistingChart();
    
    const chartContainer = document.querySelector("#trendChart");
    if (!chartContainer) {
        console.error('Chart container not found');
        return;
    }
    
    // Chart options for yearly view - ALWAYS BAR CHART
    const options = {
        series: data.series || [],
        chart: {
            type: 'bar',
            height: 450,
            stacked: false,
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            },
            // ✨ NEW: Click handler for drill-down
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    handleBarClick(config.dataPointIndex);
                }
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
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '65%',
                endingShape: 'rounded',
                dataLabels: {
                    position: 'top'
                }
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
        fill: {
            opacity: 1
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
        console.error('Error creating yearly chart:', error);
        showError('Gagal membuat chart: ' + error.message);
    }
}

/**
 * Render monthly chart (bar or line based on filter)
 */
function renderMonthlyChart(data) {
    console.log('Rendering monthly chart with data:', data);
    
    // Destroy existing chart
    destroyExistingChart();
    
    const chartContainer = document.querySelector("#trendChart");
    if (!chartContainer) {
        console.error('Chart container not found');
        return;
    }
    
    // Determine chart type based on whether month filter is active
    const hasMonthFilter = appState.month && appState.month !== '';
    const chartType = hasMonthFilter ? 'bar' : 'line';
    
    console.log('Monthly chart type:', chartType, 'Has filter:', hasMonthFilter);
    
    // Chart options for monthly view
    const options = {
        series: data.series || [],
        chart: {
            type: chartType,
            height: 450,
            toolbar: {
                show: true
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        colors: ['#696cff', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00'],
        stroke: {
            curve: chartType === 'line' ? 'smooth' : 'straight',
            width: chartType === 'line' ? 3 : 1
        },
        markers: {
            size: chartType === 'line' ? 5 : 0,
            hover: {
                size: chartType === 'line' ? 7 : 0
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
        xaxis: {
            categories: data.categories || [],
            title: {
                text: hasMonthFilter ? 'Tahun' : 'Bulan',
                style: {
                    fontSize: '14px',
                    fontWeight: 600
                }
            }
        },
        yaxis: {
            title: {
                text: hasMonthFilter ? 
                    `Penerimaan Bulan ${data.monthName || ''}` : 
                    'Jumlah Penerimaan',
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
        }
    };
    
    try {
        chart = new ApexCharts(chartContainer, options);
        chart.render();
        
        // Calculate growth analysis for monthly data
        if (hasMonthFilter) {
            calculateGrowthAnalysis(data);
        }
    } catch (error) {
        console.error('Error creating monthly chart:', error);
        showError('Gagal membuat chart: ' + error.message);
    }
}

/**
 * Show no data message in chart container
 */
function showNoDataMessage() {
    const chartContainer = document.querySelector("#trendChart");
    if (chartContainer) {
        chartContainer.innerHTML = `
            <div class="text-center py-5">
                <i class="bx bx-bar-chart-alt-2 bx-lg text-muted mb-3 d-block"></i>
                <h5 class="text-muted">Tidak Ada Data</h5>
                <p class="text-muted">Tidak ada data penerimaan untuk periode yang dipilih</p>
                <button class="btn btn-primary mt-3" onclick="handleReloadData()">
                    <i class="bx bx-refresh me-1"></i> Muat Ulang
                </button>
            </div>
        `;
    }
    
    // Reset summary cards
    resetGrowthAnalysis();
}

// ========================================
// EVENT HANDLERS
// ========================================

/**
 * Handle view toggle between yearly and monthly
 */
function handleViewToggle(event) {
    const button = event.target.closest('button');
    if (!button) return;
    
    const view = button.dataset.view;
    
    if (view === appState.view) {
        console.log('View already active:', view);
        return;
    }
    
    console.log('Toggling view to:', view);
    
    // Update state with new view
    updateState({
        view: view,
        month: view === 'monthly' ? appState.month : '' // Keep month for monthly, clear for yearly
    });
}

/**
 * Handle month change
 */
function handleMonthChange(event) {
    const month = parseInt(event.target.value);
    
    if (month === appState.month) {
        console.log('Month already selected:', month);
        return;
    }
    
    console.log('Month changed to:', month);
    updateState({ month: month });
}

/**
 * Handle year range change
 */
function handleYearChange(event) {
    const button = event.target.closest('button');
    if (!button) return;
    
    const years = parseInt(button.dataset.years);
    
    if (years === appState.yearRange) {
        console.log('Year range already selected:', years);
        return;
    }
    
    console.log('Year range changed to:', years);
    updateState({ yearRange: years });
}

/**
 * Handle reset button click
 */
function handleReset() {
    console.log('Resetting to overview...');
    
    updateState({
        categoryId: '',
        categoryName: '',
        categoryLevel: null
    });
}

/**
 * Handle reload data button
 */
function handleReloadData() {
    console.log('Reloading data...');
    
    if (appState.categoryId) {
        loadChartData('category', appState.categoryId, true);
    } else {
        loadChartData('overview', null, true);
    }
}

/**
 * Handle keyboard shortcuts
 */
function handleKeyboardShortcuts(e) {
    // Ctrl/Cmd + K for search
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchBtn = document.getElementById('searchModalBtn');
        if (searchBtn) searchBtn.click();
    }
    
    // Ctrl/Cmd + R for reset (if category is selected)
    if ((e.ctrlKey || e.metaKey) && e.key === 'r' && appState.categoryId) {
        e.preventDefault();
        handleReset();
    }
    
    // Ctrl/Cmd + Shift + C for clear cache (dev only)
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'C') {
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            e.preventDefault();
            clearCache();
        }
    }
}

// ========================================
// SEARCH FUNCTIONS
// ========================================

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
 * Search categories via API
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
 * Display search results in modal
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
    
    // Group results by level
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
            selectCategoryFromModal({
                id: this.dataset.id,
                nama: this.dataset.nama,
                level: parseInt(this.dataset.level)
            });
        });
    });
}

/**
 * Select category from modal
 */
function selectCategoryFromModal(category) {
    console.log('Category selected:', category);
    
    // Update state
    updateState({
        categoryId: category.id,
        categoryName: category.nama,
        categoryLevel: category.level
    });
    
    // Hide modal
    if (searchModal) {
        searchModal.hide();
    }
}

/**
 * Update category info from API response
 */
function updateCategoryInfo(categoryInfo) {
    if (categoryInfo) {
        updateState({
            categoryName: categoryInfo.nama,
            categoryLevel: categoryInfo.level
        }, false); // Don't reload data
    }
}

// ========================================
// ANALYSIS FUNCTIONS
// ========================================

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
 * Render monthly comparison table
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
 * Update summary cards and insights
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
 * Reset growth analysis display
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

// ========================================
// UTILITY & HELPER FUNCTIONS
// ========================================

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
    setLoadingState(true);
}

function hideLoading() {
    setLoadingState(false);
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

// ========================================
// DEVELOPMENT TOOLS
// ========================================

/**
 * Add development tools for testing
 */
function addDevelopmentTools() {
    // Add clear cache button
    const headerRow = document.querySelector('.row.mb-4 .col-md-4.text-end');
    if (headerRow) {
        const clearCacheBtn = document.createElement('button');
        clearCacheBtn.className = 'btn btn-warning ms-2';
        clearCacheBtn.innerHTML = '<i class="bx bx-refresh me-1"></i> Clear Cache';
        clearCacheBtn.onclick = clearCache;
        headerRow.appendChild(clearCacheBtn);
    }
    
    // Export debug functions
    window.trendAnalysisDebug = {
        getState: () => appState,
        getPreviousState: () => previousState,
        getCurrentChart: () => chart,
        forceReload: () => loadChartData('overview', null, true),
        testRaceCondition: async () => {
            // Test rapid filter changes
            for (let i = 0; i < 5; i++) {
                setTimeout(() => {
                    updateState({ yearRange: (i % 3) + 1 });
                }, i * 100);
            }
        },
        testChartTypes: () => {
            console.log('Testing chart type determination:');
            console.log('Yearly:', determineChartType());
            appState.view = 'monthly';
            appState.month = 9;
            console.log('Monthly with filter:', determineChartType());
            appState.month = '';
            console.log('Monthly without filter:', determineChartType());
        }
    };
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


function handleBarClick(yearIndex) {
    console.log('Bar clicked at index:', yearIndex);
    
    // Only allow drill-down when category is selected
    if (!appState.categoryId) {
        console.log('No category selected, ignoring click');
        return;
    }
    
    // Prevent drill-down in monthly view
    if (appState.view === 'monthly') {
        console.log('Already in monthly view, ignoring click');
        return;
    }
    
    // Get the year from categories
    const chartContainer = document.querySelector("#trendChart");
    if (!chart || !chart.w || !chart.w.config || !chart.w.config.xaxis) {
        console.error('Chart not properly initialized');
        return;
    }
    
    const categories = chart.w.config.xaxis.categories;
    if (!categories || yearIndex >= categories.length) {
        console.error('Invalid year index');
        return;
    }
    
    const selectedYear = parseInt(categories[yearIndex]);
    
    console.log('Year selected for drill-down:', selectedYear);
    
    // Update state
    updateState({
        selectedYear: selectedYear,
        isDrillDown: true
    }, false); // Don't reload main chart
    
    // Load monthly detail
    loadMonthlyDetail(appState.categoryId, selectedYear);
    
    // Highlight selected bar
    highlightSelectedBar(yearIndex);
}

async function loadMonthlyDetail(categoryId, year) {
    console.log('Loading monthly detail:', { categoryId, year });
    
    try {
        setLoadingState(true);
        
        const url = `${API_MONTHLY_DETAIL}/${categoryId}/monthly/${year}`;
        console.log('Fetching from:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Monthly detail response:', result);
        
        if (result.success && result.data) {
            renderMonthlyDetailChart(result.data);
            
            if (result.data.summary) {
                updateMonthlyCards(result.data.summary);
            }
            
            if (result.data.tableData) {
                updateMonthlyTable(result.data.tableData);
            }
            
            showSecondaryChart();
            
            const yearTitle = document.getElementById('selectedYearTitle');
            if (yearTitle) {
                yearTitle.textContent = year;
            }
            
        } else {
            showError(result.message || 'Gagal memuat data monthly detail');
        }
        
    } catch (error) {
        console.error('Error loading monthly detail:', error);
        showError('Terjadi kesalahan: ' + error.message);
    } finally {
        setLoadingState(false);
    }
}

/**
 * Render monthly detail chart (line chart)
 */
function renderMonthlyDetailChart(data) {
    console.log('Rendering monthly detail chart:', data);
    
    // Destroy existing secondary chart
    destroySecondaryChart();
    
    const chartContainer = document.querySelector("#monthlyDetailChart");
    if (!chartContainer) {
        console.error('Monthly detail chart container not found');
        return;
    }
    
    // Chart options for monthly detail - LINE CHART
    const options = {
        series: data.series || [],
        chart: {
            type: 'line',
            height: 380,
            toolbar: {
                show: true,
                tools: {
                    download: true,
                    zoom: true,
                    zoomin: true,
                    zoomout: true,
                    pan: false,
                    reset: true
                }
            },
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800
            }
        },
        colors: ['#667eea', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00'],
        stroke: {
            curve: 'smooth',
            width: 3
        },
        markers: {
            size: 6,
            colors: ['#667eea'],
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: {
                size: 8
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: data.categories || [],
            title: {
                text: 'Bulan',
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
        }
    };
    
    try {
        chartSecondary = new ApexCharts(chartContainer, options);
        chartSecondary.render();
        console.log('Monthly detail chart rendered successfully');
    } catch (error) {
        console.error('Error rendering monthly detail chart:', error);
        showError('Gagal membuat chart: ' + error.message);
    }
}

/**
 * Destroy secondary chart
 */
function destroySecondaryChart() {
    if (chartSecondary) {
        try {
            chartSecondary.destroy();
            chartSecondary = null;
            console.log('Secondary chart destroyed');
        } catch (e) {
            console.error('Error destroying secondary chart:', e);
            chartSecondary = null;
        }
    }
}

/**
 * Show secondary chart with animation
 */
function showSecondaryChart() {
    const container = document.getElementById('trendChartSecondary');
    if (container) {
        container.style.display = 'block';
        
        // Smooth scroll to secondary chart
        setTimeout(() => {
            container.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
        }, 300);
        
        console.log('Secondary chart shown');
    }
}

/**
 * Hide secondary chart
 */
function hideSecondaryChart() {
    const container = document.getElementById('trendChartSecondary');
    if (container) {
        container.style.display = 'none';
    }
    
    // Destroy chart to free memory
    destroySecondaryChart();
    
    // Reset state
    updateState({
        selectedYear: null,
        isDrillDown: false
    }, false); // Don't reload
    
    // Remove bar highlight
    unhighlightBars();
    
    console.log('Secondary chart hidden');
}

/**
 * Update cards for monthly view
 */
function updateMonthlyCards(summary) {
    console.log('Updating monthly cards:', summary);
    
    if (!summary) return;
    
    // Card 1: Total (yearly)
    const totalGrowthEl = document.getElementById('totalGrowthValue');
    if (totalGrowthEl) {
        totalGrowthEl.innerHTML = `<span class="value-text">${formatCurrency(summary.total)}</span>`;
    }
    
    // Card 2: Average Monthly
    const avgGrowthEl = document.getElementById('avgGrowthValue');
    if (avgGrowthEl) {
        avgGrowthEl.innerHTML = `<span class="value-text">${formatCurrency(summary.avgMonthly)}</span>`;
    }
    
    // Card 3: Peak Month
    const bestPerformerEl = document.getElementById('bestPerformerValue');
    if (bestPerformerEl) {
        bestPerformerEl.innerHTML = `<span class="value-text">${summary.peakMonth.name}<br><small>${formatCurrency(summary.peakMonth.value)}</small></span>`;
    }
    
    // Card 4: Trend Direction
    const statusEl = document.getElementById('trendStatusValue');
    if (statusEl) {
        let trendText = 'Stabil';
        let trendIcon = '→';
        
        if (summary.trendDirection === 'increasing') {
            trendText = `Naik ${summary.monthlyGrowth.toFixed(1)}%`;
            trendIcon = '↑';
        } else if (summary.trendDirection === 'decreasing') {
            trendText = `Turun ${Math.abs(summary.monthlyGrowth).toFixed(1)}%`;
            trendIcon = '↓';
        }
        
        statusEl.innerHTML = `<span class="value-text">${trendText} ${trendIcon}</span>`;
    }
}

/**
 * Update table for monthly data
 */
function updateMonthlyTable(tableData) {
    console.log('Updating monthly table:', tableData);
    
    const tbody = document.getElementById('growthTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    if (!tableData || tableData.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    Tidak ada data bulanan
                </td>
            </tr>
        `;
        return;
    }
    
    tableData.forEach(row => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${row.monthName}</strong></td>
            <td class="text-end">${formatCurrency(row.value)}</td>
            <td class="text-end ${getGrowthClass(row.growth)}">${row.growth.toFixed(1)}%</td>
            <td class="text-center">${getTrendBadge(row.trend)}</td>
            <td>${row.description}</td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Highlight selected bar
 */
function highlightSelectedBar(yearIndex) {
    // This would require ApexCharts API manipulation
    // For now, we can update via state
    console.log('Highlighting bar at index:', yearIndex);
    
    // Could be implemented with ApexCharts update methods
    // chart.updateOptions({ ... })
}

/**
 * Remove bar highlights
 */
function unhighlightBars() {
    console.log('Removing bar highlights');
    // Reset any highlights
}

/**
 * Update breadcrumb navigation
 */
function updateBreadcrumb() {
    const breadcrumbCategory = document.getElementById('breadcrumbCategory');
    const breadcrumbCategoryLink = document.getElementById('breadcrumbCategoryLink');
    const breadcrumbYear = document.getElementById('breadcrumbYear');
    
    if (!breadcrumbCategory || !breadcrumbCategoryLink || !breadcrumbYear) {
        return;
    }
    
    // Show/hide based on state
    if (appState.categoryId) {
        breadcrumbCategory.style.display = 'list-item';
        breadcrumbCategoryLink.textContent = appState.categoryName || 'Kategori';
    } else {
        breadcrumbCategory.style.display = 'none';
    }
    
    if (appState.isDrillDown && appState.selectedYear) {
        breadcrumbYear.style.display = 'list-item';
        breadcrumbYear.textContent = appState.selectedYear;
    } else {
        breadcrumbYear.style.display = 'none';
    }
}

/**
 * Back to category view (from drill-down)
 */
function backToCategory() {
    console.log('Back to category view');
    
    // Hide secondary chart
    hideSecondaryChart();
    
    // State already updated in hideSecondaryChart
}

/**
 * Reset to overview (clear all filters)
 */
function resetToOverview() {
    console.log('Reset to overview');
    
    // Hide secondary chart first
    if (appState.isDrillDown) {
        hideSecondaryChart();
    }
    
    // Then reset category
    if (appState.categoryId) {
        handleReset();
    }
}

// Make functions globally accessible
window.hideSecondaryChart = hideSecondaryChart;
window.backToCategory = backToCategory;
window.resetToOverview = resetToOverview;


// Log initialization complete
console.log('Trend Analysis v2.0 initialized successfully');
console.log('Debug tools available at window.trendAnalysisDebug');