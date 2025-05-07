<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Pajak Daerah' }}</title>
    
    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/core.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/css/theme-default.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    
    @livewireStyles
</head>
<body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">
            <!-- Menu -->
            @include('layouts.sidebar')
            <!-- / Menu -->

            <!-- Layout container -->
            <div class="layout-page">
                <!-- Navbar -->
                @include('layouts.navbar')
                <!-- / Navbar -->

                <!-- Content wrapper -->
                <div class="content-wrapper">
                    <!-- Content -->
                    <div class="container-xxl flex-grow-1 container-p-y">
                        {{ $slot }}
                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    @include('layouts.footer')
                    <!-- / Footer -->
                </div>
                <!-- / Content wrapper -->
            </div>
            <!-- / Layout container -->
        </div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Scripts -->
    <script src="{{ asset('sneat-template/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('sneat-template/assets/js/main.js') }}"></script>
    
    @livewireScripts

     <!-- Tambahkan baris ini untuk memuat script dari view lain -->
     @stack('scripts')
    
    <script>
    document.addEventListener('livewire:navigated', () => {
        // Script lainnya tetap sama
    });
    </script>
    
    <script>
    document.addEventListener('livewire:navigated', () => {
        // Reinisialisasi menu setelah navigasi Livewire
        if (window.Menu) {
            const menuElement = document.querySelector('.menu');
            if (menuElement && menuElement.menuInstance) {
                menuElement.menuInstance.update();
            }
        }
        
        // Untuk template Sneat
        if (window.Helpers && typeof window.Helpers.initSidebarMenu === 'function') {
            window.Helpers.initSidebarMenu();
        }
    });
    </script>
</body>
</html>
