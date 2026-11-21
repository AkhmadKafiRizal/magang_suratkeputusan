@extends('layouts.dashboard')

@section('title', 'Surat Saya')
@section('pageTitle', 'Surat Saya')
@section('roleLabel', 'Pegawai')

@section('content')
    @php
        $kategoriAktif = filled(request('kategori'))
            ? $kategoriSurats->first(fn ($k) => (string) $k->id === (string) request('kategori') || $k->slug === request('kategori'))
            : null;
        $filterAktif = filled(request('search')) || filled(request('status'));
    @endphp

    <section class="page-heading" aria-labelledby="surat-saya-title">
        <div>
            <span class="section-kicker">{{ $kategoriAktif ? 'Kategori surat saya' : 'Daftar pekerjaan' }}</span>
            <h2 id="surat-saya-title">{{ $kategoriAktif ? $kategoriAktif->nama : 'Surat Saya' }}</h2>
            <p>{{ $kategoriAktif ? 'Surat yang Anda ambil dalam kategori ' . $kategoriAktif->nama . ' beserta progres penanganannya.' : 'Surat yang Anda ambil beserta progres penanganannya.' }}</p>
        </div>
        <a class="primary-button" href="{{ route('surat-tersedia.index') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            Ambil Surat Masuk
        </a>
    </section>

    <section class="content-card filter-card" aria-label="Pencarian dan filter surat saya">
        <form class="filter-form" method="GET" action="{{ route('surat-saya.index') }}">
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
                        <option value="{{ $kategori->id }}" @selected((string) request('kategori') === (string) $kategori->id || request('kategori') === $kategori->slug)>{{ $kategori->nama }}</option>
                    @endforeach
                </select>
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
            <div class="filter-actions">
                @if ($filterAktif)
                    <x-filter-active-indicator />
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    @if ($kategoriAktif && (filled(request('search')) || filled(request('status'))))
                        <a class="outline-button" href="{{ route('surat-saya.index', ['kategori' => $kategoriAktif->id]) }}">Reset Pencarian</a>
                    @else
                        <a class="outline-button" href="{{ route('surat-saya.index') }}">Reset</a>
                    @endif
                @endif
            </div>
        </form>
    </section>

    <section class="content-card letters-card data-letters-card" aria-label="Daftar surat saya">
        @if ($surats->isEmpty())
            <div class="section-empty employee-page-empty">
                <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg></span>
                <strong>{{ $kategoriAktif ? 'Belum ada surat yang Anda ambil untuk kategori ' . $kategoriAktif->nama : 'Anda belum mengambil surat' }}</strong>
                <span>{{ $filterAktif ? 'Tidak ada surat yang sesuai dengan kriteria filter.' : ($kategoriAktif ? 'Anda belum mengambil surat pada kategori ini.' : 'Surat yang Anda ambil akan muncul di sini.') }}</span>
                @if ($filterAktif)
                    <a class="primary-button" href="{{ $kategoriAktif ? route('surat-saya.index', ['kategori' => $kategoriAktif->id]) : route('surat-saya.index') }}">Reset Filter</a>
                @else
                    <a class="primary-button" href="{{ route('surat-tersedia.index') }}">Lihat Surat Masuk Tersedia</a>
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table employee-letters-table">
                    <thead><tr><th>Nomor Surat</th><th>Tanggal Masuk</th><th>Pemohon / Pengirim</th><th>Kategori</th><th>Status</th><th>Terakhir Diperbarui</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr data-surat-row="{{ $surat->id }}">
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="table-text-wide">{{ $surat->pemohon_pengirim }}</td>
                                <td>{{ $surat->kategori_label }}</td>
                                <td>
                                    @if ($surat->status === \App\Models\Surat::STATUS_SELESAI)
                                        <span class="status-badge status-selesai">Selesai</span>
                                    @else
                                        @php
                                            $sedangDiproses = $surat->status === \App\Models\Surat::STATUS_SEDANG_DIPROSES;
                                            $statusTujuan = $sedangDiproses
                                                ? \App\Models\Surat::STATUS_SELESAI
                                                : \App\Models\Surat::STATUS_SEDANG_DIPROSES;
                                            $statusLabel = $sedangDiproses ? 'Sedang Diproses' : 'Belum Ditangani';
                                        @endphp
                                        <form
                                            class="quick-status-form"
                                            method="POST"
                                            action="{{ route('surat-saya.update-status', $surat) }}"
                                            data-quick-status-form
                                            data-current-status="{{ $surat->status }}"
                                            data-current-label="{{ $statusLabel }}"
                                            data-surat-number="{{ $surat->nomor_surat }}"
                                            @if ($sedangDiproses)
                                                data-confirm-title="Konfirmasi Penyelesaian Surat"
                                                data-confirm-message="Apakah Anda yakin penanganan surat ini sudah selesai? Nomor Surat: {{ $surat->nomor_surat }}"
                                                data-confirm-label="Ya, Selesaikan"
                                                data-confirm-loading-label="Menyelesaikan..."
                                            @endif
                                        >
                                            @csrf
                                            @method('PATCH')
                                            <input name="status" type="hidden" value="{{ $statusTujuan }}">
                                            <button
                                                class="status-badge quick-status-button status-{{ $surat->status }}"
                                                type="submit"
                                                data-loading-label="{{ $sedangDiproses ? 'Menyelesaikan...' : 'Memproses...' }}"
                                                aria-label="{{ $sedangDiproses ? 'Selesaikan' : 'Mulai proses' }} surat {{ $surat->nomor_surat }}"
                                            >{{ $statusLabel }}</button>
                                        </form>
                                    @endif
                                </td>
                                <td>
                                    <time data-status-updated-at datetime="{{ $surat->updated_at->toIso8601String() }}">
                                        {{ $surat->updated_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB
                                    </time>
                                </td>
                                <td><a class="table-action" href="{{ route('surat-saya.show', $surat) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links('components.pagination') }}</div>
        @endif
    </section>
@endsection
