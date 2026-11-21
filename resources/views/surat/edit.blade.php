@extends('layouts.dashboard')

@section('title', 'Edit Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Pegawai')

@section('content')
    <section class="page-heading" aria-labelledby="edit-surat-title">
        <div>
            <span class="section-kicker">Data Surat</span>
            <h2 id="edit-surat-title">Edit Surat</h2>
            <p>Perbarui informasi administratif surat.</p>
        </div>
    </section>

    <section class="content-card form-card letter-form-card">
        <form
            method="POST"
            action="{{ route('surat.update', $surat) }}"
            data-loading-form
            data-dirty-form
        >
            @csrf
            @method('PUT')
            @include('surat._form', [
                'submitLabel' => 'Simpan Perubahan',
                'loadingLabel' => 'Memperbarui...',
                'cancelUrl' => route('surat.show', $surat),
            ])
        </form>
    </section>
@endsection
