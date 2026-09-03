@extends('layouts.dashboard')

@section('title', 'Tambah Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="tambah-surat-title">
        <div>
            <span class="section-kicker">Data Surat</span>
            <h2 id="tambah-surat-title">Tambah Surat</h2>
            <p>Catat informasi surat dan tentukan pegawai yang menangani.</p>
        </div>
    </section>

    <section class="content-card form-card form-card-compact">
        <form method="POST" action="{{ route('kepala-bidang.surat.store') }}" data-loading-form data-dirty-form>
            @csrf
            @include('surat._form', [
                'submitLabel' => 'Simpan Surat',
                'loadingLabel' => 'Menyimpan...',
                'cancelUrl' => route('kepala-bidang.surat.index'),
            ])
        </form>
    </section>
@endsection
