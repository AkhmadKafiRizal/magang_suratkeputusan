<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSuratStatusRequest;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Services\AktivitasSuratService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class SuratSayaController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $kategoriFilter = (string) $request->query('kategori', '');

        $surats = Surat::query()
            ->with('kategoriSurat:id,nama,slug')
            ->where('pegawai_id', $request->user()->id)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('pemohon_pengirim', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists($status, Surat::STATUS_LABELS), fn ($query) => $query->where('status', $status))
            ->when($kategoriFilter !== '', fn ($query) => $query->dalamKategori($kategoriFilter))
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $kategoriSurats = Schema::hasTable('kategori_surats')
            ? KategoriSurat::query()->resmi()->orderBy('urutan')->get(['id', 'nama', 'slug'])
            : collect();

        return view('pegawai.surat.index', [
            'surats' => $surats,
            'kategoriSurats' => $kategoriSurats,
            'statusLabels' => Surat::STATUS_LABELS,
        ]);
    }

    public function show(Surat $surat): View
    {
        Gate::authorize('viewMine', $surat);
        $surat->load(['pegawai:id,name,email', 'kategoriSurat:id,nama,slug']);

        return view('pegawai.surat.show', compact('surat'));
    }

    public function updateStatus(
        UpdateSuratStatusRequest $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): JsonResponse|RedirectResponse {
        $statusTujuan = $request->validated('status');

        $hasil = DB::transaction(function () use ($request, $surat, $statusTujuan, $aktivitasSurat): array {
            /** @var Surat $lockedSurat */
            $lockedSurat = Surat::query()
                ->lockForUpdate()
                ->findOrFail($surat->getKey());

            Gate::authorize('updateStatus', $lockedSurat);

            if (! $lockedSurat->canAdvanceTo($statusTujuan)) {
                return ['berhasil' => false, 'surat' => $lockedSurat];
            }

            $statusSebelumnya = $lockedSurat->status;
            $lockedSurat->advanceStatus($statusTujuan);
            $lockedSurat->save();
            $aktivitasSurat->statusDiperbarui($lockedSurat, $request->user(), $statusSebelumnya);

            return ['berhasil' => true, 'surat' => $lockedSurat->fresh()];
        });

        /** @var Surat $suratTerkini */
        $suratTerkini = $hasil['surat'];

        if (! $hasil['berhasil']) {
            $pesan = 'Status surat sudah berubah atau transisi yang diminta tidak lagi tersedia. Tampilan telah disinkronkan dengan data terbaru.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $pesan,
                    'surat' => $this->statusPayload($suratTerkini),
                ], 409);
            }

            return back()
                ->withErrors(['status' => 'Perubahan status tidak diizinkan. Progres surat hanya dapat diperbarui secara berurutan.'])
                ->withInput();
        }

        $pesan = $statusTujuan === Surat::STATUS_SELESAI
            ? 'Surat berhasil diselesaikan.'
            : 'Surat berhasil mulai diproses.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'surat' => $this->statusPayload($suratTerkini),
            ]);
        }

        return redirect()
            ->route('surat-saya.show', $suratTerkini)
            ->with('success', $pesan);
    }

    /** @return array<string, string> */
    private function statusPayload(Surat $surat): array
    {
        return [
            'status' => $surat->status,
            'status_label' => match ($surat->status) {
                Surat::STATUS_SEDANG_DIPROSES => 'Sedang Diproses',
                default => $surat->status_label,
            },
            'updated_at' => $surat->updated_at->toIso8601String(),
            'updated_at_label' => $surat->updated_at->locale('id')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    public function batalkanPengambilan(
        Request $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        Gate::authorize('cancelAssignment', $surat);

        $pegawai = $request->user();

        $berhasil = DB::transaction(function () use ($surat, $pegawai, $aktivitasSurat): bool {
            /** @var Surat|null $lockedSurat */
            $lockedSurat = Surat::query()
                ->lockForUpdate()
                ->find($surat->id);

            if (
                ! $lockedSurat
                || $lockedSurat->pegawai_id !== $pegawai->id
                || $lockedSurat->status === Surat::STATUS_SELESAI
            ) {
                return false;
            }

            $statusSebelumnya = $lockedSurat->status;

            $lockedSurat->forceFill([
                'pegawai_id' => null,
                'status' => Surat::STATUS_BELUM_DITANGANI,
                'ditugaskan_pada' => null,
                'mulai_diproses_pada' => null,
                'selesai_pada' => null,
            ])->save();

            $aktivitasSurat->pengambilanDibatalkan($lockedSurat, $pegawai, $statusSebelumnya);

            return true;
        });

        if (! $berhasil) {
            return redirect()
                ->route('surat-saya.index')
                ->with('warning', 'Pengambilan surat tidak dapat dibatalkan karena status atau penangan surat telah berubah.');
        }

        return redirect()
            ->route('surat-tersedia.index')
            ->with('success', "Pengambilan surat {$surat->nomor_surat} berhasil dibatalkan. Surat kembali tersedia untuk diambil pegawai lain.");
    }

    public function tersedia(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $kategoriFilter = (string) $request->query('kategori', '');

        $surats = Surat::query()
            ->with('kategoriSurat:id,nama,slug')
            ->whereNull('pegawai_id')
            ->where('status', Surat::STATUS_BELUM_DITANGANI)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('pemohon_pengirim', 'like', "%{$search}%");
                });
            })
            ->when($kategoriFilter !== '', fn ($query) => $query->dalamKategori($kategoriFilter))
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $kategoriSurats = Schema::hasTable('kategori_surats')
            ? KategoriSurat::query()->resmi()->orderBy('urutan')->get(['id', 'nama', 'slug'])
            : collect();

        return view('pegawai.surat.tersedia', compact('surats', 'kategoriSurats'));
    }

    public function ambil(
        Request $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $pegawai = $request->user();

        $berhasil = DB::transaction(function () use ($surat, $pegawai, $aktivitasSurat): bool {
            /** @var Surat|null $lockedSurat */
            $lockedSurat = Surat::query()
                ->lockForUpdate()
                ->find($surat->id);

            if (! $lockedSurat || $lockedSurat->pegawai_id !== null || $lockedSurat->status !== Surat::STATUS_BELUM_DITANGANI) {
                return false;
            }

            $lockedSurat->pegawai_id = $pegawai->id;
            $lockedSurat->ditugaskan_pada = now();
            $lockedSurat->save();

            $aktivitasSurat->suratDiambil($lockedSurat, $pegawai);

            return true;
        });

        if (! $berhasil) {
            return redirect()
                ->route('surat-tersedia.index')
                ->withErrors(['ambil' => 'Surat sudah tidak tersedia atau telah diambil oleh pegawai lain.']);
        }

        return redirect()
            ->route('surat-saya.show', $surat)
            ->with('success', "Surat {$surat->nomor_surat} berhasil diambil. Surat sekarang masuk ke menu Surat Saya dan siap diproses.");
    }
}
