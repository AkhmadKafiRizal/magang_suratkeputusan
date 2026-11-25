<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkSuratRequest;
use App\Http\Requests\BulkUpdateSuratStatusRequest;
use App\Http\Requests\UpdateSuratDescriptionRequest;
use App\Http\Requests\UpdateSuratStatusRequest;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\AktivitasSuratService;
use App\Services\SuratNumberHistoryService;
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
        $perPage = $this->perPage($request);

        $query = Surat::query()
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
            ->latest('id');

        $surats = $query->paginate($perPage)->withQueryString();

        $kategoriSurats = Schema::hasTable('kategori_surats')
            ? KategoriSurat::query()->resmi()->orderBy('urutan')->get(['id', 'nama', 'slug'])
            : collect();

        return view('pegawai.surat.index', [
            'surats' => $surats,
            'kategoriSurats' => $kategoriSurats,
            'statusLabels' => Surat::STATUS_LABELS,
        ]);
    }

    private function perPage(Request $request): int
    {
        $perPage = (string) $request->query('per_page', '10');

        return in_array($perPage, ['10', '25', '50'], true) ? (int) $perPage : 10;
    }

    public function show(Surat $surat, SuratNumberHistoryService $historyService): View
    {
        Gate::authorize('viewMine', $surat);
        $surat->load(['pegawai:id,name,email,profile_photo_path', 'kategoriSurat:id,nama,slug']);

        $aktivitasTerbaru = $historyService->for($surat, 5);

        return view('pegawai.surat.show', compact('surat', 'aktivitasTerbaru'));
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

    public function bulkUpdateStatus(
        BulkUpdateSuratStatusRequest $request,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $berhasil = 0;
        $gagal = [];
        $statusTujuan = $request->validated('status');

        foreach (array_values(array_unique($request->validated('surat_ids'))) as $id) {
            $hasil = $this->advanceStatus((int) $id, $request->user()->id, $statusTujuan, $request, $aktivitasSurat);
            if ($hasil['berhasil']) {
                $berhasil++;
            } else {
                $gagal[] = $hasil['alasan'];
            }
        }

        return back()->with('bulk_result', $this->bulkResult(
            'Pembaruan Status Selesai',
            $berhasil,
            count($gagal),
            $gagal,
            'surat berhasil diperbarui',
            'surat tidak dapat diperbarui'
        ));
    }

    public function bulkAmbil(BulkSuratRequest $request, AktivitasSuratService $aktivitasSurat): RedirectResponse
    {
        $berhasil = 0;
        $gagal = [];

        foreach (array_values(array_unique($request->validated('surat_ids'))) as $id) {
            if ($this->claimSurat((int) $id, $request->user()->id, $aktivitasSurat)) {
                $berhasil++;
            } else {
                $gagal[] = 'Surat ID '.$id.' sudah tidak tersedia.';
            }
        }

        return back()->with('bulk_result', $this->bulkResult(
            'Pengambilan Surat Selesai',
            $berhasil,
            count($gagal),
            $gagal,
            'surat berhasil diambil',
            'surat gagal karena sudah tidak tersedia'
        ));
    }

    public function updateDescription(
        UpdateSuratDescriptionRequest $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $changed = DB::transaction(function () use ($request, $surat, $aktivitasSurat): bool {
            $locked = Surat::query()->lockForUpdate()->findOrFail($surat->id);
            Gate::authorize('viewMine', $locked);
            $value = $request->validated('keterangan');

            if ($locked->keterangan === $value) {
                return false;
            }

            $locked->keterangan = $value;
            $locked->save();
            $aktivitasSurat->suratDiedit($locked, $request->user(), ['keterangan']);

            return true;
        });

        return back()->with($changed ? 'success' : 'info', $changed
            ? 'Keterangan surat berhasil diperbarui.'
            : 'Keterangan tidak berubah.');
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

        $berhasil = $this->claimSurat($surat->id, $pegawai->id, $aktivitasSurat);

        if (! $berhasil) {
            return redirect()
                ->route('surat-tersedia.index')
                ->withErrors(['ambil' => 'Surat sudah tidak tersedia atau telah diambil oleh pegawai lain.']);
        }

        return redirect()
            ->route('surat-saya.index')
            ->with('success', "Surat {$surat->nomor_surat} berhasil diambil. Surat sekarang masuk ke menu Surat Saya dan siap diproses.");
    }

    /** @return array{berhasil: bool, alasan: string} */
    private function advanceStatus(
        int $suratId,
        int $pegawaiId,
        string $statusTujuan,
        Request $request,
        AktivitasSuratService $aktivitasSurat
    ): array {
        return DB::transaction(function () use ($suratId, $pegawaiId, $statusTujuan, $request, $aktivitasSurat): array {
            $surat = Surat::query()->lockForUpdate()->find($suratId);

            if (! $surat || $surat->pegawai_id !== $pegawaiId) {
                return ['berhasil' => false, 'alasan' => 'Surat tidak ditemukan atau bukan tanggung jawab Anda.'];
            }

            if (! $surat->canAdvanceTo($statusTujuan)) {
                return ['berhasil' => false, 'alasan' => "Surat {$surat->nomor_surat} tidak dapat beralih ke status tersebut."];
            }

            $sebelumnya = $surat->status;
            $surat->advanceStatus($statusTujuan);
            $surat->save();
            $aktivitasSurat->statusDiperbarui($surat, $request->user(), $sebelumnya);

            return ['berhasil' => true, 'alasan' => ''];
        });
    }

    private function claimSurat(int $suratId, int $pegawaiId, AktivitasSuratService $aktivitasSurat): bool
    {
        return DB::transaction(function () use ($suratId, $pegawaiId, $aktivitasSurat): bool {
            $surat = Surat::query()->lockForUpdate()->find($suratId);

            if (! $surat || $surat->pegawai_id !== null || $surat->status !== Surat::STATUS_BELUM_DITANGANI) {
                return false;
            }

            $surat->pegawai_id = $pegawaiId;
            $surat->ditugaskan_pada = now();
            $surat->save();
            $aktivitasSurat->suratDiambil($surat, User::query()->findOrFail($pegawaiId));

            return true;
        });
    }

    /** @param array<int, string> $reasons */
    private function bulkResult(
        string $title,
        int $success,
        int $failure,
        array $reasons,
        string $successLabel,
        string $failureLabel
    ): array {
        return [
            'title' => $title,
            'variant' => $success === 0 ? 'danger' : ($failure > 0 ? 'warning' : 'success'),
            'success_count' => $success,
            'failure_count' => $failure,
            'success_label' => $successLabel,
            'failure_label' => $failureLabel,
            'reasons' => array_values(array_unique(array_slice($reasons, 0, 5))),
        ];
    }
}
