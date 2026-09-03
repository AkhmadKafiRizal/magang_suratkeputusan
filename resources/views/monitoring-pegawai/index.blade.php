@extends('layouts.dashboard')

@section('title', 'Monitoring Pegawai')
@section('pageTitle', 'Monitoring Pegawai')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="monitoring-pegawai-title">
        <div>
            <span class="section-kicker">Monitoring Tim</span>
            <h2 id="monitoring-pegawai-title">Monitoring Pegawai</h2>
            <p>Pantau beban kerja dan progres setiap pegawai untuk membantu menentukan penugasan surat.</p>
        </div>
    </section>

    <section class="summary-grid" aria-label="Ringkasan pekerjaan tim">
        <article class="summary-card summary-card-total">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M17 11h5M19.5 8.5v5" stroke-linecap="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Total Pegawai</span>
                <strong>{{ number_format($ringkasan['total_pegawai'], 0, ',', '.') }}</strong>
                <small>Pegawai yang tersedia</small>
            </div>
        </article>

        <article class="summary-card summary-card-waiting">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 9v4M12 17h.01" stroke-linecap="round"/><path d="M10.3 4.2 2.7 17.4A1.7 1.7 0 0 0 4.2 20h15.6a1.7 1.7 0 0 0 1.5-2.6L13.7 4.2a2 2 0 0 0-3.4 0Z" stroke-linejoin="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Surat Belum Ditangani</span>
                <strong>{{ number_format($ringkasan['belum_ditangani'], 0, ',', '.') }}</strong>
                <small>Sudah ditugaskan ke pegawai</small>
            </div>
        </article>

        <article class="summary-card summary-card-progress">
            <div class="summary-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="summary-copy">
                <span>Sedang Diproses</span>
                <strong>{{ number_format($ringkasan['sedang_diproses'], 0, ',', '.') }}</strong>
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
                <small>Telah diselesaikan pegawai</small>
            </div>
        </article>
    </section>

    <section class="content-card monitoring-team-card" aria-labelledby="daftar-pegawai-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Beban Kerja Tim</span>
                <h2 id="daftar-pegawai-title">Daftar Pegawai</h2>
                <p>Beban kerja dihitung dari jumlah surat Belum Ditangani dan Sedang Diproses.</p>
            </div>
            <span class="record-count">{{ number_format($pegawai->count(), 0, ',', '.') }} pegawai</span>
        </div>

        @forelse ($pegawai as $anggota)
            @if ($loop->first)
                <div class="monitoring-list">
            @endif
                <article class="monitoring-employee-card">
                    <div class="monitoring-employee-header">
                        <div class="employee-identity">
                            <span class="employee-avatar" aria-hidden="true">{{ strtoupper(substr($anggota->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ $anggota->name }}</strong>
                                <small>{{ $anggota->email }}</small>
                            </span>
                        </div>
                        <div class="workload-summary">
                            <span>Beban Kerja</span>
                            <strong class="workload-badge workload-{{ $anggota->beban_kerja_class }}">{{ $anggota->beban_kerja_label }}</strong>
                        </div>
                    </div>

                    <div class="monitoring-metrics-grid" aria-label="Statistik {{ $anggota->name }}">
                        <span class="monitoring-metric">
                            <small>Belum Ditangani</small>
                            <strong>{{ number_format($anggota->belum_ditangani_count, 0, ',', '.') }}</strong>
                        </span>
                        <span class="monitoring-metric">
                            <small>Sedang Diproses</small>
                            <strong>{{ number_format($anggota->sedang_diproses_count, 0, ',', '.') }}</strong>
                        </span>
                        <span class="monitoring-metric">
                            <small>Selesai</small>
                            <strong>{{ number_format($anggota->selesai_count, 0, ',', '.') }}</strong>
                        </span>
                        <span class="monitoring-metric">
                            <small>Total Ditangani</small>
                            <strong>{{ number_format($anggota->total_ditangani_count, 0, ',', '.') }}</strong>
                        </span>
                        <span class="monitoring-metric monitoring-metric-active">
                            <small>Surat Aktif</small>
                            <strong>{{ number_format($anggota->surat_aktif_count, 0, ',', '.') }}</strong>
                        </span>
                    </div>

                    <div class="monitoring-employee-footer">
                        <span>Surat aktif merupakan pekerjaan yang belum ditangani atau sedang diproses.</span>
                        <a class="secondary-button" href="{{ route('kepala-bidang.monitoring-pegawai.show', $anggota) }}">Lihat Detail</a>
                    </div>
                </article>
            @if ($loop->last)
                </div>
            @endif
        @empty
            <div class="section-empty monitoring-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M16 20v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M18 8v6M15 11h6" stroke-linecap="round"/></svg>
                </span>
                <strong>Belum ada akun pegawai</strong>
                <span>Akun dengan role pegawai akan tampil otomatis di sini.</span>
            </div>
        @endforelse
    </section>
@endsection
