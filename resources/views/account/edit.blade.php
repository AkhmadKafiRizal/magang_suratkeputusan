@extends('layouts.dashboard')
@section('title', 'Pengaturan Akun')
@section('pageTitle', 'Pengaturan Akun')
@section('topbarDescription', 'Kelola profil, akses masuk, dan keamanan akun Anda.')
@section('content')
@if(session('status'))<div class="alert" role="status">{{ session('status') }}</div>@endif
<div class="account-settings-grid">
<section class="content-card account-settings-card">
    <header class="account-card-heading">
        <span class="account-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
        </span>
        <div><span class="section-kicker">Profil</span><h2>Informasi akun</h2><p>Perbarui nama tampilan, foto profil, dan username untuk masuk.</p></div>
    </header>
    @if($errors->any())
        <div class="alert alert-error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" data-loading-form>
        @csrf @method('PUT')
        <div class="account-photo-row">
            <span class="account-avatar">@if($user->profile_photo_path)<img src="{{ route('account.photo') }}" alt="Foto profil Anda">@else{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}@endif</span>
            <div class="form-field"><label for="photo">Foto Profil</label><input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help"><span class="field-help" id="photo-help">JPG, PNG, atau WebP. Maksimal 2 MB dan 4096 × 4096 piksel.</span>
                @if($user->profile_photo_path)<label class="account-remove-photo"><input type="checkbox" name="remove_photo" value="1"> <span>Hapus foto profil saat disimpan</span></label>@endif
            </div>
        </div>
        <div class="form-grid">
            <div class="form-field"><label for="name">Nama</label><input id="name" name="name" value="{{ old('name', $user->name) }}" autocomplete="nickname" maxlength="255" aria-describedby="name-help" required><span class="field-help" id="name-help">Nama yang ditampilkan di aplikasi. Boleh menggunakan nama panggilan, misalnya Revina.</span></div>
            <div class="form-field"><label for="username">Username Login</label><input id="username" name="username" value="{{ old('username', $user->username) }}" autocomplete="username" minlength="3" maxlength="50" required aria-describedby="username-help"><span class="field-help" id="username-help">3–50 karakter: huruf, angka, titik, garis bawah, atau tanda hubung. Gunakan username ini saat login.</span></div>
            <div class="form-field"><label for="email">Email Terdaftar</label><input id="email" type="email" value="{{ $user->email }}" readonly><span class="field-help">Email ini digunakan untuk login dan pemulihan kata sandi.</span></div>
            <div class="form-field"><label for="profile-password">Kata Sandi Saat Ini</label><input id="profile-password" name="current_password" type="password" autocomplete="current-password"><span class="field-help">Wajib diisi jika mengubah username.</span></div>
        </div>
        <div class="form-actions"><button class="primary-button" type="submit" data-loading-label="Menyimpan...">Simpan Pengaturan</button></div>
    </form>
</section>
<section class="content-card account-settings-card account-security-card">
    <header class="account-card-heading">
        <span class="account-card-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" stroke-linecap="round"/></svg>
        </span>
        <div><span class="section-kicker">Keamanan</span><h2>Ubah kata sandi</h2><p>Gunakan kata sandi yang kuat untuk menjaga akses akun Anda.</p></div>
    </header>
    @if($errors->passwordChange->any())<div class="alert alert-error" role="alert"><ul>@foreach($errors->passwordChange->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('account.password') }}" data-loading-form>
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-field form-field-full"><label for="current-password">Kata Sandi Saat Ini</label><input id="current-password" name="current_password" type="password" autocomplete="current-password" required></div>
            <div class="form-field"><label for="new-password">Kata Sandi Baru</label><input id="new-password" name="password" type="password" autocomplete="new-password" minlength="8" required><span class="field-help">Minimal 8 karakter.</span></div>
            <div class="form-field"><label for="confirm-password">Konfirmasi Kata Sandi Baru</label><input id="confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required><span class="field-help">Masukkan kata sandi baru yang sama persis.</span></div>
        </div>
        <div class="form-actions"><button class="primary-button" type="submit" data-loading-label="Menyimpan...">Simpan Kata Sandi</button></div>
    </form>
    <div class="account-recovery-panel">
        <h4>Lupa kata sandi saat ini?</h4>
        <p>Kami akan mengirimkan kode verifikasi ke <strong>{{ $user->email }}</strong>. Setelah kode diverifikasi, Anda dapat membuat kata sandi baru.</p>
        <form method="POST" action="{{ route('account.recovery') }}" data-loading-form>
            @csrf
            <button class="outline-button" type="submit" data-loading-label="Mengirim kode...">Lupa Kata Sandi · Kirim Kode Verifikasi</button>
        </form>
    </div>
</section>
</div>
@endsection
