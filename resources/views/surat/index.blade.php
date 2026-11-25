@extends('layouts.dashboard')

@section('title', 'Data Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Pegawai')

@section('content')
    @php
        $kategoriAktif = filled(request('kategori'))
            ? $kategoriSurats->first(fn ($k) => (string) $k->id === (string) request('kategori') || $k->slug === request('kategori'))
            : null;
        $filterAktif = filled(request('search')) || filled(request('status'));
    @endphp

    <section class="page-heading" aria-labelledby="data-surat-title">
        <div>
            <span class="section-kicker">{{ $kategoriAktif ? 'Kategori surat' : 'Manajemen surat' }}</span>
            <h2 id="data-surat-title">{{ $kategoriAktif ? $kategoriAktif->nama : 'Daftar Surat' }}</h2>
            <p>{{ $kategoriAktif ? 'Kelola dan pantau seluruh surat masuk dalam kategori ' . $kategoriAktif->nama . '.' : 'Kelola data surat dan pantau penanganannya.' }}</p>
        </div>
        <a class="primary-button" href="{{ route('surat.create', $kategoriAktif ? ['kategori' => $kategoriAktif->id] : []) }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            Tambah Surat{{ $kategoriAktif ? ' ' . $kategoriAktif->nama : '' }}
        </a>
    </section>

    <section class="content-card filter-card" aria-label="Pencarian dan filter surat">
        <form class="filter-form data-filter-form" method="GET" action="{{ route('surat.index') }}">
            @if (in_array((string) request('per_page'), ['10', '25', '50'], true))
                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
            @endif
            <div class="filter-search">
                <div class="filter-label-row">
                    <label for="search">Pencarian</label>
                    <span>Ketik kata kunci, lalu tekan Enter atau klik Terapkan.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nomor surat, pemohon, atau keterangan" enterkeyhint="search">
                </div>
            </div>
            <div>
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua Status</option>
                    @foreach ($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if (request()->filled('kategori'))
                <input type="hidden" name="kategori" value="{{ request('kategori') }}">
            @endif
            <div class="filter-actions">
                @if ($filterAktif)
                    <x-filter-active-indicator />
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    @if ($kategoriAktif)
                        <a class="outline-button" href="{{ route('surat.index', ['kategori' => $kategoriAktif->id]) }}">Reset Pencarian</a>
                    @else
                        <a class="outline-button" href="{{ route('surat.index') }}">Reset</a>
                    @endif
                @endif
                <button
                    class="outline-button excel-export-button"
                    type="submit"
                    formaction="{{ route('surat.export') }}"
                    formmethod="GET"
                    data-confirm-title="Export Data {{ $kategoriAktif?->nama ?? 'Semua Surat' }}?"
                    data-confirm-message="Anda akan mengekspor {{ number_format($surats->total(), 0, ',', '.') }} surat {{ $kategoriAktif ? 'kategori '.$kategoriAktif->nama : 'dari semua kategori' }} sesuai filter yang sedang aktif."
                    data-confirm-label="Export Excel"
                    data-confirm-loading-label="Menyiapkan Excel..."
                    data-confirm-variant="excel"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 3v12M7 10l5 5 5-5M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Export Excel
                </button>
            </div>
        </form>
    </section>

    <section class="content-card letters-card data-letters-card" aria-labelledby="daftar-surat-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">{{ $kategoriAktif ? 'Data kategori' : 'Arsip tercatat' }}</span>
                <h2 id="daftar-surat-title">{{ $kategoriAktif ? 'Daftar ' . $kategoriAktif->nama : 'Daftar Data Surat' }}</h2>
                <p>{{ $kategoriAktif ? 'Menampilkan surat masuk khusus kategori ' . $kategoriAktif->nama . ' sesuai pencarian dan filter.' : 'Menampilkan surat sesuai pencarian dan filter yang dipilih.' }}</p>
            </div>
            <span class="record-count">{{ number_format($surats->total(), 0, ',', '.') }} surat</span>
        </div>

        @if ($surats->isEmpty())
            <div class="table-empty data-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg>
                </span>
                <strong>{{ $kategoriAktif ? 'Belum ada surat pada kategori ' . $kategoriAktif->nama : 'Belum ada data surat' }}</strong>
                <span>{{ $filterAktif ? 'Tidak ada surat yang sesuai dengan pencarian atau filter.' : ($kategoriAktif ? 'Tambahkan surat pertama khusus untuk kategori ini.' : 'Tambahkan surat pertama untuk mulai melakukan pencatatan.') }}</span>
                @if ($kategoriAktif && ! $filterAktif)
                    <a class="primary-button" href="{{ route('surat.create', ['kategori' => $kategoriAktif->id]) }}">Tambah Surat {{ $kategoriAktif->nama }}</a>
                @elseif ($filterAktif)
                    <a class="primary-button" href="{{ $kategoriAktif ? route('surat.index', ['kategori' => $kategoriAktif->id]) : route('surat.index') }}">Reset Filter</a>
                @else
                    <a class="primary-button" href="{{ route('surat.create') }}">Tambah Surat</a>
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table data-table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nomor Surat</th>
                            <th>Nama Pemohon</th>
                            <th>Status</th>
                            <th>Tanggal Masuk</th>
                            <th>Tanggal Selesai</th>
                            <th>Keterangan</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr>
                                <td>{{ $surats->firstItem() + $loop->index }}</td>
                                <td><a class="table-action" href="{{ route('surat.show', $surat) }}"><strong>{{ $surat->nomor_surat }}</strong></a></td>
                                <td>{{ $surat->pemohon_pengirim }}</td>
                                <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                                <td>
                                    <span class="table-date-time">
                                        <span>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</span>
                                        <small>{{ $surat->created_at->format('H:i') }} WIB</small>
                                    </span>
                                </td>
                                <td>
                                    @if ($surat->selesai_pada)
                                        <span class="table-date-time">
                                            <span>{{ $surat->selesai_pada->locale('id')->translatedFormat('d M Y') }}</span>
                                            <small>{{ $surat->selesai_pada->format('H:i') }} WIB</small>
                                        </span>
                                    @else
                                        Tidak tercatat
                                    @endif
                                </td>
                                <td class="table-text-wide">{{ filled($surat->keterangan) ? $surat->keterangan : '-' }}</td>
                                <td><a class="table-action" href="{{ route('surat.show', $surat) }}">Lihat Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links('components.pagination', ['showPerPage' => true]) }}</div>
        @endif
    </section>
@endsection
