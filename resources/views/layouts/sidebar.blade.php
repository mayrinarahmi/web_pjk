<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <!-- Logo disini -->
            </span>
            <img src="{{ asset('images/logo.png') }}" alt="Logo BKPAD Banjarmasin" class="pkpad-logo" style="width: 150px; height: auto; max-width: 100%; margin-bottom: 10px;">
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard - Semua user bisa akses -->
        @can('view-dashboard')
        <li class="menu-item {{ request()->routeIs('dashboard.*') || request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div>Dashboard</div>
            </a>
        </li>
        @endcan

        <!-- Trend Analysis - TIDAK untuk Operator SKPD -->
        @if(auth()->user()->hasRole(['Super Admin', 'Administrator', 'Kepala Badan', 'Operator', 'Viewer']))
        @can('view-trend-analysis')
        <li class="menu-item {{ request()->routeIs('trend-analysis') ? 'active' : '' }}">
            <a href="{{ route('trend-analysis') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-line-chart"></i>
                <div>Trend Analysis</div>
            </a>
        </li>
        @endcan
        @endif

        <!-- Master Data - Header untuk semua yang punya permission -->
        @if(auth()->user()->can('view-tahun-anggaran') || 
            auth()->user()->can('view-kode-rekening') || 
            auth()->user()->can('view-target'))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master Data</span>
        </li>
        
        <!-- Tahun Anggaran - Tidak untuk Operator SKPD -->
        @if(!auth()->user()->hasRole('Operator SKPD'))
            @can('view-tahun-anggaran')
            <li class="menu-item {{ request()->routeIs('tahun-anggaran.*') ? 'active' : '' }}">
                <a href="{{ route('tahun-anggaran.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-calendar"></i>
                    <div>Tahun Anggaran</div>
                </a>
            </li>
            @endcan
        @endif
        
        <!-- Kode Rekening - Tidak untuk Operator SKPD -->
        @if(!auth()->user()->hasRole('Operator SKPD'))
            @can('view-kode-rekening')
            <li class="menu-item {{ request()->routeIs('kode-rekening.*') ? 'active' : '' }}">
                <a href="{{ route('kode-rekening.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-code-block"></i>
                    <div>Kode Rekening</div>
                </a>
            </li>
            @endcan
        @endif

        <!-- Target Periode - Tidak untuk Operator SKPD -->
        @if(!auth()->user()->hasRole('Operator SKPD'))
            @can('view-target')
            <li class="menu-item {{ request()->routeIs('target-periode.*') ? 'active' : '' }}">
                <a href="{{ route('target-periode.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons bx bx-target-lock"></i>
                    <div>Target Periode</div>
                </a>
            </li>
            @endcan
        @endif
        
        <!-- ✅ PAGU ANGGARAN - SEMUA ROLE BISA AKSES (TERMASUK OPERATOR SKPD) -->
        @can('view-target')
        <li class="menu-item {{ request()->routeIs('target-anggaran.*') ? 'active' : '' }}">
            <a href="{{ route('target-anggaran.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-money"></i>
                <div>Pagu Anggaran</div>
            </a>
        </li>
        @endcan
        @endif

        <!-- Transaksi - Semua kecuali Viewer -->
        @can('view-penerimaan')
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Transaksi</span>
        </li>
        
        <li class="menu-item {{ request()->routeIs('penerimaan.*') ? 'active' : '' }}">
            <a href="{{ route('penerimaan.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-receipt"></i>
                <div>Penerimaan</div>
            </a>
        </li>
        @endcan

        <!-- Laporan - Hanya Super Admin & Kepala Badan, TIDAK untuk Operator SKPD -->
        @if(auth()->user()->hasRole(['Super Admin', 'Kepala Badan']))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Laporan</span>
        </li>
        <li class="menu-item {{ request()->routeIs('laporan.ringkasan') ? 'active' : '' }}">
            <a href="{{ route('laporan.ringkasan') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-bar-chart-alt-2"></i>
                <div>Ringkasan</div>
            </a>
        </li>
        @endif

        <!-- Pengaturan - Hanya Super Admin dan Operator (backward compatibility) -->
        @if(auth()->user()->hasRole(['Super Admin', 'Administrator', 'Operator']))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pengaturan</span>
        </li>
        
        @can('view-users')
        <li class="menu-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
            <a href="{{ route('user.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div>Pengguna</div>
            </a>
        </li>
        @endcan
        
        @can('manage-skpd')
        <li class="menu-item {{ request()->routeIs('skpd.*') ? 'active' : '' }}">
            <a href="{{ route('skpd.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-building"></i>
                <div>Kelola SKPD</div>
            </a>
        </li>
        @endcan
        
        @can('manage-backup')
        <li class="menu-item {{ request()->routeIs('backup.*') ? 'active' : '' }}">
            <a href="{{ route('backup.index') }}" class="menu-link">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div>Backup & Restore</div>
            </a>
        </li>
        @endcan
        @endif
    </ul>
</aside>