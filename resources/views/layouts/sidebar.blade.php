<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route('dashboard') }}" class="app-brand-link">
            <span class="app-brand-logo demo">
                <!-- Logo disini -->
            </span>
            <span class="app-brand-text demo menu-text fw-bolder ms-2">Pajak Daerah</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboard -->
        <li class="menu-item {{ request()->routeIs('dashboard.*') ? 'active' : '' }}">
        <a href="{{ route('dashboard') }}" class="menu-link" onclick="window.location.href='{{ route('dashboard') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-home-circle"></i>
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>


<li class="menu-item {{ request()->routeIs('trend-analysis') ? 'active' : '' }}">
    <a href="{{ route('trend-analysis') }}" class="menu-link">
        <i class="menu-icon tf-icons bx bx-line-chart"></i>
        <div>Trend Analysis</div>
    </a>
</li>

        <!-- Master Data -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Master Data</span>
        </li>
        

        <li class="menu-item {{ request()->routeIs('tahun-anggaran.*') ? 'active' : '' }}">
    <a href="{{ route('tahun-anggaran.index') }}" class="menu-link" onclick="window.location.href='{{ route('tahun-anggaran.index') }}'; return false;">
        <!-- <li class="menu-item {{ request()->routeIs('tahun-anggaran.*') ? 'active open' : '' }}">
            <a href="{{ route('tahun-anggaran.index') }}" class="menu-link" wire:navigate> -->
                <i class="menu-icon tf-icons bx bx-calendar"></i>
                <div data-i18n="Tahun Anggaran">Tahun Anggaran</div>
            </a>
        </li>
        
        <li class="menu-item {{ request()->routeIs('kode-rekening.*') ? 'active' : '' }}">
        <a href="{{ route('kode-rekening.index') }}" class="menu-link" onclick="window.location.href='{{ route('kode-rekening.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-code-block"></i>
                <div data-i18n="Kode Rekening">Kode Rekening</div>
            </a>
        </li>

        <!-- <li class="menu-item {{ request()->routeIs('target-bulan.*') ? 'active' : '' }}">
        <a href="{{ route('target-bulan.index') }}" class="menu-link" onclick="window.location.href='{{ route('target-bulan.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-target-lock"></i>
                <div data-i18n="Target Kelompok Bulan">Target Kelompok Bulan</div> -->
            </a>
        </li>
        
        <li class="menu-item {{ request()->routeIs('target-periode.*') ? 'active' : '' }}">
        <a href="{{ route('target-periode.index') }}" class="menu-link" onclick="window.location.href='{{ route('target-periode.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-target-lock"></i>
                <div data-i18n="Target Periode">Target Periode</div>
            </a>
        </li>
        
        <li class="menu-item {{ request()->routeIs('target-anggaran.*') ? 'active' : '' }}">
        <a href="{{ route('target-anggaran.index') }}" class="menu-link" onclick="window.location.href='{{ route('target-anggaran.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-money"></i>
                <div data-i18n="Target Anggaran">Pagu Anggaran</div>
            </a>
        </li>

        <!-- Transaksi -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Transaksi</span>
        </li>
        
        <li class="menu-item {{ request()->routeIs('penerimaan.*') ? 'active' : '' }}">
        <a href="{{ route('penerimaan.index') }}" class="menu-link" onclick="window.location.href='{{ route('penerimaan.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-receipt"></i>
                <div data-i18n="Penerimaan">Penerimaan</div>
            </a>
        </li>

        <!-- Laporan -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Laporan</span>
        </li>
        
        <li class="menu-item {{ request()->routeIs('laporan.*') ? 'active' : '' }}">
        <a href="{{ route('laporan.index') }}" class="menu-link" onclick="window.location.href='{{ route('laporan.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-file"></i>
                <div data-i18n="Laporan Realisasi">Laporan Realisasi</div>
            </a>
        </li>

        <!-- Pengaturan
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Pengaturan</span>
        </li>
        <li class="menu-item {{ request()->routeIs('user.*') ? 'active' : '' }}">
        <a href="{{ route('user.index') }}" class="menu-link" onclick="window.location.href='{{ route('user.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-user"></i>
                <div data-i18n="Pengguna">Pengguna</div>
            </a>
        </li>
        
<li class="menu-item {{ request()->routeIs('backup.*') ? 'active' : '' }}">
        <a href="{{ route('backup.index') }}" class="menu-link" onclick="window.location.href='{{ route('backup.index') }}'; return false;">
                <i class="menu-icon tf-icons bx bx-data"></i>
                <div data-i18n="Backup & Restore">Backup & Restore</div>
            </a>
        </li> -->
    </ul>
</aside>
