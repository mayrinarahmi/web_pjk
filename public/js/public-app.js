/**
 * ==========================================
 * PUBLIC DASHBOARD - JAVASCRIPT
 * ==========================================
 * Handle interactions, animations, and utilities
 * for Public Dashboard Pendapatan Daerah
 */

(function() {
    'use strict';

    // ==========================================
    // CONFIGURATION
    // ==========================================
    const CONFIG = {
        navbar: {
            scrollThreshold: 50,
            scrolledClass: 'scrolled'
        },
        counter: {
            duration: 2000,
            increment: 50
        },
        chart: {
            defaultColors: ['#667eea', '#71dd37', '#ff3e1d', '#03c3ec', '#ffab00']
        }
    };

    // ==========================================
    // DOM READY
    // ==========================================
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Public Dashboard initialized');
        
        initNavbar();
        initCounters();
        initSmoothScroll();
        initTooltips();
        initChartDefaults();
        
        // Remove loading overlay if exists
        removeLoadingOverlay();
    });

    // ==========================================
    // NAVBAR SCROLL EFFECT
    // ==========================================
    function initNavbar() {
        const navbar = document.getElementById('publicNavbar');
        if (!navbar) return;

        let lastScroll = 0;

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;

            // Add scrolled class when scrolled past threshold
            if (currentScroll > CONFIG.navbar.scrollThreshold) {
                navbar.classList.add(CONFIG.navbar.scrolledClass);
            } else {
                navbar.classList.remove(CONFIG.navbar.scrolledClass);
            }

            lastScroll = currentScroll;
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navbarCollapse = document.getElementById('navbarNav');
            const navbarToggler = document.querySelector('.navbar-toggler');
            
            if (navbarCollapse && navbarToggler) {
                const isClickInside = navbar.contains(event.target);
                const isExpanded = navbarCollapse.classList.contains('show');
                
                if (!isClickInside && isExpanded) {
                    navbarToggler.click();
                }
            }
        });

        console.log('✓ Navbar initialized');
    }

    // ==========================================
    // COUNTER ANIMATION
    // ==========================================
    function initCounters() {
        const counters = document.querySelectorAll('[data-counter]');
        
        counters.forEach(counter => {
            animateCounter(counter);
        });

        console.log(`✓ ${counters.length} counters initialized`);
    }

    function animateCounter(element) {
        const target = parseFloat(element.getAttribute('data-counter'));
        const duration = parseInt(element.getAttribute('data-duration')) || CONFIG.counter.duration;
        const prefix = element.getAttribute('data-prefix') || '';
        const suffix = element.getAttribute('data-suffix') || '';
        const decimals = parseInt(element.getAttribute('data-decimals')) || 0;
        
        let current = 0;
        const increment = target / (duration / CONFIG.counter.increment);
        
        const updateCounter = () => {
            current += increment;
            
            if (current < target) {
                element.textContent = prefix + formatNumber(current, decimals) + suffix;
                setTimeout(updateCounter, CONFIG.counter.increment);
            } else {
                element.textContent = prefix + formatNumber(target, decimals) + suffix;
            }
        };
        
        // Start animation when element is in viewport
        observeElement(element, () => {
            updateCounter();
        });
    }

    function formatNumber(num, decimals = 0) {
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(num);
    }

    // ==========================================
    // INTERSECTION OBSERVER
    // ==========================================
    function observeElement(element, callback) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback();
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.5
        });

        observer.observe(element);
    }

    // ==========================================
    // SMOOTH SCROLL
    // ==========================================
    function initSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                if (href === '#') return;
                
                e.preventDefault();
                
                const target = document.querySelector(href);
                if (target) {
                    const navbarHeight = document.getElementById('publicNavbar')?.offsetHeight || 0;
                    const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navbarHeight - 20;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        console.log('✓ Smooth scroll initialized');
    }

    // ==========================================
    // TOOLTIPS
    // ==========================================
    function initTooltips() {
        // Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            console.log(`✓ ${tooltipTriggerList.length} tooltips initialized`);
        }
    }

    // ==========================================
    // CHART DEFAULTS
    // ==========================================
    function initChartDefaults() {
        if (typeof ApexCharts === 'undefined') return;

        // Set default chart options
        window.publicChartDefaults = {
            chart: {
                fontFamily: 'Public Sans, sans-serif',
                height: 300, // ✅ FIXED: Default 300px
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
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                }
            },
            colors: CONFIG.chart.defaultColors,
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
            yaxis: {
                labels: {
                    formatter: function(val) {
                        // ✅ Y-axis: SHORT format (OK)
                        return window.formatCurrency(val, { useShortFormat: true });
                    }
                }
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: '14px',
                    fontFamily: 'Public Sans, sans-serif'
                },
                y: {
                    formatter: function(val) {
                        // ✅ Tooltip: FULL format (FIXED)
                        if (!val || val === 0) return 'Rp 0';
                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0
                        }).format(Math.abs(val));
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'left',
                fontSize: '14px',
                fontFamily: 'Public Sans, sans-serif',
                markers: {
                    width: 10,
                    height: 10,
                    radius: 2
                }
            }
        };

        console.log('✓ Chart defaults configured');
    }

    // ==========================================
    // UTILITY FUNCTIONS
    // ==========================================

    /**
     * Format currency untuk Rupiah
     */
    window.formatCurrency = function(value, options = {}) {
        const {
            minimumFractionDigits = 0,
            maximumFractionDigits = 0,
            useShortFormat = false
        } = options;

        if (!value || value === 0) return 'Rp 0';

        const absValue = Math.abs(value);
        const isNegative = value < 0;

        let formatted = '';

        if (useShortFormat) {
            // Short format: T, M, Jt, Rb
            if (absValue >= 1e12) {
                formatted = 'Rp ' + (absValue / 1e12).toFixed(2).replace('.', ',') + ' T';
            } else if (absValue >= 1e9) {
                formatted = 'Rp ' + (absValue / 1e9).toFixed(2).replace('.', ',') + ' M';
            } else if (absValue >= 1e6) {
                formatted = 'Rp ' + (absValue / 1e6).toFixed(2).replace('.', ',') + ' Jt';
            } else if (absValue >= 1e3) {
                formatted = 'Rp ' + (absValue / 1e3).toFixed(2).replace('.', ',') + ' Rb';
            } else {
                formatted = 'Rp ' + new Intl.NumberFormat('id-ID').format(absValue);
            }
        } else {
            // Full format
            formatted = 'Rp ' + new Intl.NumberFormat('id-ID', {
                minimumFractionDigits,
                maximumFractionDigits
            }).format(absValue);
        }

        return isNegative ? '(' + formatted + ')' : formatted;
    };

    /**
     * Format percentage
     */
    window.formatPercentage = function(value, decimals = 1) {
        if (value === null || value === undefined) return '0%';
        return value.toFixed(decimals) + '%';
    };

    /**
     * Format number with thousand separator
     */
    window.formatNumber = function(value, decimals = 0) {
        if (value === null || value === undefined) return '0';
        return new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(value);
    };

    /**
     * Debounce function
     */
    window.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    /**
     * Show loading overlay
     */
    window.showLoading = function(message = 'Memuat data...') {
        let overlay = document.getElementById('loadingOverlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            overlay.innerHTML = `
                <div class="spinner-modern"></div>
                <div class="loading-text">${message}</div>
            `;
            document.body.appendChild(overlay);
        } else {
            overlay.querySelector('.loading-text').textContent = message;
            overlay.style.display = 'flex';
        }
    };

    /**
     * Hide loading overlay
     */
    window.hideLoading = function() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    };

    /**
     * Remove loading overlay
     */
    function removeLoadingOverlay() {
        setTimeout(() => {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => {
                    overlay.remove();
                }, 300);
            }
        }, 500);
    }

    /**
     * Show toast notification
     */
    window.showToast = function(message, type = 'info') {
        // Use SweetAlert2 if available
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: type,
                title: message,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });
        } else {
            // Fallback to simple alert
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    };

    /**
     * Copy to clipboard
     */
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Berhasil disalin!', 'success');
            }).catch(() => {
                showToast('Gagal menyalin', 'error');
            });
        } else {
            // Fallback
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showToast('Berhasil disalin!', 'success');
        }
    };

    /**
     * Scroll to top
     */
    window.scrollToTop = function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    };

    /**
     * Get query parameter
     */
    window.getQueryParam = function(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    };

    /**
     * Set query parameter
     */
    window.setQueryParam = function(param, value) {
        const url = new URL(window.location);
        url.searchParams.set(param, value);
        window.history.pushState({}, '', url);
    };

    /**
     * Generate random ID
     */
    window.generateId = function(prefix = 'id') {
        return prefix + '_' + Math.random().toString(36).substr(2, 9);
    };

    // ==========================================
    // CHART HELPERS
    // ==========================================

    /**
     * Create bar chart
     */
    window.createBarChart = function(selector, data, options = {}) {
        const defaultOptions = {
            series: data.series || [],
            chart: {
                type: 'bar',
                height: 350,
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
                categories: data.categories || []
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return formatCurrency(val, { useShortFormat: true });
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatCurrency(val);
                    }
                }
            },
            ...window.publicChartDefaults,
            ...options
        };

        const chart = new ApexCharts(document.querySelector(selector), defaultOptions);
        chart.render();
        return chart;
    };

    /**
     * Create line chart
     */
    window.createLineChart = function(selector, data, options = {}) {
        const defaultOptions = {
            series: data.series || [],
            chart: {
                type: 'line',
                height: 350,
                ...window.publicChartDefaults.chart
            },
            xaxis: {
                categories: data.categories || []
            },
            yaxis: {
                labels: {
                    formatter: function(val) {
                        return formatCurrency(val, { useShortFormat: true });
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatCurrency(val);
                    }
                }
            },
            markers: {
                size: 5,
                hover: {
                    size: 7
                }
            },
            ...window.publicChartDefaults,
            ...options
        };

        const chart = new ApexCharts(document.querySelector(selector), defaultOptions);
        chart.render();
        return chart;
    };

    // ==========================================
    // DATA FETCHING HELPERS
    // ==========================================

    /**
     * Fetch data from API
     */
    window.fetchData = async function(url, options = {}) {
        try {
            showLoading('Memuat data...');
            
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    ...options.headers
                },
                ...options
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            hideLoading();
            
            return data;
        } catch (error) {
            hideLoading();
            console.error('Fetch error:', error);
            showToast('Gagal memuat data: ' + error.message, 'error');
            throw error;
        }
    };

    // ==========================================
    // EXPORT FUNCTIONS
    // ==========================================

    /**
     * Export table to CSV
     */
    window.exportTableToCSV = function(tableSelector, filename = 'export.csv') {
        const table = document.querySelector(tableSelector);
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            const cols = row.querySelectorAll('td, th');
            const csvRow = [];
            
            cols.forEach(col => {
                csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
            });
            
            csv.push(csvRow.join(','));
        });

        downloadCSV(csv.join('\n'), filename);
    };

    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // ==========================================
    // CONSOLE INFO
    // ==========================================
    console.log('%c🎨 Public Dashboard v1.0', 'color: #667eea; font-size: 16px; font-weight: bold;');
    console.log('%cBPKPAD Kota Banjarmasin', 'color: #718096; font-size: 12px;');
    console.log('%cUtility functions available:', 'color: #2d3748; font-weight: bold; margin-top: 10px;');
    console.log('- formatCurrency(value, options)');
    console.log('- formatPercentage(value, decimals)');
    console.log('- formatNumber(value, decimals)');
    console.log('- createBarChart(selector, data, options)');
    console.log('- createLineChart(selector, data, options)');
    console.log('- showLoading(message)');
    console.log('- hideLoading()');
    console.log('- showToast(message, type)');
    console.log('- exportTableToCSV(tableSelector, filename)');

})();
