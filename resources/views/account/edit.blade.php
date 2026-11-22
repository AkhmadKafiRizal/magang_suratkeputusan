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
    @if($user->profile_photo_path)
        <form
            id="delete-profile-photo-form"
            class="account-photo-delete-form"
            method="POST"
            action="{{ route('account.update') }}"
            data-loading-form
            data-confirm-title="Hapus Foto Profil?"
            data-confirm-message="Apakah Anda yakin ingin menghapus foto profil? Foto akan diganti dengan avatar inisial nama Anda."
            data-confirm-label="Ya, Hapus Foto"
            data-confirm-loading-label="Menghapus foto..."
            data-confirm-variant="danger"
        >
            @csrf @method('PUT')
            <input type="hidden" name="name" value="{{ $user->name }}">
            <input type="hidden" name="username" value="{{ $user->username }}">
            <input type="hidden" name="remove_photo" value="1">
        </form>
    @endif
    <form method="POST" action="{{ route('account.update') }}" enctype="multipart/form-data" data-loading-form data-dirty-form data-profile-photo-form>
        @csrf @method('PUT')
        <div class="account-photo-row">
            <span class="account-avatar" data-profile-photo-preview aria-live="polite">@if($user->profile_photo_path)<img src="{{ route('account.photo') }}" alt="Foto profil Anda">@else<span class="avatar-initials">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>@endif</span>
            <div class="form-field account-photo-field">
                <label for="photo">Pilih atau Ganti Foto Profil</label>
                <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" aria-describedby="photo-help photo-selection-status" data-profile-photo-input>
                <span class="field-help" id="photo-help">JPG, PNG, atau WebP. Maksimal 2 MB dan 4096 × 4096 piksel.</span>
                <span class="account-photo-file-name" id="photo-selection-status" data-profile-photo-file-name>Belum ada foto baru yang dipilih.</span>
                <span class="field-error" data-profile-photo-error role="alert" hidden></span>
                <div class="account-photo-actions">
                    <button class="primary-button" type="submit" data-profile-photo-save data-loading-label="Menyimpan foto..." disabled>Simpan Foto</button>
                    <button class="outline-button" type="button" data-profile-photo-cancel hidden>Batalkan Pilihan</button>
                    @if($user->profile_photo_path)
                        <button
                            class="primary-button danger-button account-photo-delete-button"
                            type="submit"
                            form="delete-profile-photo-form"
                            data-loading-label="Menghapus foto..."
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Hapus Foto Profil
                        </button>
                    @endif
                </div>
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

<div class="confirmation-modal profile-crop-modal" data-profile-crop-modal hidden>
    <button class="confirmation-backdrop" type="button" data-profile-crop-cancel aria-label="Batalkan crop foto"></button>
    <section
        class="confirmation-dialog profile-crop-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="profile-crop-title"
        aria-describedby="profile-crop-description"
    >
        <button class="confirmation-close" type="button" data-profile-crop-cancel aria-label="Tutup crop foto">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m7 7 10 10M17 7 7 17" stroke-linecap="round"/></svg>
        </button>
        <header class="profile-crop-heading">
            <span class="account-card-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M7 3v14a4 4 0 0 0 4 4h10M3 7h14a4 4 0 0 1 4 4v10" stroke-linecap="round"/><path d="M7 17 17 7" stroke-linecap="round"/></svg>
            </span>
            <div>
                <h2 id="profile-crop-title">Atur Crop Foto Profil</h2>
                <p id="profile-crop-description">Geser foto dan atur zoom hingga bagian yang diinginkan berada di dalam panduan lingkaran.</p>
            </div>
        </header>
        <div class="profile-crop-stage" data-profile-crop-stage>
            <canvas width="512" height="512" tabindex="0" role="img" aria-label="Area crop foto. Geser dengan mouse, sentuhan, atau tombol panah." data-profile-crop-canvas></canvas>
            <span class="profile-crop-guide" aria-hidden="true"></span>
        </div>
        <p class="profile-crop-hint">Geser foto dengan mouse atau sentuhan. Tombol panah juga dapat digunakan untuk mengatur posisi.</p>
        <div class="profile-crop-zoom">
            <label for="profile-crop-zoom">Zoom</label>
            <input id="profile-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" data-profile-crop-zoom>
            <output for="profile-crop-zoom" data-profile-crop-zoom-output>100%</output>
        </div>
        <p class="field-error profile-crop-error" role="alert" data-profile-crop-error hidden></p>
        <div class="confirmation-actions profile-crop-actions">
            <button class="outline-button" type="button" data-profile-crop-cancel>Batal</button>
            <button class="primary-button" type="button" data-profile-crop-apply>Terapkan Crop</button>
        </div>
    </section>
</div>
@endsection
