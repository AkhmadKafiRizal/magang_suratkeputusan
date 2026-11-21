<?php

namespace App\Http\Controllers;

use App\Models\Surat;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
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

        $suratTerbaru = Surat::query()
            ->latest()
            ->limit(7)
            ->get();

        return view('dashboard.index', [
            'ringkasan' => $ringkasan,
            'suratTerbaru' => $suratTerbaru,
        ]);
    }
}
