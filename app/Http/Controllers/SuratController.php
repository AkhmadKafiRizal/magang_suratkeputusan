<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratRequest;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Services\AktivitasSuratService;
use App\Services\SuratExcelExportService;
use App\Services\SuratNumberHistoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class SuratController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $perPage = $this->perPage($request);
        $query = $this->suratQuery($filters)
            ->latest('tanggal_masuk')
            ->latest('id');

        $surats = $query->paginate($perPage)->withQueryString();

        return view('surat.index', [
            'surats' => $surats,
            'statusLabels' => Surat::STATUS_LABELS,
            'kategoriSurats' => $this->daftarKategori(),
            'filters' => $filters,
        ]);
    }

    private function perPage(Request $request): int
    {
        $perPage = (string) $request->query('per_page', '10');

        return in_array($perPage, ['10', '25', '50'], true) ? (int) $perPage : 10;
    }

    public function export(
        Request $request,
        SuratExcelExportService $excelExport
    ): BinaryFileResponse {
        $filters = $this->filters($request);
        $surats = $this->suratQuery($filters)
            ->latest('tanggal_masuk')
            ->latest('id')
            ->get();
        $namaKategori = $this->namaKategori($filters['kategori']);
        $spreadsheet = $excelExport->dataSurat(
            $surats,
            $namaKategori,
            $this->ringkasanFilter($filters)
        );
        $tempPath = $excelExport->saveTemporary($spreadsheet, 'siarka-data-surat-');

        return response()
            ->download($tempPath, $this->namaFileExport($filters), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    public function create(Request $request): View
    {
        $surat = new Surat;
        $kategoriKonteks = $this->kategoriDariKonteks((string) $request->query('kategori', ''));
        $surat->kategori_surat_id = $kategoriKonteks?->id;

        return view('surat.create', [
            'surat' => $surat,
            'kategoriSurats' => $this->daftarKategori(),
        ]);
    }

    public function store(
        SuratRequest $request,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $submitAction = $request->validated('submit_action', 'save');

        try {
            $surat = DB::transaction(function () use ($request, $aktivitasSurat): Surat {
                $surat = new Surat;
                $surat->status = Surat::STATUS_BELUM_DITANGANI;
                $surat->applyAdministrativeChanges($request->validated());
                $surat->save();
                $aktivitasSurat->suratDicatat($surat, $request->user());

                return $surat;
            });
        } catch (QueryException $exception) {
            if ($this->isActiveNumberConflict($exception)) {
                return back()->withErrors([
                    'nomor_surat' => 'Nomor surat tersebut sudah digunakan oleh surat aktif. Gunakan nomor surat lain.',
                ])->withInput();
            }

            throw $exception;
        }

        if ($submitAction === 'save_and_add') {
            return redirect()
                ->route('surat.create', ['kategori' => $surat->kategori_surat_id])
                ->with('success', 'Surat berhasil disimpan. Silakan tambahkan surat berikutnya.');
        }

        return redirect()
            ->route('surat.index')
            ->with('success', 'Surat berhasil dicatat.');
    }

    public function show(Surat $surat, SuratNumberHistoryService $historyService): View
    {
        $surat->load(['pegawai:id,name,email,profile_photo_path', 'kategoriSurat:id,nama,slug']);
        $aktivitasTerbaru = $historyService->for($surat, 5);

        return view('surat.show', compact('surat', 'aktivitasTerbaru'));
    }

    public function edit(Surat $surat): View
    {
        return view('surat.edit', [
            'surat' => $surat,
            'kategoriSurats' => $this->daftarKategori(),
        ]);
    }

    public function update(
        SuratRequest $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        try {
            DB::transaction(function () use ($request, $surat, $aktivitasSurat): void {
                $actor = $request->user();
                $nilaiLama = $this->nilaiAdministratif($surat);

                $surat->applyAdministrativeChanges($request->validated());
                $nilaiBaru = $this->nilaiAdministratif($surat);
                $fieldBerubah = [];

                foreach ($nilaiLama as $field => $nilai) {
                    if ($nilai !== $nilaiBaru[$field]) {
                        $fieldBerubah[] = $field;
                    }
                }

                if ($fieldBerubah !== []) {
                    $surat->save();
                    $aktivitasSurat->suratDiedit($surat, $actor, $fieldBerubah);
                }
            });
        } catch (QueryException $exception) {
            if ($this->isActiveNumberConflict($exception)) {
                return back()->withErrors([
                    'nomor_surat' => 'Nomor surat tersebut sudah digunakan oleh surat aktif. Gunakan nomor surat lain.',
                ])->withInput();
            }

            throw $exception;
        }

        return redirect()
            ->route('surat.show', $surat)
            ->with('success', 'Data surat berhasil diperbarui.');
    }

    public function descriptionSuggestions(Request $request): JsonResponse
    {
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 200);

        $suggestions = Surat::query()
            ->whereNotNull('keterangan')
            ->whereRaw("TRIM(keterangan) <> ''")
            ->when($query !== '', fn (Builder $builder) => $builder->where('keterangan', 'like', '%'.$query.'%'))
            ->latest('updated_at')
            ->limit(50)
            ->pluck('keterangan')
            ->map(fn (string $value) => trim($value))
            ->filter()
            ->unique(fn (string $value) => mb_strtolower($value))
            ->take(10)
            ->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function destroy(
        Request $request,
        Surat $surat,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        Gate::authorize('delete', $surat);

        try {
            DB::transaction(function () use ($request, $surat, $aktivitasSurat): void {
                $aktivitasSurat->suratDihapus($surat, $request->user());
                $surat->delete();
            });
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Surat gagal dihapus. Silakan coba lagi.');
        }

        return redirect()
            ->route('dashboard')
            ->with('success', 'Surat berhasil dihapus.');
    }

    /**
     * @return Collection<int, KategoriSurat>
     */
    private function daftarKategori(): Collection
    {
        return KategoriSurat::query()
            ->resmi()
            ->orderBy('urutan')
            ->get(['id', 'nama']);
    }

    private function kategoriDariKonteks(string $kategoriParam): ?KategoriSurat
    {
        $kategoriParam = mb_substr(trim($kategoriParam), 0, 100);

        if ($kategoriParam === '') {
            return null;
        }

        return KategoriSurat::query()
            ->resmi()
            ->when(
                ctype_digit($kategoriParam),
                fn (Builder $query) => $query->whereKey((int) $kategoriParam),
                fn (Builder $query) => $query->where('slug', $kategoriParam)
            )
            ->first();
    }

    /** @return array{search: string, status: string, kategori: string} */
    private function filters(Request $request): array
    {
        return [
            'search' => mb_substr(trim((string) $request->query('search', '')), 0, 255),
            'status' => (string) $request->query('status', ''),
            'kategori' => mb_substr(trim((string) $request->query('kategori', '')), 0, 100),
        ];
    }

    /** @param array{search: string, status: string, kategori: string} $filters */
    private function suratQuery(array $filters): Builder
    {
        return Surat::query()
            ->with('kategoriSurat:id,nama,slug')
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('pemohon_pengirim', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            })
            ->when(
                array_key_exists($filters['status'], Surat::STATUS_LABELS),
                fn (Builder $query) => $query->where('status', $filters['status'])
            )
            ->when(
                $filters['kategori'] !== '',
                fn (Builder $query) => $query->dalamKategori($filters['kategori'])
            );
    }

    private function namaKategori(string $filter): string
    {
        if ($filter === '') {
            return 'Semua Kategori';
        }

        if ($filter === KategoriSurat::SLUG_LAIN_LAIN) {
            return 'Surat Lain-lain';
        }

        $kategori = ctype_digit($filter)
            ? KategoriSurat::query()->resmi()->find((int) $filter)
            : KategoriSurat::query()->resmi()->where('slug', $filter)->first();

        return $kategori?->nama ?? 'Surat Lain-lain';
    }

    /** @param array{search: string, status: string, kategori: string} $filters */
    private function ringkasanFilter(array $filters): string
    {
        $ringkasan = [];

        if (array_key_exists($filters['status'], Surat::STATUS_LABELS)) {
            $ringkasan[] = 'Status: '.Surat::STATUS_LABELS[$filters['status']];
        }

        if ($filters['search'] !== '') {
            $ringkasan[] = 'Pencarian: '.$filters['search'];
        }

        return $ringkasan !== [] ? implode(' | ', $ringkasan) : 'Semua data';
    }

    /** @param array{kategori: string} $filters */
    private function namaFileExport(array $filters): string
    {
        $kategori = $filters['kategori'] !== ''
            ? preg_replace('/[^a-z0-9-]+/i', '-', $filters['kategori'])
            : 'semua-kategori';

        return "SIARKA-Data-Surat-{$kategori}.xlsx";
    }

    /** @return array<string, string|null> */
    private function nilaiAdministratif(Surat $surat): array
    {
        return [
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_masuk' => $surat->tanggal_masuk?->format('Y-m-d'),
            'pemohon_pengirim' => $surat->pemohon_pengirim,
            'kategori_surat_id' => $surat->kategori_surat_id ? (string) $surat->kategori_surat_id : null,
            'keterangan' => $surat->keterangan,
        ];
    }

    private function isActiveNumberConflict(QueryException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062
            && (
                str_contains($exception->getMessage(), 'surats_nomor_surat_aktif_unique')
                || str_contains($exception->getMessage(), 'surats_nomor_surat_unique')
            );
    }
}
