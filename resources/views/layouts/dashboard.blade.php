<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#103b74">
    <title>@yield('title') | {{ config('branding.short_name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-body">
    <x-toast />
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
                    <strong>{{ config('branding.short_name') }}</strong>
                    <span>{{ config('branding.full_name') }}</span>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Menu dashboard">
                @php
                    $dashboardRoute = 'dashboard';
                    $dataSuratAktif = request()->routeIs('surat.*');
                    $suratSayaAktif = request()->routeIs('surat-saya.*');
                    $suratTersediaAktif = request()->routeIs('surat-tersedia.*');
                    $laporanAktif = request()->routeIs('laporan.*');
                    $dropdownSuratAktif = $dataSuratAktif;
                    $dropdownLaporanAktif = $laporanAktif;
                    $currentKategoriId = request('kategori');
                    $kategoriList = \Illuminate\Support\Facades\Schema::hasTable('kategori_surats')
                        ? \App\Models\KategoriSurat::query()->resmi()->orderBy('urutan')->get(['id', 'nama', 'slug'])
                        : collect();
                @endphp
                <span class="sidebar-nav-label">Menu utama</span>
                <a class="sidebar-link {{ request()->routeIs($dashboardRoute) ? 'is-active' : '' }}" href="{{ route($dashboardRoute) }}" @if (request()->routeIs($dashboardRoute)) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>
                    <span>Dashboard</span>
                </a>

                <div class="sidebar-dropdown-group" data-sidebar-dropdown>
                    <div class="sidebar-dropdown-header">
                        <a class="sidebar-link sidebar-parent-link {{ $dropdownSuratAktif ? 'is-active' : '' }}" href="{{ route('surat.index') }}" @if ($dropdownSuratAktif) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M8 14h8M8 17h5" stroke-linecap="round"/></svg>
                            <span>Data Surat</span>
                        </a>
                        <button class="sidebar-dropdown-toggle" type="button" data-dropdown-toggle aria-label="Buka daftar jenis surat" aria-expanded="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="sidebar-submenu-wrapper">
                        <div class="sidebar-submenu" data-submenu>
                            @foreach ($kategoriList as $kategoriItem)
                                @php
                                    $isKatActive = $dropdownSuratAktif && (
                                        (string) $currentKategoriId === (string) $kategoriItem->id ||
                                        (string) $currentKategoriId === (string) $kategoriItem->slug
                                    );
                                    $kategoriValue = $kategoriItem->slug === \App\Models\KategoriSurat::SLUG_LAIN_LAIN
                                        ? $kategoriItem->slug
                                        : $kategoriItem->id;
                                    $kategoriLabel = $kategoriItem->slug === \App\Models\KategoriSurat::SLUG_LAIN_LAIN
                                        ? 'Surat Lain-lain'
                                        : $kategoriItem->nama;
                                    $targetUrl = route('surat.index', ['kategori' => $kategoriValue]);
                                @endphp
                                <a class="sidebar-sublink {{ $isKatActive ? 'is-active' : '' }}" href="{{ $targetUrl }}" @if ($isKatActive) aria-current="page" @endif>
                                    <span class="sublink-dot" aria-hidden="true"></span>
                                    <span>{{ $kategoriLabel }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                <a class="sidebar-link {{ $suratTersediaAktif ? 'is-active' : '' }}" href="{{ route('surat-tersedia.index') }}" @if ($suratTersediaAktif) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                    <span>Ambil Surat</span>
                </a>
                <a class="sidebar-link {{ $suratSayaAktif ? 'is-active' : '' }}" href="{{ route('surat-saya.index') }}" @if ($suratSayaAktif) aria-current="page" @endif>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M8 14h8M8 17h5" stroke-linecap="round"/></svg>
                    <span>Surat Saya</span>
                </a>
                <div class="sidebar-dropdown-group" data-sidebar-dropdown>
                    <div class="sidebar-dropdown-header">
                        <a class="sidebar-link sidebar-parent-link {{ $dropdownLaporanAktif ? 'is-active' : '' }}" href="{{ route('laporan.index') }}" @if ($dropdownLaporanAktif) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 3h14v18H5z"/><path d="M8 8h8M8 12h8M8 16h5" stroke-linecap="round"/></svg>
                            <span>Laporan</span>
                        </a>
                        <button class="sidebar-dropdown-toggle" type="button" data-dropdown-toggle aria-label="Buka daftar laporan per kategori" aria-expanded="false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>

                    <div class="sidebar-submenu-wrapper">
                        <div class="sidebar-submenu" data-submenu>
                            @foreach ($kategoriList as $kategoriItem)
                                @php
                                    $isLaporanKatActive = $dropdownLaporanAktif && (
                                        (string) $currentKategoriId === (string) $kategoriItem->id ||
                                        (string) $currentKategoriId === (string) $kategoriItem->slug
                                    );
                                    $laporanKategoriValue = $kategoriItem->slug === \App\Models\KategoriSurat::SLUG_LAIN_LAIN
                                        ? $kategoriItem->slug
                                        : $kategoriItem->id;
                                    $laporanKategoriLabel = $kategoriItem->slug === \App\Models\KategoriSurat::SLUG_LAIN_LAIN
                                        ? 'Surat Lain-lain'
                                        : $kategoriItem->nama;
                                @endphp
                                <a class="sidebar-sublink {{ $isLaporanKatActive ? 'is-active' : '' }}" href="{{ route('laporan.index', ['kategori' => $laporanKategoriValue]) }}" @if ($isLaporanKatActive) aria-current="page" @endif>
                                    <span class="sublink-dot" aria-hidden="true"></span>
                                    <span>{{ $laporanKategoriLabel }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </nav>

            <div class="sidebar-account">
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    data-loading-form
                    data-confirm-title="Keluar dari {{ config('branding.short_name') }}?"
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

        <div class="dashboard-workspace {{ request()->routeIs('account.*') ? 'dashboard-workspace-account' : '' }}">
            <header class="dashboard-topbar">
                <button class="mobile-menu-button" type="button" data-sidebar-open aria-controls="dashboard-sidebar" aria-expanded="false" aria-label="Buka menu navigasi">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
                </button>
                <div class="topbar-heading">
                    @php($topbarEyebrow = trim($__env->yieldContent('topbarEyebrow', config('branding.short_name'))))
                    @if($topbarEyebrow !== '')
                        <span class="topbar-eyebrow">{{ $topbarEyebrow }}</span>
                    @endif
                    <h1>@yield('pageTitle')</h1>
                    @hasSection('topbarDescription')
                        <p class="topbar-description">@yield('topbarDescription')</p>
                    @endif
                </div>
                <details class="profile-menu" data-profile-menu>
                    <summary class="profile-menu-trigger" aria-label="Profil dan pengaturan akun">
                        <span class="topbar-avatar" aria-hidden="true">@if(auth()->user()->profile_photo_path)<img class="profile-avatar-image" src="{{ route('account.photo') }}" alt="">@else<span class="avatar-initials">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>@endif</span>
                        <span class="topbar-user-copy"><strong>{{ auth()->user()->name }}</strong><small>Akun Saya</small></span>
                        <svg class="profile-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </summary>
                    <div class="profile-menu-panel">
                        <div class="profile-menu-identity"><strong>{{ auth()->user()->name }}</strong><span>{{ auth()->user()->email }}</span><small>Username: {{ auth()->user()->username }}</small></div>
                        <a class="profile-menu-link" href="{{ route('account.edit') }}" @if(request()->routeIs('account.*')) aria-current="page" @endif>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                            <span><strong>Profil &amp; Pengaturan Akun</strong><small>Foto profil, username, dan kata sandi</small></span>
                        </a>
                    </div>
                </details>
            </header>

            <main class="dashboard-main {{ request()->routeIs('account.*') ? 'dashboard-main-account' : '' }} {{ request()->routeIs('surat.show', 'surat-saya.show') ? 'dashboard-main-detail' : '' }}" data-page-transition>
                @yield('content')
                <footer class="dashboard-footer">
                    <span>&copy; {{ date('Y') }} {{ config('branding.short_name') }}</span>
                    <span>{{ config('branding.agency') }}</span>
                </footer>
            </main>
        </div>
    </div>
    <x-confirmation-modal />
    <x-bulk-result-modal />
</body>
</html>
