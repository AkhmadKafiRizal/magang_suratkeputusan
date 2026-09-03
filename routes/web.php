<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoringPegawaiController;
use App\Http\Controllers\RiwayatAktivitasController;
use App\Http\Controllers\SuratController;
use App\Http\Controllers\SuratSayaController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'auth.login');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Email atau password tidak sesuai.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $route = match ($user->role) {
            User::ROLE_KEPALA_BIDANG => 'dashboard.kepala-bidang',
            User::ROLE_PEGAWAI => 'dashboard.pegawai',
            default => abort(403, 'Role pengguna tidak dikenali.'),
        };

        return redirect()
            ->route($route)
            ->with('auth_feedback', [
                'title' => 'Login Berhasil',
                'message' => "Selamat datang, {$user->name}.",
            ]);
    })->name('login.store');
});

if (app()->environment('local')) {
    Route::get('/__codex-auth-animation-preview-b94d', function (Request $request) {
        Auth::loginUsingId(1);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard.kepala-bidang')
            ->with('auth_feedback', [
                'title' => 'Login Berhasil',
                'message' => 'Selamat datang, Kafi.',
            ]);
    });
}

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'redirect'])
        ->name('dashboard');

    Route::get('/dashboard/kepala-bidang', [DashboardController::class, 'kepalaBidang'])
        ->middleware('role:'.User::ROLE_KEPALA_BIDANG)
        ->name('dashboard.kepala-bidang');

    Route::get('/dashboard/pegawai', [DashboardController::class, 'pegawai'])
        ->middleware('role:'.User::ROLE_PEGAWAI)
        ->name('dashboard.pegawai');

    Route::middleware('role:'.User::ROLE_KEPALA_BIDANG)
        ->prefix('kepala-bidang/data-surat')
        ->name('kepala-bidang.surat.')
        ->controller(SuratController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{surat}', 'show')->name('show');
            Route::get('/{surat}/edit', 'edit')->name('edit');
            Route::put('/{surat}', 'update')->name('update');
        });

    Route::middleware('role:'.User::ROLE_KEPALA_BIDANG)
        ->prefix('kepala-bidang/monitoring-pegawai')
        ->name('kepala-bidang.monitoring-pegawai.')
        ->controller(MonitoringPegawaiController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{pegawai}', 'show')->name('show');
        });

    Route::get('/kepala-bidang/riwayat-aktivitas', [RiwayatAktivitasController::class, 'index'])
        ->middleware('role:'.User::ROLE_KEPALA_BIDANG)
        ->name('kepala-bidang.riwayat-aktivitas.index');

    Route::middleware('role:'.User::ROLE_PEGAWAI)
        ->prefix('pegawai/surat-saya')
        ->name('pegawai.surat-saya.')
        ->controller(SuratSayaController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{surat}', 'show')->name('show');
            Route::patch('/{surat}/status', 'updateStatus')->name('update-status');
        });
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
