<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Aplikasi Pajak Daerah') }}</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('sneat-template/assets/img/favicon/favicon.ico') }}" />

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
    @livewireStyles
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
    <script src="{{ asset('sneat-template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/menu.js') }}"></script>
    
    <!-- Main JS -->
    <script src="{{ asset('sneat-template/assets/js/main.js') }}"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Page JS -->
    @stack('scripts')
    
    <!-- Livewire Scripts -->
    @livewireScripts

    <!-- Fix untuk integrasi Menu, Alpine.js dan Livewire -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Fix untuk menu.js error (menuPsScroll undefined)
            if (typeof window.Helpers !== 'undefined' && typeof window.PerfectScrollbar !== 'undefined') {
                try {
                    // Inisialisasi ulang menu setelah DOM loaded
                    window.Helpers.initMenu();
                } catch (error) {
                    console.warn('Menu initialization error:', error);
                }
            }

            // Fix untuk menu items yang tidak berfungsi saat diklik
            const menuItems = document.querySelectorAll('.menu-item a');
            menuItems.forEach(item => {
                if (item && item.getAttribute('href') && !item.getAttribute('href').startsWith('#') && 
                    !item.classList.contains('menu-toggle')) {
                    item.addEventListener('click', function(e) {
                        // Hindari default behavior untuk menu dengan submenu
                        if (this.classList.contains('menu-toggle')) {
                            e.preventDefault();
                            return;
                        }
                        
                        // Redirect ke URL yang benar
                        if (this.getAttribute('href') !== 'javascript:void(0);') {
                            window.location.href = this.getAttribute('href');
                        }
                    });
                }
            });
        });

        // Fix untuk Livewire dan Alpine.js yang saling konflik
        document.addEventListener('livewire:load', function() {
            // Re-inisialisasi menu setelah Livewire update
            Livewire.hook('message.processed', () => {
                if (typeof window.Helpers !== 'undefined') {
                    try {
                        // Re-init menu setelah Livewire update DOM
                        window.Helpers.initMenu();
                        
                        // Re-init menu-toggle untuk dropdown
                        document.querySelectorAll('.menu-toggle').forEach(menuToggle => {
                            menuToggle.addEventListener('click', e => {
                                e.preventDefault();
                                const parentMenuItem = menuToggle.closest('.menu-item');
                                if (parentMenuItem) {
                                    parentMenuItem.classList.toggle('open');
                                }
                            });
                        });
                    } catch (error) {
                        console.warn('Menu re-initialization error:', error);
                    }
                }
            });
        });
    </script>
</body>
</html>