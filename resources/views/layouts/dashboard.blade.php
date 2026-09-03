<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#103b74">
    <title>@yield('title') — Sistem Arsip Surat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-body">
    <div class="dashboard-shell" data-dashboard-shell>
        <aside class="dashboard-sidebar" id="dashboard-sidebar" aria-label="Navigasi utama">
            <button class="sidebar-close-button" type="button" data-sidebar-close aria-label="Tutup menu navigasi">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>
            </button>
            <div class="sidebar-brand">
                <div class="sidebar-logos" aria-hidden="true">
                    <img src="{{ asset('images/logo-pemkab-jember.png') }}" alt="">
                    <img class="sidebar-logo-bapenda" src="{{ asset('images/logo-bapenda-jember.png') }}" alt="">
                </div>
                <div class="sidebar-brand-copy">
                    <strong>Sistem Arsip Surat</strong>
                    <span>Bidang Penetapan &amp; Keberatan</span>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Menu dashboard">
                @php
                    $isKepalaBidang = auth()->user()->role === \App\Models\User::ROLE_KEPALA_BIDANG;
                    $dashboardRoute = $isKepalaBidang ? 'dashboard.kepala-bidang' : 'dashboard.pegawai';
                    $dataSuratAktif = request()->routeIs('kepala-bidang.surat.*');
                    $monitoringPegawaiAktif = request()->routeIs('kepala-bidang.monitoring-pegawai.*');
                    $riwayatAktif = request()->routeIs('kepala-bidang.riwayat-aktivitas.*');
                    $suratSayaAktif = request()->routeIs('pegawai.surat-saya.*');
                @endphp
                <span class="sidebar-nav-label">Menu utama</span>
                <a class="sidebar-link {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }}" href="{{ route($dashboardRoute) }}" @if (request()->routeIs($dashboardRoute)) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                    <span>Dashboard</span>
                </a>
                <a class="sidebar-link {{ $dataSuratAktif || $suratSayaAktif ? 'is-active' : '' }}" href="{{ $isKepalaBidang ? route('kepala-bidang.surat.index') : route('pegawai.surat-saya.index') }}" @if ($dataSuratAktif || $suratSayaAktif) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M8 14h8M8 17h5" stroke-linecap="round"/></svg>
                    <span>{{ $isKepalaBidang ? 'Data Surat' : 'Surat Saya' }}</span>
                </a>
                @if ($isKepalaBidang)
                    <a class="sidebar-link {{ $monitoringPegawaiAktif ? 'is-active' : '' }}" href="{{ route('kepala-bidang.monitoring-pegawai.index') }}" @if ($monitoringPegawaiAktif) aria-current="page" @endif>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M17 11h5M19.5 8.5v5" stroke-linecap="round"/></svg>
                        <span>Monitoring Pegawai</span>
                    </a>
                @endif
                <a class="sidebar-link {{ $riwayatAktif ? 'is-active' : '' }}" href="{{ $isKepalaBidang ? route('kepala-bidang.riwayat-aktivitas.index') : route($dashboardRoute).'#aktivitas' }}" @if ($riwayatAktif) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Riwayat / Aktivitas</span>
                </a>
            </nav>

            <div class="sidebar-account">
                <div class="sidebar-user">
                    <span class="user-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="sidebar-user-copy">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>@yield('roleLabel')</small>
                    </span>
                </div>
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    data-loading-form
                    data-confirm-title="Keluar dari Sistem?"
                    data-confirm-message="Anda akan keluar dari sesi saat ini."
                    data-confirm-label="Ya, Keluar"
                    data-confirm-loading-label="Mengeluarkan..."
                >
                    @csrf
                    <button class="logout-button" type="submit" data-loading-label="Mengeluarkan...">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 4h5a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-5" stroke-linecap="round"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <button class="sidebar-overlay" type="button" data-sidebar-close aria-label="Tutup menu navigasi"></button>

        <div class="dashboard-workspace">
            <header class="dashboard-topbar">
                <button class="mobile-menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Buka menu navigasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
                <div class="topbar-heading">
                    <span class="topbar-eyebrow">Sistem Arsip Surat</span>
                    <h1>@yield('pageTitle')</h1>
                </div>
                <div class="topbar-user">
                    <span class="topbar-avatar" aria-hidden="true">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="topbar-user-copy">
                        <strong>Selamat datang, {{ auth()->user()->name }}</strong>
                        <small>@yield('roleLabel')</small>
                    </span>
                </div>
            </header>

            <main class="dashboard-main">
                <x-toast />
                @yield('content')
                <footer class="dashboard-footer">
                    <span>&copy; {{ date('Y') }} Sistem Arsip Surat</span>
                    <span>Bidang Penetapan dan Keberatan</span>
                </footer>
            </main>
        </div>
    </div>
    <x-confirmation-modal />
</body>
</html>
