@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('pageTitle', 'Dashboard')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="dashboard-intro" id="ringkasan" aria-labelledby="intro-title">
        <div>
            <span class="section-kicker">Ringkasan hari ini</span>
            <h2 id="intro-title">Pantau pekerjaan surat dalam satu tampilan.</h2>
            <p>Ringkasan dan perkembangan penanganan surat tersaji dalam satu halaman.</p>
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
                <small>Tersedia untuk diambil</small>
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
                        <th>Pemohon / Pengirim</th>
                        <th>Status</th>
                        <th>Aksi / Detail</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suratTerbaru as $surat)
                        <tr>
                            <td><strong>{{ $surat->nomor_surat }}</strong></td>
                            <td>
                                <span class="table-date-time">
                                    <span>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</span>
                                    <small>{{ $surat->created_at->format('H:i') }} WIB</small>
                                </span>
                            </td>
                            <td>{{ $surat->pemohon_pengirim }}</td>
                            <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                            <td>
                                <div class="table-actions dashboard-letter-actions">
                                    <a class="table-action" href="{{ route('surat.show', $surat) }}">Lihat Detail</a>
                                    <form
                                        method="POST"
                                        action="{{ route('surat.destroy', $surat) }}"
                                        data-confirm-title="Konfirmasi Hapus Surat"
                                        data-confirm-message="{{ "Apakah Anda yakin ingin menghapus surat berikut?\n\nNomor Surat: {$surat->nomor_surat}\nPerihal: ".(filled($surat->perihal) ? $surat->perihal : 'Tidak dicantumkan') }}"
                                        data-confirm-label="Ya, Hapus Surat"
                                        data-confirm-loading-label="Menghapus..."
                                        data-confirm-variant="danger"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button class="table-delete-action" type="submit" data-loading-label="Menghapus...">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3m-8 0 1 13h8l1-13M10 11v5M14 11v5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            <span>Hapus</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
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

@endsection
