@extends('layouts.dashboard')

@section('title', 'Riwayat Aktivitas')
@section('pageTitle', 'Riwayat / Aktivitas')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="riwayat-aktivitas-title">
        <div>
            <span class="section-kicker">Jejak Pekerjaan</span>
            <h2 id="riwayat-aktivitas-title">Riwayat Aktivitas</h2>
            <p>Lihat perubahan dan progres pekerjaan surat secara kronologis.</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if ($suratFilter !== null)
        <div class="activity-filter-chip" role="status">
            <span>Menampilkan riwayat surat <strong>{{ $suratFilter->nomor_surat }}</strong></span>
            <a href="{{ route('kepala-bidang.riwayat-aktivitas.index', request()->except(['surat', 'page'])) }}">Hapus Filter</a>
        </div>
    @endif

    <section class="content-card filter-card" aria-label="Pencarian dan filter aktivitas">
        <form class="filter-form activity-filter-form" method="GET" action="{{ route('kepala-bidang.riwayat-aktivitas.index') }}">
            @if ($suratFilter !== null)
                <input name="surat" type="hidden" value="{{ $suratFilter->id }}">
            @endif

            <div class="filter-search activity-filter-search">
                <div class="filter-label-row">
                    <label for="q">Pencarian</label>
                    <span>Ketik kata kunci, lalu tekan Enter atau klik Terapkan.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nomor surat atau perihal" enterkeyhint="search">
                </div>
            </div>

            <div>
                <label for="jenis">Jenis Aktivitas</label>
                <select id="jenis" name="jenis">
                    <option value="">Semua Aktivitas</option>
                    @foreach ($kategoriFilter as $value => $kategori)
                        <option value="{{ $value }}" @selected(request('jenis') === $value)>{{ $kategori['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="pengguna">Pengguna</label>
                <select id="pengguna" name="pengguna">
                    <option value="">Semua Pengguna</option>
                    @foreach ($pengguna as $user)
                        <option value="{{ $user->id }}" @selected((string) request('pengguna') === (string) $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="dari">Dari Tanggal</label>
                <input id="dari" name="dari" type="date" value="{{ request('dari') }}">
            </div>

            <div>
                <label for="sampai">Sampai Tanggal</label>
                <input id="sampai" name="sampai" type="date" value="{{ request('sampai') }}">
            </div>

            <div class="filter-actions activity-filter-actions">
                @if ($filterAktif)
                    <span class="filter-active-indicator">Filter aktif</span>
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    <a class="outline-button" href="{{ route('kepala-bidang.riwayat-aktivitas.index', $suratFilter !== null ? ['surat' => $suratFilter->id] : []) }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="content-card activity-history-card" aria-labelledby="daftar-aktivitas-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Kronologi Surat</span>
                <h2 id="daftar-aktivitas-title">Daftar Aktivitas</h2>
                <p>Aktivitas terbaru ditampilkan lebih dahulu.</p>
            </div>
            <span class="record-count">{{ number_format($aktivitas->total(), 0, ',', '.') }} aktivitas</span>
        </div>

        @if ($aktivitas->isEmpty())
            <div class="section-empty activity-page-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
                <strong>Belum ada aktivitas</strong>
                <span>{{ $filterAktif || $suratFilter !== null ? 'Tidak ada aktivitas yang sesuai dengan filter.' : 'Aktivitas penugasan dan perubahan status surat akan muncul di sini.' }}</span>
                @if ($filterAktif)
                    <a class="primary-button" href="{{ route('kepala-bidang.riwayat-aktivitas.index', $suratFilter !== null ? ['surat' => $suratFilter->id] : []) }}">Reset Filter</a>
                @endif
            </div>
        @else
            <div class="activity-timeline-list">
                @foreach ($aktivitas as $item)
                    <article class="activity-timeline-item">
                        <div class="activity-timeline-time">
                            <time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</time>
                            <span>{{ $item->actor?->name ?? 'Pengguna tidak tersedia' }}</span>
                        </div>
                        <span class="activity-timeline-dot" aria-hidden="true"></span>
                        <div class="activity-timeline-copy">
                            <span class="activity-type-badge activity-type-{{ $item->tipe_badge_class }}">{{ $item->tipe_label }}</span>
                            <p>{{ $item->deskripsi }}</p>
                            <small>Surat {{ $item->surat->nomor_surat }}</small>
                        </div>
                        <a class="outline-button compact-button activity-letter-link" href="{{ route('kepala-bidang.surat.show', $item->surat) }}">Lihat Surat</a>
                    </article>
                @endforeach
            </div>
            <div class="pagination-wrap">{{ $aktivitas->links() }}</div>
        @endif
    </section>
@endsection
