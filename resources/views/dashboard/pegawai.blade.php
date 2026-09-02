@extends('layouts.dashboard')

@section('title', 'Dashboard Pegawai')
@section('pageTitle', 'Dashboard Pegawai')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="dashboard-intro" id="ringkasan" aria-labelledby="intro-title">
        <div>
            <span class="section-kicker">Ruang kerja pegawai</span>
            <h2 id="intro-title">Kelola surat yang menjadi tanggung jawab Anda.</h2>
            <p>Daftar tugas akan muncul di sini setelah data surat tersedia dan ditugaskan.</p>
        </div>
        <div class="intro-date" aria-label="Tanggal hari ini">
            <span class="intro-date-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round"/></svg>
            </span>
            <span><small>Hari ini</small><strong>{{ now()->locale('id')->translatedFormat('d F Y') }}</strong></span>
        </div>
    </section>

    <section class="summary-grid summary-grid-three" aria-label="Ringkasan tugas">
        <article class="summary-card summary-card-total">
            <div class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/></svg></div>
            <div class="summary-copy"><span>Surat Ditugaskan</span><strong>{{ $ringkasan['ditugaskan'] }}</strong><small>Seluruh tugas Anda</small></div>
        </article>
        <article class="summary-card summary-card-progress">
            <div class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2" stroke-linecap="round"/></svg></div>
            <div class="summary-copy"><span>Sedang Diproses</span><strong>{{ $ringkasan['diproses'] }}</strong><small>Masih dikerjakan</small></div>
        </article>
        <article class="summary-card summary-card-done">
            <div class="summary-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.6 2.6L16.5 9" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
            <div class="summary-copy"><span>Selesai</span><strong>{{ $ringkasan['selesai'] }}</strong><small>Telah diselesaikan</small></div>
        </article>
    </section>

    <section class="content-card" id="surat" aria-labelledby="surat-title">
        <div class="card-heading">
            <div><span class="section-kicker">Daftar pekerjaan</span><h2 id="surat-title">Surat Saya</h2><p>Surat yang ditugaskan kepada Anda akan tampil di bagian ini.</p></div>
            <span class="record-count">0 surat</span>
        </div>
        <div class="section-empty employee-page-empty">
            <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg></span>
            <strong>Belum ada surat yang ditugaskan</strong>
            <span>Tugas baru dari Kepala Bidang akan muncul di sini.</span>
        </div>
    </section>

    <section class="content-card employee-activity" id="aktivitas" aria-labelledby="aktivitas-title">
        <div class="card-heading compact-heading">
            <div><span class="section-kicker">Jejak pekerjaan</span><h2 id="aktivitas-title">Aktivitas Terbaru</h2><p>Aktivitas pada surat Anda akan tercatat secara otomatis.</p></div>
        </div>
        <div class="section-empty">
            <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <strong>Belum ada aktivitas</strong>
            <span>Pembaruan tugas Anda akan muncul di sini.</span>
        </div>
    </section>
@endsection
