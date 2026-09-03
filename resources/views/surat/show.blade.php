@extends('layouts.dashboard')

@section('title', 'Detail Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="detail-surat-title">
        <div>
            <span class="section-kicker">Data Surat</span>
            <h2 id="detail-surat-title">Detail Surat</h2>
            <p>Informasi lengkap surat dan progres penanganannya.</p>
        </div>
        <div class="page-actions">
            <a class="outline-button" href="{{ route('kepala-bidang.surat.index') }}">Kembali</a>
            <a class="outline-button" href="{{ route('kepala-bidang.surat.edit', $surat) }}">Edit Surat</a>
            @if ($surat->pegawai_id === null)
                <a class="primary-button" href="{{ route('kepala-bidang.surat.edit', $surat) }}#pegawai_id">Tugaskan Pegawai</a>
            @endif
        </div>
    </section>

    @if (session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif

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
@endsection
