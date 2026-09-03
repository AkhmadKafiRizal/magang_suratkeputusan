@extends('layouts.dashboard')

@section('title', 'Edit Surat')
@section('pageTitle', 'Data Surat')
@section('roleLabel', 'Kepala Bidang')

@section('content')
    <section class="page-heading" aria-labelledby="edit-surat-title">
        <div>
            <span class="section-kicker">Data Surat</span>
            <h2 id="edit-surat-title">Edit Surat</h2>
            <p>Perbarui informasi dan penugasan surat.</p>
        </div>
    </section>

    <section class="content-card form-card form-card-compact">
        <form method="POST" action="{{ route('kepala-bidang.surat.update', $surat) }}" novalidate>
            @csrf
            @method('PUT')
            @include('surat._form', [
                'submitLabel' => 'Simpan Perubahan',
                'cancelUrl' => route('kepala-bidang.surat.show', $surat),
            ])
        </form>
    </section>
@endsection
