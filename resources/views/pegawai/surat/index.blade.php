@extends('layouts.dashboard')

@section('title', 'Surat Saya')
@section('pageTitle', 'Surat Saya')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="page-heading" aria-labelledby="surat-saya-title">
        <div>
            <span class="section-kicker">Daftar pekerjaan</span>
            <h2 id="surat-saya-title">Surat Saya</h2>
            <p>Surat yang ditugaskan kepada Anda dan progres penanganannya.</p>
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

    <section class="content-card letters-card data-letters-card" aria-label="Daftar surat saya">
        @if ($surats->isEmpty())
            <div class="section-empty employee-page-empty">
                <span class="empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7.5h6l2 2h8v10H4z" stroke-linejoin="round"/><path d="M9 14h6" stroke-linecap="round"/></svg></span>
                <strong>Belum ada surat yang ditugaskan</strong>
                <span>Tugas baru dari Kepala Bidang akan muncul di sini.</span>
            </div>
        @else
            <div class="table-scroll">
                <table class="letters-table employee-letters-table">
                    <thead><tr><th>Nomor Surat</th><th>Tanggal Masuk</th><th>Perihal</th><th>Status</th><th>Terakhir Diperbarui</th><th>Aksi</th></tr></thead>
                    <tbody>
                        @foreach ($surats as $surat)
                            <tr>
                                <td><strong>{{ $surat->nomor_surat }}</strong></td>
                                <td>{{ $surat->tanggal_masuk->locale('id')->translatedFormat('d M Y') }}</td>
                                <td class="table-text-wide">{{ $surat->perihal }}</td>
                                <td><span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span></td>
                                <td>{{ $surat->updated_at->locale('id')->translatedFormat('d M Y, H:i') }} WIB</td>
                                <td><a class="table-action" href="{{ route('pegawai.surat-saya.show', $surat) }}">Detail</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination-wrap">{{ $surats->links() }}</div>
        @endif
    </section>
@endsection
