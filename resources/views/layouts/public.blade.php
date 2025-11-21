<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Dashboard Pendapatan Daerah Kota Banjarmasin - Transparansi Pengelolaan Keuangan Daerah">
    <meta name="keywords" content="Dashboard Pendapatan, BPKPAD, Banjarmasin, Keuangan Daerah, PAD, Transparansi">
    <meta name="author" content="BPKPAD Kota Banjarmasin">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Dashboard Pendapatan Daerah - Kota Banjarmasin">
    <meta property="og:description" content="Transparansi Pengelolaan Keuangan Daerah Kota Banjarmasin">
    <meta property="og:type" content="website">
    
    <title>@yield('title', 'Dashboard Pendapatan Daerah - Kota Banjarmasin')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat-template/assets/img/favicon/favicon.ico') }}" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/theme-default.css') }}" />
    
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    
    <!-- ApexCharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Public CSS -->
    <link rel="stylesheet" href="{{ asset('css/public-modern.css') }}" />
    
    <!-- Page Styles -->
    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" id="publicNavbar">
        <div class="container-fluid px-4 px-lg-5">
            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center" href="{{ url('/dashboard-publik') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Logo BPKPAD" height="40" class="me-2">
                <div class="brand-text">
                    <div class="brand-title">Dashboard Pendapatan</div>
                    <div class="brand-subtitle">Kota Banjarmasin</div>
                </div>
            </a>

            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard-publik') ? 'active' : '' }}" href="{{ url('/dashboard-publik') }}">
                            <i class='bx bx-home-alt me-1'></i>
                            Dashboard
                        </a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link {{ request()->is('trend-publik') ? 'active' : '' }}" href="{{ url('/trend-publik') }}">
                            <i class='bx bx-line-chart me-1'></i>
                            Trend Analysis
                        </a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">
                            <i class='bx bx-log-in me-1'></i>
                            Login Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid px-4 px-lg-5 py-4">
            @yield('content')
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-public">
        <div class="container-fluid px-4 px-lg-5 py-4">
            <div class="row align-items-center">
                <div class="col-md-6 mb-3 mb-md-0">
                    <div class="footer-brand mb-2">
                        <img src="{{ asset('images/logo.png') }}" alt="Logo BPKPAD" height="32" class="me-2">
                        <span class="footer-brand-text">BPKPAD Kota Banjarmasin</span>
                    </div>
                    <p class="footer-text mb-0">
                        Badan Pengelolaan Keuangan dan Pendapatan Daerah<br>
                        Kota Banjarmasin
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="footer-info mb-2">
                        <i class='bx bx-map me-1'></i>
                        Jl. Sultan Adam No. 18, Banjarmasin
                    </div>
                    <div class="footer-info mb-2">
                        <i class='bx bx-phone me-1'></i>
                        (0511) 3304180
                    </div>
                    <div class="footer-social mt-3">
                        <a href="#" class="social-link" title="Facebook">
                            <i class='bx bxl-facebook-circle'></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class='bx bxl-instagram-alt'></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class='bx bxl-twitter'></i>
                        </a>
                        <a href="#" class="social-link" title="Email">
                            <i class='bx bx-envelope'></i>
                        </a>
                    </div>
                </div>
            </div>
            <hr class="footer-divider my-3">
            <div class="row">
                <div class="col-12 text-center">
                    <p class="footer-copyright mb-0">
                        © {{ date('Y') }} BPKPAD Kota Banjarmasin. All rights reserved.
                        <span class="ms-2">|</span>
                        <span class="ms-2">Sistem Informasi Laporan Pendapatan Terpadu</span>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Core JS -->
    <script src="{{ asset('sneat-template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/bootstrap.js') }}"></script>
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.0/dist/apexcharts.min.js"></script>
    
    <!-- AOS Animation -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- Alpine.js for interactions -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom Public JS -->
    <script src="{{ asset('js/public-app.js') }}"></script>
    
    <!-- Page Scripts -->
    @stack('scripts')
    
    <!-- Initialize AOS -->
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });
    </script>
</body>
</html>
