@extends('layouts.dashboard')

@section('title', 'Laporan Surat')
@section('pageTitle', 'Laporan')
@section('roleLabel', 'Pegawai')

@section('content')
    @php
        $filterAktif = $filters['search'] !== ''
            || $filters['periode'] !== 'tahunan'
            || $filters['tahun'] !== (int) now()->year;
        $resetParameters = $filters['kategori'] !== '' ? ['kategori' => $filters['kategori']] : [];
        $cakupanExport = $filters['kategori'] === ''
            ? 'dari semua kategori'
            : 'dari kategori '.$namaKategoriAktif;
    @endphp

    <section class="page-heading" aria-labelledby="laporan-title">
        <div>
            <span class="section-kicker">Rekap surat selesai</span>
            <h2 id="laporan-title">{{ $filters['kategori'] !== '' ? $namaKategoriAktif : 'Semua Kategori' }}</h2>
            <p>{{ $filters['kategori'] !== '' ? 'Rekap surat selesai untuk kategori '.$namaKategoriAktif.' berdasarkan periode tanggal masuk.' : 'Rekap seluruh surat selesai berdasarkan kategori dan periode tanggal masuk.' }}</p>
        </div>
    </section>

    @if ($errors->any())
        <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="content-card filter-card" aria-label="Pencarian dan filter laporan surat">
        <form class="filter-form report-filter-form {{ $filters['periode'] === 'bulanan' ? 'is-monthly' : 'is-yearly' }}" method="GET" action="{{ route('laporan.index') }}" data-period-filter>
            <div class="filter-search">
                <div class="filter-label-row">
                    <label for="search">Pencarian</label>
                    <span>Ketik nomor surat, nama pemohon, atau keterangan.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="search" value="{{ $filters['search'] }}" placeholder="Cari isi laporan" enterkeyhint="search">
                </div>
            </div>

            <div data-period-field>
                <label for="periode">Periode</label>
                <select id="periode" name="periode" data-period-select>
                    <option value="tahunan" @selected($filters['periode'] === 'tahunan')>Tahun</option>
                    <option value="bulanan" @selected($filters['periode'] === 'bulanan')>Bulan</option>
                </select>
            </div>

            <div data-year-field>
                <label for="tahun">Tahun</label>
                <select id="tahun" name="tahun">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected($filters['tahun'] === $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>

            <div data-month-filter aria-hidden="{{ $filters['periode'] === 'bulanan' ? 'false' : 'true' }}">
                <label for="bulan">Bulan</label>
                <select id="bulan" name="bulan" @disabled($filters['periode'] !== 'bulanan')>
                    @foreach (range(1, 12) as $bulan)
                        <option value="{{ $bulan }}" @selected($filters['bulan'] === $bulan)>{{ \Carbon\Carbon::create(2000, $bulan, 1)->locale('id')->translatedFormat('F') }}</option>
                    @endforeach
                </select>
            </div>

            @if ($filters['kategori'] !== '')
                <input type="hidden" name="kategori" value="{{ $filters['kategori'] }}">
            @endif

            <div class="filter-actions report-filter-actions">
                @if ($filterAktif)
                    <x-filter-active-indicator />
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    <a class="outline-button" href="{{ route('laporan.index', $resetParameters) }}">Reset</a>
                @endif
                <button
                    class="outline-button excel-export-button"
                    type="submit"
                    formaction="{{ route('laporan.export') }}"
                    formmethod="GET"
                    data-confirm-title="Export Laporan Surat Selesai?"
                    data-confirm-message="Sebanyak {{ number_format($surats->total(), 0, ',', '.') }} surat akan diekspor {{ $cakupanExport }} untuk periode {{ $labelPeriode }}. Hanya surat berstatus Selesai yang akan masuk ke file Excel."
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

    <section class="content-card letters-card data-letters-card" aria-labelledby="daftar-laporan-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">{{ $namaKategoriAktif }} · {{ $labelPeriode }}</span>
                <h2 id="daftar-laporan-title">Surat Selesai</h2>
                <p>Daftar hanya memuat surat berstatus Selesai pada periode yang dipilih.</p>
            </div>
            <span class="record-count">{{ number_format($surats->total(), 0, ',', '.') }} surat</span>
        </div>

        @if ($surats->isEmpty())
            <div class="table-empty data-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg>
                </span>
                <strong>Belum ada surat selesai pada periode ini</strong>
                <span>Tidak ada surat selesai yang sesuai dengan kategori, periode, atau pencarian.</span>
                @if ($filterAktif)
                    <a class="primary-button" href="{{ route('laporan.index', $resetParameters) }}">Reset Filter</a>
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table data-table">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Tanggal Masuk</th>
                            <th>Pemohon / Pengirim</th>
                            <th>Kategori</th>
                            <th>Selesai Diproses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr>
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                                <td>{{ $surat->pemohon_pengirim }}</td>
                                <td>{{ $surat->kategori_label }}</td>
                                <td>{{ $surat->selesai_pada ? $surat->selesai_pada->locale('id')->translatedFormat('d M Y, H:i').' WIB' : 'Tidak tercatat' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a class="table-action" href="{{ route('surat.show', $surat) }}">Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links('components.pagination') }}</div>
        @endif
    </section>
@endsection
