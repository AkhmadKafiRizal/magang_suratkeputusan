<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Arahkan pengguna ke dashboard yang sesuai dengan role-nya.
     */
    public function redirect(Request $request): RedirectResponse
    {
        return match ($request->user()->role) {
            User::ROLE_KEPALA_BIDANG => redirect()->route('dashboard.kepala-bidang'),
            User::ROLE_PEGAWAI => redirect()->route('dashboard.pegawai'),
            default => abort(403, 'Role pengguna tidak dikenali.'),
        };
    }

    /**
     * Tampilkan dashboard pengawasan untuk Kepala Bidang.
     */
    public function kepalaBidang(): View
    {
        // Angka surat tetap nol sampai tabel surat tersedia. Struktur ini sengaja
        // dipusatkan di controller agar mudah diganti dengan query agregat nanti.
        $ringkasan = [
            'total' => 0,
            'diproses' => 0,
            'selesai' => 0,
            'belum_ditangani' => 0,
        ];

        $pegawai = User::query()
            ->where('role', User::ROLE_PEGAWAI)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('dashboard.kepala-bidang', [
            'ringkasan' => $ringkasan,
            'suratTerbaru' => collect(),
            'pegawai' => $pegawai,
            'aktivitasTerbaru' => collect(),
        ]);
    }

    /**
     * Dashboard pegawai disiapkan sebagai tujuan redirect role pegawai.
     */
    public function pegawai(): View
    {
        return view('dashboard.pegawai', [
            'ringkasan' => [
                'ditugaskan' => 0,
                'diproses' => 0,
                'selesai' => 0,
            ],
        ]);
    }
}
