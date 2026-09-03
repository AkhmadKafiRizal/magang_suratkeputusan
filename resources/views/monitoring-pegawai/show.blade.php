@extends('layouts.dashboard')

@section('title', 'Detail Monitoring Pegawai')
@section('pageTitle', 'Monitoring Pegawai')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="detail-monitoring-title">
        <div>
            <span class="section-kicker">Monitoring Pegawai</span>
            <h2 id="detail-monitoring-title">Detail Pegawai</h2>
            <p>Lihat beban kerja dan surat yang saat ini ditangani pegawai.</p>
        </div>
        <div class="page-actions">
            <a class="outline-button" href="{{ route('kepala-bidang.monitoring-pegawai.index') }}">Kembali</a>
        </div>
    </section>

    <section class="content-card monitoring-profile-card" aria-label="Identitas pegawai">
        <div class="monitoring-profile">
            <span class="monitoring-profile-avatar" aria-hidden="true">{{ strtoupper(substr($pegawai->name, 0, 1)) }}</span>
            <span class="monitoring-profile-copy">
                <small>Pegawai</small>
                <strong>{{ $pegawai->name }}</strong>
                <span>{{ $pegawai->email }}</span>
            </span>
        </div>
        <div class="workload-summary workload-summary-detail">
            <span>Beban Kerja</span>
            <strong class="workload-badge workload-{{ $pegawai->beban_kerja_class }}">{{ $pegawai->beban_kerja_label }}</strong>
            <small>Berdasarkan jumlah surat aktif.</small>
        </div>
    </section>

    <section class="monitoring-summary-grid" aria-label="Statistik pekerjaan {{ $pegawai->name }}">
        <article class="monitoring-stat-card">
            <span>Belum Ditangani</span>
            <strong>{{ number_format($pegawai->belum_ditangani_count, 0, ',', '.') }}</strong>
        </article>
        <article class="monitoring-stat-card">
            <span>Sedang Diproses</span>
            <strong>{{ number_format($pegawai->sedang_diproses_count, 0, ',', '.') }}</strong>
        </article>
        <article class="monitoring-stat-card">
            <span>Selesai</span>
            <strong>{{ number_format($pegawai->selesai_count, 0, ',', '.') }}</strong>
        </article>
        <article class="monitoring-stat-card">
            <span>Total Ditangani</span>
            <strong>{{ number_format($pegawai->total_ditangani_count, 0, ',', '.') }}</strong>
        </article>
        <article class="monitoring-stat-card monitoring-stat-active">
            <span>Surat Aktif</span>
            <strong>{{ number_format($pegawai->surat_aktif_count, 0, ',', '.') }}</strong>
        </article>
    </section>

    <section class="content-card filter-card" aria-label="Pencarian dan filter pekerjaan pegawai">
        <form class="filter-form monitoring-filter-form" method="GET" action="{{ route('kepala-bidang.monitoring-pegawai.show', $pegawai) }}">
            <div class="filter-search">
                <div class="filter-label-row">
                    <label for="q">Pencarian</label>
                    <span>Ketik kata kunci, lalu tekan Enter atau klik Terapkan.</span>
                </div>
                <div class="input-with-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4" stroke-linecap="round"/></svg>
                    <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nomor surat atau perihal" enterkeyhint="search">
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
            <div class="filter-actions">
                <button class="primary-button" type="submit">Terapkan</button>
                @if ($filterAktif)
                    <a class="outline-button" href="{{ route('kepala-bidang.monitoring-pegawai.show', $pegawai) }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    <section class="content-card letters-card monitoring-jobs-card" aria-labelledby="daftar-pekerjaan-title">
        <div class="card-heading">
            <div>
                <span class="section-kicker">Pekerjaan Pegawai</span>
                <h2 id="daftar-pekerjaan-title">Surat yang Ditangani</h2>
                <p>Daftar surat berdasarkan penugasan saat ini kepada {{ $pegawai->name }}.</p>
            </div>
            <span class="record-count">{{ number_format($surats->total(), 0, ',', '.') }} surat</span>
        </div>

        @if ($surats->isEmpty())
            <div class="table-empty data-empty">
                <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg>
                </span>
                <strong>{{ $filterAktif ? 'Tidak ada surat yang sesuai dengan filter.' : 'Belum ada surat yang ditugaskan kepada pegawai ini.' }}</strong>
                @if ($filterAktif)
                    <span>Coba ubah kata kunci atau status yang dipilih.</span>
                @else
                    <span>Surat akan tampil setelah Kepala Bidang melakukan penugasan.</span>
                @endif
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table monitoring-table">
                    <thead>
                        <tr>
                            <th>Nomor Surat</th>
                            <th>Tanggal Masuk</th>
                            <th>Perihal</th>
                            <th>Status</th>
                            <th>Ditugaskan</th>
                            <th>Mulai Diproses</th>
                            <th>Selesai Diproses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr>
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="table-text-wide">{{ $surat->perihal }}</td>
                                <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                                <td class="timestamp-cell">{{ $surat->ditugaskan_pada !== null ? $surat->ditugaskan_pada->locale('id')->translatedFormat('d M Y, H:i').' WIB' : 'Belum Ditugaskan' }}</td>
                                <td class="timestamp-cell">{{ $surat->mulai_diproses_pada !== null ? $surat->mulai_diproses_pada->locale('id')->translatedFormat('d M Y, H:i').' WIB' : 'Belum Diproses' }}</td>
                                <td class="timestamp-cell">{{ $surat->selesai_pada !== null ? $surat->selesai_pada->locale('id')->translatedFormat('d M Y, H:i').' WIB' : 'Belum Selesai' }}</td>
                                <td><a class="table-action" href="{{ route('kepala-bidang.surat.show', $surat) }}">Lihat Surat</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links() }}</div>
        @endif
    </section>
@endsection
