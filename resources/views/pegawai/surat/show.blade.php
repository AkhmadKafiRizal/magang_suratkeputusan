@extends('layouts.dashboard')

@section('title', 'Detail Surat Saya')
@section('pageTitle', 'Surat Saya')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="page-heading" aria-labelledby="detail-surat-title">
        <div>
            <span class="section-kicker">Surat Saya</span>
            <h2 id="detail-surat-title">Detail Surat</h2>
            <p>Informasi surat dan progres pekerjaan yang menjadi tanggung jawab Anda.</p>
        </div>
        <div class="page-actions">
            <a class="outline-button" href="{{ route('surat-saya.index') }}">Kembali</a>
            @if ($surat->status !== \App\Models\Surat::STATUS_SELESAI)
                <form
                    method="POST"
                    action="{{ route('surat-saya.cancel', $surat) }}"
                    data-loading-form
                    data-confirm-title="Batalkan Pengambilan Surat?"
                    data-confirm-message="Apakah Anda yakin ingin membatalkan pengambilan surat {{ $surat->nomor_surat }}? Surat akan kembali ke menu Ambil Surat dan dapat diambil pegawai lain. Riwayat pengambilan tetap tersimpan."
                    data-confirm-label="Ya, Batalkan Pengambilan"
                    data-confirm-loading-label="Membatalkan..."
                    data-confirm-variant="danger"
                >
                    @csrf
                    @method('PATCH')
                    <button class="outline-button danger-outline-button" type="submit" data-loading-label="Membatalkan...">
                        Batalkan Pengambilan
                    </button>
                </form>
            @endif
            @if ($surat->status === \App\Models\Surat::STATUS_BELUM_DITANGANI)
                <form method="POST" action="{{ route('surat-saya.update-status', $surat) }}" data-loading-form>
                    @csrf
                    @method('PATCH')
                    <input name="status" type="hidden" value="{{ \App\Models\Surat::STATUS_SEDANG_DIPROSES }}">
                    <button class="primary-button" type="submit" data-loading-label="Memproses...">Mulai Proses</button>
                </form>
            @elseif ($surat->status === \App\Models\Surat::STATUS_SEDANG_DIPROSES)
                <form
                    method="POST"
                    action="{{ route('surat-saya.update-status', $surat) }}"
                    data-loading-form
                    data-confirm-title="Tandai Surat Selesai?"
                    data-confirm-message="Setelah surat ditandai selesai, status tidak dapat dikembalikan melalui workflow saat ini."
                    data-confirm-label="Ya, Tandai Selesai"
                    data-confirm-loading-label="Menyelesaikan..."
                >
                    @csrf
                    @method('PATCH')
                    <input name="status" type="hidden" value="{{ \App\Models\Surat::STATUS_SELESAI }}">
                    <button class="primary-button" type="submit" data-loading-label="Menyelesaikan...">Tandai Selesai</button>
                </form>
            @endif
        </div>
    </section>

    @error('status')
        <div class="alert alert-error" role="alert">{{ $message }}</div>
    @enderror

    <section class="content-card detail-card detail-card-contained" aria-label="Informasi surat">
        <div class="detail-card-heading">
            <div><span>Nomor Surat</span><h3>{{ $surat->nomor_surat }}</h3></div>
            <span class="status-badge status-{{ $surat->status }}">{{ $surat->status_label }}</span>
        </div>
        @include('surat._detail')
    </section>
@endsection
