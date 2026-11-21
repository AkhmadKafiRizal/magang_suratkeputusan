<?php

namespace App\Http\Controllers;

use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Services\SuratExcelExportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);
        $tahunSekarang = (int) now()->year;
        $tanggalTerlama = Surat::query()
            ->where('status', Surat::STATUS_SELESAI)
            ->min('tanggal_masuk');
        $tahunTerlama = $tanggalTerlama ? (int) Carbon::parse($tanggalTerlama)->year : $tahunSekarang;
        $tahunAwal = min($tahunTerlama, $tahunSekarang, $filters['tahun']);

        $surats = $this->laporanQuery($filters)
            ->paginate(10)
            ->withQueryString();

        return view('laporan.index', [
            'surats' => $surats,
            'daftarTahun' => range($tahunSekarang, $tahunAwal),
            'filters' => $filters,
            'namaKategoriAktif' => $this->namaKategori($filters['kategori']),
            'labelPeriode' => $this->labelPeriode($filters),
        ]);
    }

    public function export(
        Request $request,
        SuratExcelExportService $excelExport
    ): BinaryFileResponse {
        $filters = $this->validatedFilters($request);
        $surats = $this->laporanQuery($filters)->get();
        $namaKategori = $this->namaKategori($filters['kategori']);
        $labelPeriode = $this->labelPeriode($filters);
        $spreadsheet = $excelExport->laporanSelesai($surats, $namaKategori, $labelPeriode);
        $tempPath = $excelExport->saveTemporary($spreadsheet, 'siarka-laporan-');

        return response()
            ->download($tempPath, $this->namaFile($filters), [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /** @return array{search: string, periode: string, tahun: int, bulan: int, kategori: string} */
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'periode' => ['nullable', Rule::in(['bulanan', 'tahunan'])],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.((int) now()->year)],
            'bulan' => ['nullable', 'integer', 'between:1,12'],
            'kategori' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'periode' => (string) ($validated['periode'] ?? 'tahunan'),
            'tahun' => (int) ($validated['tahun'] ?? now()->year),
            'bulan' => (int) ($validated['bulan'] ?? now()->month),
            'kategori' => trim((string) ($validated['kategori'] ?? '')),
        ];
    }

    /** @param array{search: string, periode: string, tahun: int, bulan: int, kategori: string} $filters */
    private function laporanQuery(array $filters): Builder
    {
        return Surat::query()
            ->with('kategoriSurat:id,nama,slug')
            ->where('status', Surat::STATUS_SELESAI)
            ->whereYear('tanggal_masuk', $filters['tahun'])
            ->when(
                $filters['periode'] === 'bulanan',
                fn (Builder $query) => $query->whereMonth('tanggal_masuk', $filters['bulan'])
            )
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('pemohon_pengirim', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%");
                });
            })
            ->when($filters['kategori'] !== '', fn (Builder $query) => $query->dalamKategori($filters['kategori']))
            ->latest('tanggal_masuk')
            ->latest('id');
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

    /** @param array{periode: string, tahun: int, bulan: int} $filters */
    private function labelPeriode(array $filters): string
    {
        if ($filters['periode'] === 'bulanan') {
            return Carbon::create($filters['tahun'], $filters['bulan'], 1)
                ->locale('id')
                ->translatedFormat('F Y');
        }

        return 'Tahun '.$filters['tahun'];
    }

    /** @param array{periode: string, tahun: int, bulan: int, kategori: string} $filters */
    private function namaFile(array $filters): string
    {
        $periode = $filters['periode'] === 'bulanan'
            ? sprintf('%04d-%02d', $filters['tahun'], $filters['bulan'])
            : (string) $filters['tahun'];
        $kategori = $filters['kategori'] !== ''
            ? preg_replace('/[^a-z0-9-]+/i', '-', $filters['kategori'])
            : 'semua-kategori';

        return "SIARKA-Rekap-Surat-Selesai-{$kategori}-{$periode}.xlsx";
    }
}
