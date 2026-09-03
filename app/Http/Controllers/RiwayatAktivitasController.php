<?php

namespace App\Http\Controllers;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiwayatAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'jenis' => ['nullable', Rule::in(array_keys(AktivitasSurat::KATEGORI_FILTER))],
            'pengguna' => ['nullable', 'integer', 'exists:users,id'],
            'dari' => ['nullable', 'date_format:Y-m-d'],
            'sampai' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('dari'), ['after_or_equal:dari']),
            ],
            'surat' => ['nullable', 'integer', 'exists:surats,id'],
        ], [
            'dari.date_format' => 'Tanggal awal harus berupa tanggal yang valid.',
            'sampai.date_format' => 'Tanggal akhir harus berupa tanggal yang valid.',
            'sampai.after_or_equal' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            'pengguna.exists' => 'Pengguna yang dipilih tidak tersedia.',
            'surat.exists' => 'Surat yang dipilih tidak tersedia.',
        ]);

        $search = trim((string) ($filters['q'] ?? ''));
        $kategori = (string) ($filters['jenis'] ?? '');
        $tipeAktivitas = AktivitasSurat::KATEGORI_FILTER[$kategori]['tipe'] ?? [];
        $penggunaId = isset($filters['pengguna']) ? (int) $filters['pengguna'] : null;
        $suratId = isset($filters['surat']) ? (int) $filters['surat'] : null;

        $aktivitas = AktivitasSurat::query()
            ->with([
                'actor:id,name,role',
                'surat:id,nomor_surat,perihal',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->whereHas('surat', function ($query) use ($search): void {
                        $query->where('nomor_surat', 'like', "%{$search}%")
                            ->orWhere('perihal', 'like', "%{$search}%");
                    })->orWhereHas('actor', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($tipeAktivitas !== [], fn ($query) => $query->whereIn('tipe', $tipeAktivitas))
            ->when($penggunaId !== null, fn ($query) => $query->where('user_id', $penggunaId))
            ->when(isset($filters['dari']), fn ($query) => $query->whereDate('created_at', '>=', $filters['dari']))
            ->when(isset($filters['sampai']), fn ($query) => $query->whereDate('created_at', '<=', $filters['sampai']))
            ->when($suratId !== null, fn ($query) => $query->where('surat_id', $suratId))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('riwayat-aktivitas.index', [
            'aktivitas' => $aktivitas,
            'kategoriFilter' => AktivitasSurat::KATEGORI_FILTER,
            'pengguna' => User::query()
                ->whereIn('role', [User::ROLE_KEPALA_BIDANG, User::ROLE_PEGAWAI])
                ->orderBy('name')
                ->get(['id', 'name', 'role']),
            'suratFilter' => $suratId !== null
                ? Surat::query()->find($suratId, ['id', 'nomor_surat'])
                : null,
            'filterAktif' => collect($filters)
                ->except('surat')
                ->contains(fn ($value) => $value !== null && $value !== ''),
        ]);
    }
}
