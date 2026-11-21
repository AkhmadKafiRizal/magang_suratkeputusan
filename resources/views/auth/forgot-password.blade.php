@extends('layouts.auth-recovery')
@section('title', 'Lupa Kata Sandi')
@section('content')
<span class="auth-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 17v.01M8.5 10.5a3.5 3.5 0 1 1 5.7 2.72c-1.3.98-2.2 1.45-2.2 2.78" stroke-linecap="round"/><circle cx="12" cy="12" r="9"/></svg></span>
<h1 id="auth-title">Lupa Kata Sandi</h1>
<p class="intro">Masukkan email yang terdaftar pada akun Anda. Kami akan mengirimkan kode verifikasi ke email yang terhubung dengan akun tersebut.</p>
<div class="recovery-guidance" role="note"><strong>Kode belum masuk?</strong> Periksa folder Spam dan pastikan email yang dimasukkan sudah terdaftar. Jika kode tetap belum diterima, silakan hubungi developer.</div>
@if(session('status'))<div class="feedback" role="status">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('password.email') }}" data-loading-form>@csrf
    <div class="field"><label for="identity">Email Terdaftar</label><div class="input-shell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/></svg><input id="identity" name="identity" type="email" value="{{ old('identity') }}" placeholder="Masukkan email terdaftar" autocomplete="email" required autofocus @if($errors->has('identity')) aria-invalid="true" aria-describedby="identity-error" @endif></div>@error('identity')<span class="error" id="identity-error">{{ $message }}</span>@enderror</div>
    <button class="submit" type="submit" data-loading-label="Mengirim...">Kirim Kode Verifikasi</button>
</form>
<a class="auth-link" href="{{ route('login') }}" data-auth-transition>&larr; Kembali ke {{ config('branding.short_name') }}</a>
@endsection
