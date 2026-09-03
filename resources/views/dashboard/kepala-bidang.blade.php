@extends('layouts.dashboard')

@section('title', 'Dashboard Kepala Bidang')
@section('pageTitle', 'Dashboard Kepala Bidang')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="dashboard-intro" id="ringkasan" aria-labelledby="intro-title">
        <div>
            <span class="section-kicker">Ringkasan hari ini</span>
            <h2 id="intro-title">Pantau pekerjaan surat dalam satu tampilan.</h2>
            <p>Informasi surat dan progres pegawai akan tersaji di sini saat data mulai tersedia.</p>
        </div>
        <div class="intro-date" aria-label="Tanggal hari ini">
            <span class="intro-date-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 10h18" stroke-linecap="round"/></svg>
            </span>
            <span>
                <small>Hari ini</small>
                <strong>{{ now()->locale('id')->translatedFormat('d F Y') }}</strong>
            </span>
        </div>
    </section>

    <section class="summary-grid" aria-label="Ringkasan surat">
        <article class="summary-card summary-card-total">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M8 14h8M8 17h5" stroke-linecap="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Total Surat</span>
                <strong>{{ number_format($ringkasan['total'], 0, ',', '.') }}</strong>
                <small>Seluruh surat tercatat</small>
            </div>
        </article>

        <article class="summary-card summary-card-progress">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Sedang Diproses</span>
                <strong>{{ number_format($ringkasan['diproses'], 0, ',', '.') }}</strong>
                <small>Dalam penanganan pegawai</small>
            </div>
        </article>

        <article class="summary-card summary-card-done">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.6 2.6L16.5 9" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Selesai</span>
                <strong>{{ number_format($ringkasan['selesai'], 0, ',', '.') }}</strong>
                <small>Telah diselesaikan</small>
            </div>
        </article>

        <article class="summary-card summary-card-waiting">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.3 4.2 2.7 17.4A1.7 1.7 0 0 0 4.2 20h15.6a1.7 1.7 0 0 0 1.5-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke-linejoin="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Belum Ditangani</span>
                <strong>{{ number_format($ringkasan['belum_ditangani'], 0, ',', '.') }}</strong>
                <small>Menunggu penugasan</small>
            </div>
        </article>
    </section>

    <section class="content-card letters-card" id="surat" aria-labelledby="surat-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Pembaruan surat</span>
                <h2 id="surat-title">Daftar Surat Terbaru</h2>
                <p>Surat yang baru masuk dan perkembangan penanganannya.</p>
            </div>
            <span class="record-count">{{ $suratTerbaru->count() }} surat</span>
        </div>

        <div class="table-scroll">
            <table class="letters-table">
                <thead>
                    <tr>
                        <th>Nomor Surat</th>
                        <th>Tanggal Masuk</th>
                        <th>Perihal</th>
                        <th>Pemohon / Pengirim</th>
                        <th>Pegawai yang Menangani</th>
                        <th>Status</th>
                        <th>Aksi / Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suratTerbaru as $surat)
                        <tr>
                            <td><strong>{{ $surat->nomor_surat }}</strong></td>
                            <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                            <td>{{ $surat->perihal }}</td>
                            <td>{{ $surat->pemohon_pengirim }}</td>
                            <td>{{ $surat->pegawai?->name ?? 'Belum Ditugaskan' }}</td>
                            <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                            <td><a class="table-action" href="{{ route('kepala-bidang.surat.show', $surat) }}">Lihat Detail</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="table-empty">
                                    <span class="empty-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg>
                                    </span>
                                    <strong>Belum ada data surat</strong>
                                    <span>Data surat akan tampil setelah tersimpan di sistem.</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="dashboard-detail-grid">
        <section class="content-card" id="monitoring" aria-labelledby="monitoring-title">
            <div class="card-heading">
                <div>
                    <span class="section-kicker">Beban kerja tim</span>
                    <h2 id="monitoring-title">Monitoring Pegawai</h2>
                    <p>Lihat beban kerja dan progres setiap pegawai secara ringkas.</p>
                </div>
                <span class="record-count">{{ $pegawai->count() }} pegawai</span>
            </div>

            <div class="employee-list">
                @forelse ($pegawai as $anggota)
                    <article class="employee-card">
                        <div class="employee-identity">
                            <span class="employee-avatar" aria-hidden="true">{{ strtoupper(substr($anggota->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ $anggota->name }}</strong>
                                <small>{{ $anggota->email }}</small>
                            </span>
                        </div>
                        <div class="employee-metrics">
                            <span><small>Sedang Diproses</small><strong>{{ $anggota->sedang_diproses_count }}</strong></span>
                            <span><small>Selesai</small><strong>{{ $anggota->selesai_count }}</strong></span>
                            <span><small>Total Ditangani</small><strong>{{ $anggota->total_ditangani_count }}</strong></span>
                        </div>
                        <a class="secondary-button" href="{{ route('kepala-bidang.monitoring-pegawai.show', $anggota) }}">Lihat Detail</a>
                    </article>
                @empty
                    <div class="section-empty">
                        <span class="empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M18 8v6M15 11h6" stroke-linecap="round"/></svg>
                        </span>
                        <strong>Belum ada akun pegawai</strong>
                        <span>Akun pegawai akan muncul otomatis di sini.</span>
                    </div>
                @endforelse
            </div>
        </section>

        <section class="content-card" id="aktivitas" aria-labelledby="aktivitas-title">
            <div class="card-heading compact-heading">
                <div>
                    <span class="section-kicker">Jejak pekerjaan</span>
                    <h2 id="aktivitas-title">Aktivitas Terbaru</h2>
                    <p>Perubahan penting pada proses surat.</p>
                </div>
            </div>

            @forelse ($aktivitasTerbaru as $aktivitas)
                <div class="activity-item">
                    <span class="activity-marker" aria-hidden="true"></span>
                    <span class="activity-content">
                        <time datetime="{{ $aktivitas->created_at->toIso8601String() }}">{{ $aktivitas->created_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</time>
                        <p>{{ $aktivitas->deskripsi }}</p>
                    </span>
                </div>
            @empty
                <div class="section-empty activity-empty">
                    <span class="empty-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 12a9 9 0 1 0 3-6.7L3 8" stroke-linecap="round" stroke-linejoin="round"/><path d="M3 3v5h5M12 7v5l3 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <strong>Belum ada aktivitas</strong>
                    <span>Aktivitas penugasan dan perubahan status surat akan muncul di sini.</span>
                </div>
            @endforelse
        </section>
    </div>
@endsection
