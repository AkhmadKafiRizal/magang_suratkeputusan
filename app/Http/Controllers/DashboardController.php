<?php

namespace App\Http\Controllers;

use App\Models\AktivitasSurat;
use App\Models\Surat;
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
        $jumlahSurat = Surat::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as diproses', [Surat::STATUS_SEDANG_DIPROSES])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as selesai', [Surat::STATUS_SELESAI])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as belum_ditangani', [Surat::STATUS_BELUM_DITANGANI])
            ->first();

        $ringkasan = [
            'total' => (int) $jumlahSurat->total,
            'diproses' => (int) $jumlahSurat->diproses,
            'selesai' => (int) $jumlahSurat->selesai,
            'belum_ditangani' => (int) $jumlahSurat->belum_ditangani,
        ];

        $pegawai = User::query()
            ->select(['id', 'name', 'email'])
            ->where('role', User::ROLE_PEGAWAI)
            ->withMonitoringCounts()
            ->orderBy('name')
            ->get();

        $suratTerbaru = Surat::query()
            ->with('pegawai:id,name')
            ->latest()
            ->limit(7)
            ->get();

        $aktivitasTerbaru = AktivitasSurat::query()
            ->with([
                'actor:id,name',
                'surat:id,nomor_surat',
            ])
            ->latest('created_at')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('dashboard.kepala-bidang', [
            'ringkasan' => $ringkasan,
            'suratTerbaru' => $suratTerbaru,
            'pegawai' => $pegawai,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ]);
    }

    /**
     * Dashboard pegawai disiapkan sebagai tujuan redirect role pegawai.
     */
    public function pegawai(Request $request): View
    {
        $suratPegawai = Surat::query()
            ->where('pegawai_id', $request->user()->id);

        $jumlahSurat = (clone $suratPegawai)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as diproses', [Surat::STATUS_SEDANG_DIPROSES])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as selesai', [Surat::STATUS_SELESAI])
            ->first();

        return view('dashboard.pegawai', [
            'ringkasan' => [
                'ditugaskan' => (int) $jumlahSurat->total,
                'diproses' => (int) $jumlahSurat->diproses,
                'selesai' => (int) $jumlahSurat->selesai,
            ],
            'suratSaya' => (clone $suratPegawai)->latest()->limit(7)->get(),
        ]);
    }
}
