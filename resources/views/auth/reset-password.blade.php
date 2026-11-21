@extends('layouts.auth-recovery')
@section('title', 'Buat Kata Sandi Baru')
@section('content')
<span class="auth-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg></span>
<h1 id="auth-title">Buat Kata Sandi Baru</h1><p class="intro">Isi kedua kolom dengan kata sandi baru yang sama, minimal 8 karakter. Gunakan ikon mata untuk memeriksa isian Anda.</p>
<form method="POST" action="{{ route('password.reset.store') }}" data-loading-form>@csrf
    <div class="field"><label for="password">Kata Sandi Baru</label><div class="input-shell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif><button class="toggle-password" type="button" data-password-toggle="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div>@error('password')<span class="error" id="password-error">{{ $message }}</span>@enderror</div>
    <div class="field"><label for="password_confirmation">Konfirmasi Kata Sandi Baru</label><div class="input-shell"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi kata sandi baru" autocomplete="new-password" required><button class="toggle-password" type="button" data-password-toggle="password_confirmation" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z"/><circle cx="12" cy="12" r="2.5"/></svg></button></div></div>
    <button class="submit" type="submit" data-loading-label="Menyimpan...">Simpan Kata Sandi Baru</button>
</form>
@auth
<a class="auth-link" href="{{ route('account.edit') }}" data-auth-transition>&larr; Kembali ke Pengaturan Akun</a>
@endauth
<script>
(() => {
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password_confirmation');
    const form = password.form;
    password.minLength = 8;
    const checkConfirmation = () => {
        confirmation.setCustomValidity(confirmation.value !== password.value
            ? 'Konfirmasi kata sandi belum sama. Isi kedua kolom dengan kata sandi yang persis sama.'
            : '');
    };
    password.addEventListener('input', checkConfirmation);
    confirmation.addEventListener('input', checkConfirmation);
    form.addEventListener('submit', (event) => {
        checkConfirmation();
        if (!form.reportValidity()) {
            event.preventDefault();
            event.stopImmediatePropagation();
        }
    }, true);
})();
</script>
@endsection
