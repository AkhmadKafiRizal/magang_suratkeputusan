@extends('layouts.dashboard')

@section('title', 'Data Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    @php
        $filterAktif = filled(request('search')) || filled(request('status')) || filled(request('pegawai'));
    @endphp

    <section class="page-heading" aria-labelledby="data-surat-title">
        <div>
            <span class="section-kicker">Manajemen surat</span>
            <h2 id="data-surat-title">Daftar Surat</h2>
            <p>Kelola data surat dan penugasan pegawai.</p>
        </div>
        <a class="primary-button" href="{{ route('kepala-bidang.surat.create') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            Tambah Surat
        </a>
    </section>

    <section class="content-card filter-card" aria-label="Pencarian dan filter surat">
        <form class="filter-form" method="GET" action="{{ route('kepala-bidang.surat.index') }}">
            <div class="filter-search">
                <div class="filter-label-row">
                    <label for="search">Pencarian</label>
                    <span>Ketik kata kunci, lalu tekan Enter atau klik Terapkan.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Cari nomor surat, perihal, atau pemohon" enterkeyhint="search">
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
            <div>
                <label for="pegawai">Pegawai</label>
                <select id="pegawai" name="pegawai">
                    <option value="">Semua Pegawai</option>
                    <option value="belum_ditugaskan" @selected(request('pegawai') === 'belum_ditugaskan')>Belum Ditugaskan</option>
                    @foreach ($pegawai as $anggota)
                        <option value="{{ $anggota->id }}" @selected((string) request('pegawai') === (string) $anggota->id)>{{ $anggota->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                @if ($filterAktif)
                    <span class="filter-active-indicator">Filter aktif</span>
                @endif
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    <a class="outline-button" href="{{ route('kepala-bidang.surat.index') }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="content-card letters-card data-letters-card" aria-labelledby="daftar-surat-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Arsip tercatat</span>
                <h2 id="daftar-surat-title">Daftar Data Surat</h2>
                <p>Menampilkan surat sesuai pencarian dan filter yang dipilih.</p>
            </div>
            <span class="record-count">{{ number_format($surats->total(), 0, ',', '.') }} surat</span>
        </div>

        @if ($surats->isEmpty())
            <div class="table-empty data-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg>
                </span>
                <strong>Belum ada data surat</strong>
                <span>{{ $filterAktif ? 'Tidak ada surat yang sesuai dengan pencarian atau filter.' : 'Tambahkan surat pertama untuk mulai melakukan pencatatan dan penugasan.' }}</span>
                <a class="primary-button" href="{{ $filterAktif ? route('kepala-bidang.surat.index') : route('kepala-bidang.surat.create') }}">{{ $filterAktif ? 'Reset Filter' : 'Tambah Surat' }}</a>
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table data-table">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Tanggal Masuk</th>
                            <th>Perihal</th>
                            <th>Pemohon / Pengirim</th>
                            <th>Pegawai yang Menangani</th>
                            <th>Status</th>
                            <th>Terakhir Diperbarui</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr>
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="table-text-wide">{{ $surat->perihal }}</td>
                                <td>{{ $surat->pemohon_pengirim }}</td>
                                <td>{{ $surat->pegawai?->name ?? 'Belum Ditugaskan' }}</td>
                                <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                                <td>{{ $surat->updated_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</td>
                                <td>
                                    <div class="table-actions">
                                        <a class="table-action" href="{{ route('kepala-bidang.surat.show', $surat) }}">Detail</a>
                                        <a class="table-action" href="{{ route('kepala-bidang.surat.edit', $surat) }}">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links() }}</div>
        @endif
    </section>
@endsection
