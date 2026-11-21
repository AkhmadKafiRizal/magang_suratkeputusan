<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuratRequest;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Services\AktivitasSuratService;
use App\Services\SuratExcelExportService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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

        $surats = $this->suratQuery($filters)
            ->latest('tanggal_masuk')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('surat.index', [
            'surats' => $surats,
            'statusLabels' => Surat::STATUS_LABELS,
            'kategoriSurats' => $this->daftarKategori(),
            'filters' => $filters,
        ]);
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
        $kategoriParam = (string) $request->query('kategori', '');
        if ($kategoriParam !== '') {
            $kategori = KategoriSurat::query()
                ->resmi()
                ->when(
                    ctype_digit($kategoriParam),
                    fn (Builder $query) => $query->whereKey((int) $kategoriParam),
                    fn (Builder $query) => $query->where('slug', $kategoriParam)
                )
                ->first();
            $surat->kategori_surat_id = $kategori?->id
                ?? KategoriSurat::query()->where('slug', KategoriSurat::SLUG_LAIN_LAIN)->value('id');
        }

        return view('surat.create', [
            'surat' => $surat,
            'kategoriSurats' => $this->daftarKategori(),
        ]);
    }

    public function store(
        SuratRequest $request,
        AktivitasSuratService $aktivitasSurat
    ): RedirectResponse {
        $surat = DB::transaction(function () use ($request, $aktivitasSurat): Surat {
            $surat = new Surat;
            $surat->status = Surat::STATUS_BELUM_DITANGANI;
            $surat->applyAdministrativeChanges($request->validated());
            $surat->save();

            $actor = $request->user();
            $aktivitasSurat->suratDicatat($surat, $actor);

            return $surat;
        });

        return redirect()
            ->route('surat.show', $surat)
            ->with('success', 'Surat berhasil dicatat.');
    }

    public function show(Surat $surat): View
    {
        $surat->load(['pegawai:id,name,email,profile_photo_path', 'kategoriSurat:id,nama,slug']);
        $aktivitasTerbaru = $surat->aktivitas()
            ->with('actor:id,name,profile_photo_path')
            ->latest('created_at')
            ->latest('id')
            ->limit(5)
            ->get();

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

        return redirect()
            ->route('surat.show', $surat)
            ->with('success', 'Data surat berhasil diperbarui.');
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
}
