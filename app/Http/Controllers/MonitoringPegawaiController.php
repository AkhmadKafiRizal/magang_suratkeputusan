<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MonitoringPegawaiController extends Controller
{
    public function index(): View
    {
        $pegawai = User::query()
            ->select(['id', 'name', 'email'])
            ->where('role', User::ROLE_PEGAWAI)
            ->withMonitoringCounts()
            ->orderByRaw('belum_ditangani_count + sedang_diproses_count ASC')
            ->orderBy('name')
            ->get();

        return view('monitoring-pegawai.index', [
            'pegawai' => $pegawai,
            'ringkasan' => [
                'total_pegawai' => $pegawai->count(),
                'belum_ditangani' => $pegawai->sum('belum_ditangani_count'),
                'sedang_diproses' => $pegawai->sum('sedang_diproses_count'),
                'selesai' => $pegawai->sum('selesai_count'),
            ],
        ]);
    }

    public function show(Request $request, User $pegawai): View
    {
        abort_unless($pegawai->role === User::ROLE_PEGAWAI, 404);

        $pegawai = User::query()
            ->select(['id', 'name', 'email'])
            ->withMonitoringCounts()
            ->findOrFail($pegawai->id);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $statusValid = array_key_exists($status, Surat::STATUS_LABELS);

        $surats = $pegawai->suratDitangani()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%");
                });
            })
            ->when($statusValid, fn ($query) => $query->where('status', $status))
            ->orderByRaw(
                'CASE status WHEN ? THEN 1 WHEN ? THEN 2 WHEN ? THEN 3 ELSE 4 END',
                [Surat::STATUS_BELUM_DITANGANI, Surat::STATUS_SEDANG_DIPROSES, Surat::STATUS_SELESAI]
            )
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('monitoring-pegawai.show', [
            'pegawai' => $pegawai,
            'surats' => $surats,
            'statusLabels' => Surat::STATUS_LABELS,
            'filterAktif' => $search !== '' || $statusValid,
        ]);
    }
}
