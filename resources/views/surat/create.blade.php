@extends('layouts.dashboard')

@section('title', 'Tambah Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Pegawai')

@section('content')
    @php
        $kategoriAktifForm = $kategoriSurats->first(fn ($k) => (string) $k->id === (string) old('kategori_surat_id', $surat->kategori_surat_id) || $k->slug === request('kategori'));
        $cancelUrl = $kategoriAktifForm
            ? route('surat.index', ['kategori' => $kategoriAktifForm->id])
            : route('surat.index');
    @endphp
    <section class="page-heading" aria-labelledby="tambah-surat-title">
        <div>
            <span class="section-kicker">{{ $kategoriAktifForm ? 'Kategori surat: ' . $kategoriAktifForm->nama : 'Data Surat' }}</span>
            <h2 id="tambah-surat-title">Tambah Surat</h2>
            <p>{{ $kategoriAktifForm ? 'Catat informasi surat masuk untuk kategori ' . $kategoriAktifForm->nama . '.' : 'Catat informasi surat masuk. Surat dapat diambil sendiri melalui menu Ambil Surat.' }}</p>
        </div>
    </section>

    <section class="content-card form-card letter-form-card">
        <form method="POST" action="{{ route('surat.store') }}" data-loading-form data-dirty-form>
            @csrf
            @include('surat._form', [
                'submitLabel' => 'Simpan Surat',
                'loadingLabel' => 'Menyimpan...',
                'cancelUrl' => $cancelUrl,
            ])
        </form>
    </section>
@endsection
