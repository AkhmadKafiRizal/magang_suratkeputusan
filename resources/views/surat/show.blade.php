@extends('layouts.dashboard')

@section('title', 'Detail Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="page-heading" aria-labelledby="detail-surat-title">
        <div>
            <span class="section-kicker">Data Surat</span>
            <h2 id="detail-surat-title">Detail Surat</h2>
            <p>Informasi lengkap surat dan progres penanganannya.</p>
        </div>
        <div class="page-actions">
            <a class="outline-button" href="{{ route('surat.index') }}">Kembali</a>
            <a class="outline-button" href="{{ route('surat.edit', $surat) }}">Edit Surat</a>
        </div>
    </section>

    <section class="content-card detail-card detail-card-contained" aria-label="Informasi surat">
        <div class="detail-card-heading">
            <div>
                <span>Nomor Surat</span>
                <h3>{{ $surat->nomor_surat }}</h3>
            </div>
            <span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span>
        </div>
        @include('surat._detail')
    </section>

    <section class="content-card detail-history-card detail-card-contained" aria-labelledby="riwayat-surat-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Jejak Pekerjaan</span>
                <h2 id="riwayat-surat-title">Riwayat Surat</h2>
                <p>Lima aktivitas terbaru yang tercatat untuk surat ini.</p>
            </div>
            <a class="outline-button compact-button" href="{{ route('riwayat-aktivitas.index', ['surat' => $surat->id]) }}">Lihat Semua Riwayat</a>
        </div>

        <div class="detail-history-list">
            @forelse ($aktivitasTerbaru as $aktivitas)
                <div class="detail-history-item">
                    <div class="detail-history-leading">
                        <x-user-avatar :user="$aktivitas->actor" size="small" :lazy="true" />
                        <time datetime="{{ $aktivitas->created_at->toIso8601String() }}">{{ $aktivitas->created_at->locale('id')->translatedFormat('H:i') }} WIB</time>
                    </div>
                    <div class="detail-history-copy">
                        <span>{{ $aktivitas->deskripsi_tampilan }}</span>
                        <small>{{ $aktivitas->actor?->name ?? 'Pelaku tidak tersedia' }}</small>
                    </div>
                </div>
            @empty
                <div class="section-empty detail-history-empty">
                    <strong>Belum ada riwayat surat</strong>
                    <span>Aktivitas perubahan dan progres surat akan tampil di sini.</span>
                </div>
            @endforelse
        </div>
    </section>
@endsection
