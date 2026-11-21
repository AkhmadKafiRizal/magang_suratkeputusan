@extends('layouts.dashboard')

@section('title', 'Surat Tersedia')
@section('pageTitle', 'Surat Tersedia')
@section('roleLabel', 'Pegawai')

@section('content')
    @php
        $filterAktif = filled(request('search')) || filled(request('kategori'));
    @endphp

    <section class="page-heading" aria-labelledby="surat-tersedia-title">
        <div>
            <span class="section-kicker">Penanganan mandiri</span>
            <h2 id="surat-tersedia-title">Surat Tersedia untuk Diambil</h2>
            <p>Daftar surat masuk yang belum diambil dan siap Anda tangani.</p>
        </div>
        <a class="outline-button" href="{{ route('surat-saya.index') }}">
            Kembali ke Surat Saya
        </a>
    </section>

    @if ($errors->has('ambil'))
        <div class="alert alert-error" role="alert">
            <strong>Gagal mengambil surat.</strong>
            <span>{{ $errors->first('ambil') }}</span>
        </div>
    @endif

    <section class="content-card filter-card" aria-label="Pencarian dan filter surat tersedia">
        <form class="filter-form" method="GET" action="{{ route('surat-tersedia.index') }}">
            <div class="filter-search">
                <div class="filter-label-row">
                    <label for="search">Pencarian</label>
                    <span>Ketik nomor surat atau nama pemohon.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nomor surat atau pemohon" enterkeyhint="search">
                </div>
            </div>
            <div>
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">Semua Kategori</option>
                    @foreach ($kategoriSurats as $kategori)
                        <option value="{{ $kategori->id }}" @selected((string) request('kategori') === (string) $kategori->id)>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                @if ($filterAktif)
                    <x-filter-active-indicator />
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    <a class="outline-button" href="{{ route('surat-tersedia.index') }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="content-card letters-card data-letters-card" aria-labelledby="daftar-tersedia-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Antrean tersedia</span>
                <h2 id="daftar-tersedia-title">Daftar Surat Masuk Belum Ditangani</h2>
                <p>Pilih surat yang ingin Anda tangani.</p>
            </div>
            <span class="record-count">{{ number_format($surats->total(), 0, ',', '.') }} surat</span>
        </div>

        @if ($surats->isEmpty())
            <div class="section-empty employee-page-empty">
                <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg></span>
                <strong>Tidak ada surat yang tersedia</strong>
                <span>{{ $filterAktif ? 'Tidak ada surat yang cocok dengan filter pencarian.' : 'Saat ini semua surat masuk sudah diambil.' }}</span>
                @if ($filterAktif)
                    <a class="primary-button" href="{{ route('surat-tersedia.index') }}">Reset Filter</a>
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table employee-letters-table">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Tanggal Masuk</th>
                            <th>Pemohon / Pengirim</th>
                            <th>Kategori</th>
                            <th>Status</th>
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
                                <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                                <td>
                                    <form
                                        method="POST"
                                        action="{{ route('surat-tersedia.ambil', $surat) }}"
                                        data-loading-form
                                        data-confirm-title="Apakah Anda Yakin?"
                                        data-confirm-message="Apakah Anda yakin ingin mengambil surat {{ $surat->nomor_surat }}? Surat akan masuk ke menu Surat Saya dan menjadi tanggung jawab Anda."
                                        data-confirm-label="Ya, Ambil Surat"
                                        data-confirm-loading-label="Mengambil..."
                                    >
                                        @csrf
                                        <button class="primary-button table-action-button" type="submit" data-loading-label="Mengambil...">
                                            Ambil Surat
                                        </button>
                                    </form>
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
