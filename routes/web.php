<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\ForgotPasswordOtpController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\MonitoringPegawaiController;
use App\Http\Controllers\RiwayatAktivitasController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\SuratSayaController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::view('/', 'auth.login');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');

    Route::post('/login', function (Request $request) {
        $validated = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $identity = Str::lower(trim($validated['username']));
        $throttleKey = $identity.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            return back()
                ->withErrors(['username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.'])
                ->onlyInput('username');
        }

        $user = User::query()->forLoginIdentity($identity)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 60);

            return back()
                ->withErrors(['username' => 'Username/email atau kata sandi tidak sesuai.'])
                ->onlyInput('username');
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        }
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('auth_feedback', [
                'title' => 'Login Berhasil',
                'message' => "Selamat datang, {$user->name}.",
            ]);
    })->name('login.store');

});

Route::get('/lupa-kata-sandi', [ForgotPasswordOtpController::class, 'create'])->name('password.request');
Route::post('/lupa-kata-sandi', [ForgotPasswordOtpController::class, 'store'])->name('password.email');
Route::get('/verifikasi-kode', [ForgotPasswordOtpController::class, 'verifyForm'])->name('password.otp.create');
Route::post('/verifikasi-kode', [ForgotPasswordOtpController::class, 'verify'])->name('password.otp.verify');
Route::post('/verifikasi-kode/kirim-ulang', [ForgotPasswordOtpController::class, 'resend'])->name('password.otp.resend');
Route::get('/kata-sandi-baru', [ForgotPasswordOtpController::class, 'resetForm'])->name('password.reset.create');
Route::post('/kata-sandi-baru', [ForgotPasswordOtpController::class, 'reset'])->name('password.reset.store');

Route::middleware('auth')->group(function () {
    Route::post('/pengaturan-akun/pemulihan', [ForgotPasswordOtpController::class, 'fromAccount'])->name('account.recovery');
    Route::get('/pengaturan-akun', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/pengaturan-akun', [AccountController::class, 'update'])->name('account.update');
    Route::put('/pengaturan-akun/kata-sandi', [AccountController::class, 'password'])->name('account.password');
    Route::get('/pengaturan-akun/foto', [AccountController::class, 'photo'])->name('account.photo');
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::prefix('data-surat')
        ->name('surat.')
        ->controller(SuratController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export-excel', 'export')->name('export');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{surat}', 'show')->name('show');
            Route::get('/{surat}/edit', 'edit')->name('edit');
            Route::put('/{surat}', 'update')->name('update');
            Route::delete('/{surat}', 'destroy')->name('destroy');
        });

    Route::prefix('monitoring-pegawai')
        ->name('monitoring-pegawai.')
        ->controller(MonitoringPegawaiController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{pegawai}', 'show')->name('show');
        });

    Route::get('/riwayat-aktivitas', [RiwayatAktivitasController::class, 'index'])
        ->name('riwayat-aktivitas.index');

    Route::prefix('surat-saya')
        ->name('surat-saya.')
        ->controller(SuratSayaController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{surat}', 'show')->name('show');
            Route::patch('/{surat}/status', 'updateStatus')->name('update-status');
            Route::patch('/{surat}/batalkan-pengambilan', 'batalkanPengambilan')->name('cancel');
        });

    Route::prefix('surat-tersedia')
        ->name('surat-tersedia.')
        ->controller(SuratSayaController::class)
        ->group(function () {
            Route::get('/', 'tersedia')->name('index');
            Route::post('/{surat}/ambil', 'ambil')->name('ambil');
        });

    Route::prefix('laporan')
        ->name('laporan.')
        ->controller(LaporanController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export-excel', 'export')->name('export');
        });

    Route::redirect('/arsip-surat', '/laporan');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()
        ->route('login')
        ->with('auth_feedback', [
            'title' => 'Logout Berhasil',
            'message' => 'Anda berhasil keluar dari sistem.',
        ]);
})->middleware('auth')->name('logout');
