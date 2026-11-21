<?php

namespace Tests\Feature;

use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_dialihkan_dari_halaman_dan_export_laporan(): void
    {
        $this->get(route('laporan.index'))->assertRedirect(route('login'));
        $this->get(route('laporan.export'))->assertRedirect(route('login'));
    }

    public function test_pengguna_dapat_mengakses_laporan_dengan_filter_bulanan_dan_tahunan(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Laporan Surat')
            ->assertSee('>Tahun<', false)
            ->assertSee('>Bulan<', false)
            ->assertDontSee('Per Tahun')
            ->assertDontSee('Per Bulan')
            ->assertDontSee('<label for="kategori">Kategori</label>', false)
            ->assertDontSee('<select id="kategori"', false)
            ->assertSee('Export Excel')
            ->assertSee('excel-export-button', false)
            ->assertSee('Export Laporan Surat Selesai?')
            ->assertSee('Sebanyak 0 surat akan diekspor dari semua kategori untuk periode Tahun '.now()->year.'.')
            ->assertSee('Hanya surat berstatus Selesai yang akan masuk ke file Excel.');
    }

    public function test_laporan_hanya_menampilkan_surat_selesai(): void
    {
        Surat::factory()->create([
            'nomor_surat' => 'LAPORAN-SELESAI',
            'tanggal_masuk' => '2026-05-10',
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => '2026-05-12 10:00:00',
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'LAPORAN-BELUM-SELESAI',
            'tanggal_masuk' => '2026-05-10',
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('laporan.index', ['tahun' => 2026]))
            ->assertOk()
            ->assertSee('LAPORAN-SELESAI')
            ->assertDontSee('LAPORAN-BELUM-SELESAI');
    }

    public function test_filter_tahunan_hanya_menampilkan_tahun_yang_dipilih(): void
    {
        $this->suratSelesai(['nomor_surat' => 'SELESAI-2025', 'tanggal_masuk' => '2025-06-15']);
        $this->suratSelesai(['nomor_surat' => 'SELESAI-2026', 'tanggal_masuk' => '2026-03-20']);

        $this->actingAs($this->pengguna())
            ->get(route('laporan.index', ['periode' => 'tahunan', 'tahun' => 2025]))
            ->assertOk()
            ->assertSee('SELESAI-2025')
            ->assertDontSee('SELESAI-2026');
    }

    public function test_filter_bulanan_hanya_menampilkan_bulan_yang_dipilih(): void
    {
        $this->suratSelesai(['nomor_surat' => 'SELESAI-MEI', 'tanggal_masuk' => '2026-05-15']);
        $this->suratSelesai(['nomor_surat' => 'SELESAI-JUNI', 'tanggal_masuk' => '2026-06-15']);

        $this->actingAs($this->pengguna())
            ->get(route('laporan.index', [
                'periode' => 'bulanan',
                'tahun' => 2026,
                'bulan' => 5,
            ]))
            ->assertOk()
            ->assertSee('SELESAI-MEI')
            ->assertDontSee('SELESAI-JUNI')
            ->assertSee('Mei 2026');
    }

    public function test_laporan_per_kategori_dan_surat_lain_lain_bekerja(): void
    {
        $pbb = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)->firstOrFail();
        $lainLain = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_LAIN_LAIN)->firstOrFail();

        $this->suratSelesai(['nomor_surat' => 'LAP-PBB', 'kategori_surat_id' => $pbb->id]);
        $this->suratSelesai(['nomor_surat' => 'LAP-LAIN-KATEGORI', 'kategori_surat_id' => $lainLain->id]);
        $this->suratSelesai(['nomor_surat' => 'LAP-TANPA-KATEGORI', 'kategori_surat_id' => null]);

        $this->actingAs($this->pengguna())
            ->get(route('laporan.index', ['kategori' => $pbb->id, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('name="kategori" value="'.$pbb->id.'"', false)
            ->assertDontSee('<select id="kategori"', false)
            ->assertSee('LAP-PBB')
            ->assertDontSee('LAP-LAIN-KATEGORI')
            ->assertDontSee('LAP-TANPA-KATEGORI');

        $this->actingAs($this->pengguna())
            ->get(route('laporan.index', ['kategori' => KategoriSurat::SLUG_LAIN_LAIN, 'tahun' => 2026]))
            ->assertOk()
            ->assertSee('LAP-LAIN-KATEGORI')
            ->assertSee('LAP-TANPA-KATEGORI')
            ->assertDontSee('LAP-PBB');
    }

    public function test_export_excel_mengikuti_filter_dan_hanya_memuat_surat_selesai(): void
    {
        $this->suratSelesai([
            'nomor_surat' => 'EXPORT-MEI',
            'tanggal_masuk' => '2026-05-15',
            'keterangan' => 'Keterangan laporan selesai.',
        ]);
        $this->suratSelesai(['nomor_surat' => 'EXPORT-JUNI', 'tanggal_masuk' => '2026-06-15']);
        Surat::factory()->create([
            'nomor_surat' => 'EXPORT-BELUM-SELESAI',
            'tanggal_masuk' => '2026-05-15',
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $response = $this->actingAs($this->pengguna())
            ->get(route('laporan.export', [
                'periode' => 'bulanan',
                'tahun' => 2026,
                'bulan' => 5,
            ]))
            ->assertOk()
            ->assertDownload('SIARKA-Rekap-Surat-Selesai-semua-kategori-2026-05.xlsx');

        $path = $response->baseResponse->getFile()->getPathname();
        $workbook = IOFactory::load($path);
        $sheet = $workbook->getActiveSheet();
        $values = $sheet->toArray();
        $flatValues = collect($values)->flatten()->filter()->implode('|');
        $identity = $sheet->getCell('A1')->getValue();

        $this->assertInstanceOf(RichText::class, $identity);
        $this->assertSame("SIARKA\nSISTEM INFORMASI ARSIP DAN REGISTER KEBERATAN\nBADAN PENDAPATAN DAERAH KABUPATEN JEMBER", $identity->getPlainText());
        $this->assertSame('SIARKA Bapenda Jember', $workbook->getProperties()->getCreator());
        $this->assertSame('Badan Pendapatan Daerah Kabupaten Jember', $workbook->getProperties()->getCompany());
        $this->assertSame('Rekapitulasi Surat Selesai', $sheet->getCell('A4')->getValue());
        $this->assertSame(17.0, $sheet->getStyle('A4')->getFont()->getSize());
        $this->assertSame(12.0, $sheet->getStyle('A5')->getFont()->getSize());
        $this->assertArrayHasKey('A1:F2', $sheet->getMergeCells());
        $this->assertSame('center', $sheet->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertSame('PERIODE', $sheet->getCell('A6')->getValue());
        $this->assertSame('Mei 2026', $sheet->getCell('A7')->getValue());
        $this->assertCount(2, $sheet->getDrawingCollection());
        $this->assertSame('C1', $sheet->getDrawingCollection()[0]->getCoordinates());
        $this->assertSame(91, $sheet->getDrawingCollection()[0]->getWidth());
        $this->assertSame(89, $sheet->getDrawingCollection()[0]->getHeight());
        $this->assertSame(21, $sheet->getDrawingCollection()[0]->getOffsetX());
        $this->assertSame(12, $sheet->getDrawingCollection()[0]->getOffsetY());
        $this->assertSame('E1', $sheet->getDrawingCollection()[1]->getCoordinates());
        $this->assertSame(69, $sheet->getDrawingCollection()[1]->getWidth());
        $this->assertSame(85, $sheet->getDrawingCollection()[1]->getHeight());
        $this->assertSame(120, $sheet->getDrawingCollection()[1]->getOffsetX());
        $this->assertSame(15, $sheet->getDrawingCollection()[1]->getOffsetY());
        $this->assertSame(100, $sheet->getSheetView()->getZoomScale());
        $this->assertSame(PageSetup::PAPERSIZE_A4, $sheet->getPageSetup()->getPaperSize());
        $this->assertSame(PageSetup::ORIENTATION_LANDSCAPE, $sheet->getPageSetup()->getOrientation());
        $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
        $this->assertSame(0, $sheet->getPageSetup()->getFitToHeight());
        $this->assertTrue($sheet->getPageSetup()->getHorizontalCentered());
        $this->assertSame(['No.', 'Nomor Surat', 'Nama Pemohon', 'Tanggal Masuk', 'Tanggal Selesai', 'Keterangan'], $sheet->rangeToArray('A9:F9')[0]);
        $this->assertSame('F', $sheet->getHighestColumn());
        $this->assertStringContainsString('EXPORT-MEI', $flatValues);
        $this->assertStringNotContainsString('EXPORT-JUNI', $flatValues);
        $this->assertStringNotContainsString('EXPORT-BELUM-SELESAI', $flatValues);
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('D10')->getNumberFormat()->getFormatCode());
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('E10')->getNumberFormat()->getFormatCode());
        $this->assertSame('Keterangan laporan selesai.', $sheet->getCell('F10')->getValue());
        $this->assertTrue($sheet->getStyle('F10')->getAlignment()->getWrapText());
        $this->assertSame('center', $sheet->getStyle('A10')->getAlignment()->getHorizontal());
        $this->assertSame('A10', $sheet->getFreezePane());

        $workbook->disconnectWorksheets();
    }

    public function test_sidebar_memuat_submenu_data_surat_dan_laporan_termasuk_surat_lain_lain(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('laporan.index'))
            ->assertOk()
            ->assertSee('Data Surat')
            ->assertSee('Laporan')
            ->assertSee('Surat Lain-lain')
            ->assertSee(route('surat.index', ['kategori' => KategoriSurat::SLUG_LAIN_LAIN]), false)
            ->assertSee(route('laporan.index', ['kategori' => KategoriSurat::SLUG_LAIN_LAIN]), false)
            ->assertDontSee('<span>Arsip Surat</span>', false);
    }

    private function pengguna(): User
    {
        return User::factory()->create();
    }

    /** @param array<string, mixed> $attributes */
    private function suratSelesai(array $attributes = []): Surat
    {
        return Surat::factory()->create(array_merge([
            'tanggal_masuk' => '2026-01-15',
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => '2026-01-20 10:00:00',
        ], $attributes));
    }
}
