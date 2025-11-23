<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="{{ asset('images/silapat-favicon.png') }}">
<title>@yield('title', 'SILAPAT - BPKPAD Banjarmasin')</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet" />

    <!-- Icons -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/fonts/boxicons.css') }}" />

    <!-- Core CSS -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/css/demo.css') }}" />

    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    
    <!-- Page CSS -->
    @stack('styles')
    
    <!-- Livewire Styles -->
    @livewireStyles(['skipAlpine' => true])
</head>
<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Sidebar -->
            @include('layouts.sidebar')
            
            <div class="layout-page">
                <!-- Navbar -->
                @include('layouts.navbar')
                
                <div class="content-wrapper">
                    <div class="container-xxl flex-grow-1 container-p-y">
                        <!-- Flash Messages -->
                        @if (session()->has('message'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('message') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        @if (session()->has('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        <!-- Content -->
                        @yield('content')
                    </div>
                    
                    <!-- Footer -->
                    @include('layouts.footer')
                </div>
            </div>
        </div>
    </div>
    
    <!-- Core JS -->
    <!-- jQuery harus dimuat pertama -->
    <script src="{{ asset('sneat-template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/bootstrap.js') }}"></script>
    
    <!-- PerfectScrollbar dimuat sebelum menu.js -->
    <script src="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    
    <!-- Helpers dan menu.js -->
    <script src="{{ asset('sneat-template/assets/vendor/js/helpers.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/menu.js') }}"></script>
    
    <!-- Main JS -->
    <script src="{{ asset('sneat-template/assets/js/main.js') }}"></script>
    <!-- Di layout sebelum </body> -->
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Livewire Scripts -->
    @livewireScripts
    
    <!-- Alpine.js dimuat setelah menu.js dan Livewire -->
    <!-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> -->
    
    <!-- Page JS -->
    @stack('scripts')
    <script>
// Tambahkan kode ini setelah script lain
document.addEventListener('DOMContentLoaded', function() {
    // Pastikan semua link menu berfungsi dengan benar
    setTimeout(function() {
        document.querySelectorAll('.menu-link:not([data-event-added])').forEach(function(link) {
            link.setAttribute('data-event-added', 'true');
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href && href !== '#' && !href.startsWith('javascript:')) {
                    window.location.href = href;
                }
            });
        });
    }, 1000); // Tunggu 1 detik untuk memastikan semua komponen dimuat
});
</script>
</body>
</html>