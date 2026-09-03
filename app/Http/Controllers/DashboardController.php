<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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
            ->where('role', User::ROLE_PEGAWAI)
            ->withCount([
                'suratDitangani as sedang_diproses_count' => fn ($query) => $query->where('status', Surat::STATUS_SEDANG_DIPROSES),
                'suratDitangani as selesai_count' => fn ($query) => $query->where('status', Surat::STATUS_SELESAI),
                'suratDitangani as total_ditangani_count',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $suratTerbaru = Surat::query()
            ->with('pegawai:id,name')
            ->latest()
            ->limit(7)
            ->get();

        $aktivitasTerbaru = $this->aktivitasSuratTerbaru();

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

    /**
     * Bentuk aktivitas ringkas dari timestamp surat tanpa menyimpan activity log baru.
     *
     * @return Collection<int, object{waktu: Carbon, deskripsi: string}>
     */
    private function aktivitasSuratTerbaru(): Collection
    {
        return Surat::query()
            ->with('pegawai:id,name')
            ->where(function ($query): void {
                $query->whereNotNull('ditugaskan_pada')
                    ->orWhereNotNull('mulai_diproses_pada')
                    ->orWhereNotNull('selesai_pada');
            })
            ->orderByRaw('COALESCE(selesai_pada, mulai_diproses_pada, ditugaskan_pada) DESC')
            ->limit(6)
            ->get([
                'id',
                'nomor_surat',
                'pegawai_id',
                'ditugaskan_pada',
                'mulai_diproses_pada',
                'selesai_pada',
            ])
            ->flatMap(function (Surat $surat): array {
                $namaPegawai = $surat->pegawai?->name ?? 'Pegawai terkait';
                $aktivitas = [];

                if ($surat->ditugaskan_pada !== null) {
                    $aktivitas[] = (object) [
                        'waktu' => $surat->ditugaskan_pada,
                        'deskripsi' => "Surat {$surat->nomor_surat} ditugaskan kepada {$namaPegawai}",
                    ];
                }

                if ($surat->mulai_diproses_pada !== null) {
                    $aktivitas[] = (object) [
                        'waktu' => $surat->mulai_diproses_pada,
                        'deskripsi' => "{$namaPegawai} mulai memproses surat {$surat->nomor_surat}",
                    ];
                }

                if ($surat->selesai_pada !== null) {
                    $aktivitas[] = (object) [
                        'waktu' => $surat->selesai_pada,
                        'deskripsi' => "{$namaPegawai} menyelesaikan surat {$surat->nomor_surat}",
                    ];
                }

                return $aktivitas;
            })
            ->sortByDesc(fn (object $aktivitas): int => $aktivitas->waktu->getTimestamp())
            ->take(6)
            ->values();
    }
}
