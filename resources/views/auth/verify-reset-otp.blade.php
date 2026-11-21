@extends('layouts.auth-recovery')
@section('title', 'Verifikasi Kode')
@section('content')
<span class="auth-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="4" y="3" width="16" height="18" rx="3"/><path d="M8 8h8M8 12h2M12 12h2M16 12h.01" stroke-linecap="round"/></svg></span>
<h1 id="auth-title">Verifikasi Kode</h1>
<p class="intro">Kode verifikasi telah dikirim ke email yang terdaftar pada akun Anda. Masukkan kode 6 digit untuk melanjutkan.</p>
@if(session('status'))<div class="feedback" role="status">{{ session('status') }}</div>@endif
<form method="POST" action="{{ route('password.otp.verify') }}" data-loading-form>@csrf
    <div class="field"><label for="otp">Kode OTP 6 digit</label><input class="otp-input" id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" maxlength="6" pattern="[0-9]{6}" data-digits-only placeholder="000000" required autofocus @if($errors->has('otp')) aria-invalid="true" aria-describedby="otp-error otp-hint" @else aria-describedby="otp-hint" @endif><span class="hint" id="otp-hint">Kode berlaku selama 10 menit.</span>@error('otp')<span class="error" id="otp-error">{{ $message }}</span>@enderror</div>
    <button class="submit" type="submit" data-loading-label="Memverifikasi...">Verifikasi Kode</button>
</form>
<form class="secondary-form" method="POST" action="{{ route('password.otp.resend') }}" data-loading-form>@csrf<button class="secondary-button" type="submit" data-loading-label="Mengirim ulang...">Kirim Ulang Kode</button></form>
<a class="auth-link" href="{{ auth()->check() ? route('account.edit') : route('password.request') }}" data-auth-transition>&larr; {{ auth()->check() ? 'Kembali ke Pengaturan Akun' : 'Gunakan email lain' }}</a>
@endsection
