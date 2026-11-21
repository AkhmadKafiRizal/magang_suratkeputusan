<?php

namespace Tests\Feature;

use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DataSuratExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_dialihkan_dari_export_data_surat(): void
    {
        $this->get(route('surat.export'))->assertRedirect(route('login'));
    }

    public function test_export_data_surat_mengikuti_kategori_status_dan_pencarian(): void
    {
        $logoMenu = getimagesize(public_path('images/logo-bapenda-jember.png'));
        $this->assertIsArray($logoMenu);
        $this->assertSame([200, 54], [$logoMenu[0], $logoMenu[1]]);

        $logoBapendaExcel = getimagesize(public_path('images/logo-bapenda-excel-crop.png'));
        $this->assertIsArray($logoBapendaExcel);
        $this->assertSame([44, 53], [$logoBapendaExcel[0], $logoBapendaExcel[1]]);

        $pengguna = User::factory()->create();
        $kategori = KategoriSurat::query()
            ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
            ->firstOrFail();
        $kategoriLain = KategoriSurat::query()
            ->where('slug', KategoriSurat::SLUG_PEMBATALAN_PBB)
            ->firstOrFail();

        Surat::factory()->create([
            'nomor_surat' => 'EXPORT-DATA-COCOK',
            'pemohon_pengirim' => 'Pemohon Cocok',
            'kategori_surat_id' => $kategori->id,
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => '2026-10-16 10:00:00',
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'EXPORT-DATA-STATUS-LAIN',
            'pemohon_pengirim' => 'Pemohon Cocok',
            'kategori_surat_id' => $kategori->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'EXPORT-DATA-KATEGORI-LAIN',
            'pemohon_pengirim' => 'Pemohon Cocok',
            'kategori_surat_id' => $kategoriLain->id,
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => '2026-10-16 10:00:00',
        ]);

        $response = $this->actingAs($pengguna)
            ->get(route('surat.export', [
                'kategori' => $kategori->id,
                'status' => Surat::STATUS_SELESAI,
                'search' => 'Pemohon Cocok',
            ]))
            ->assertOk()
            ->assertDownload("SIARKA-Data-Surat-{$kategori->id}.xlsx");

        $path = $response->baseResponse->getFile()->getPathname();
        $workbook = IOFactory::load($path);
        $sheet = $workbook->getActiveSheet();
        $flatValues = collect($sheet->toArray())->flatten()->filter()->implode('|');
        $identity = $sheet->getCell('A1')->getValue();

        $this->assertInstanceOf(\PhpOffice\PhpSpreadsheet\RichText\RichText::class, $identity);
        $this->assertSame("SIARKA\nSISTEM INFORMASI ARSIP DAN REGISTER KEBERATAN\nBADAN PENDAPATAN DAERAH KABUPATEN JEMBER", $identity->getPlainText());
        $this->assertSame('SIARKA Bapenda Jember', $workbook->getProperties()->getCreator());
        $this->assertSame('Badan Pendapatan Daerah Kabupaten Jember', $workbook->getProperties()->getCompany());
        $this->assertSame('Rekapitulasi Data Surat', $sheet->getCell('A4')->getValue());
        $this->assertSame(17.0, $sheet->getStyle('A4')->getFont()->getSize());
        $this->assertSame(12.0, $sheet->getStyle('A5')->getFont()->getSize());
        $this->assertArrayHasKey('A1:E2', $sheet->getMergeCells());
        $this->assertSame('center', $sheet->getStyle('A1')->getAlignment()->getHorizontal());
        $this->assertSame('KATEGORI', $sheet->getCell('A6')->getValue());
        $this->assertSame('Pengurangan PBB', $sheet->getCell('A7')->getValue());
        $this->assertStringContainsString('Status: Selesai', (string) $sheet->getCell('C7')->getValue());
        $this->assertCount(2, $sheet->getDrawingCollection());
        $this->assertSame('B1', $sheet->getDrawingCollection()[0]->getCoordinates());
        $this->assertSame(91, $sheet->getDrawingCollection()[0]->getWidth());
        $this->assertSame(89, $sheet->getDrawingCollection()[0]->getHeight());
        $this->assertSame(45, $sheet->getDrawingCollection()[0]->getOffsetX());
        $this->assertSame(12, $sheet->getDrawingCollection()[0]->getOffsetY());
        $this->assertSame('D1', $sheet->getDrawingCollection()[1]->getCoordinates());
        $this->assertSame(69, $sheet->getDrawingCollection()[1]->getWidth());
        $this->assertSame(85, $sheet->getDrawingCollection()[1]->getHeight());
        $this->assertSame(95, $sheet->getDrawingCollection()[1]->getOffsetX());
        $this->assertSame(15, $sheet->getDrawingCollection()[1]->getOffsetY());
        $this->assertSame(100, $sheet->getSheetView()->getZoomScale());
        $this->assertSame(['No.', 'Nomor Surat', 'Nama Pemohon', 'Tanggal Masuk', 'Tanggal Selesai'], $sheet->rangeToArray('A9:E9')[0]);
        $this->assertSame('E', $sheet->getHighestColumn());
        $this->assertStringContainsString('EXPORT-DATA-COCOK', $flatValues);
        $this->assertStringNotContainsString('EXPORT-DATA-STATUS-LAIN', $flatValues);
        $this->assertStringNotContainsString('EXPORT-DATA-KATEGORI-LAIN', $flatValues);
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('D10')->getNumberFormat()->getFormatCode());
        $this->assertSame('dd/mm/yyyy', $sheet->getStyle('E10')->getNumberFormat()->getFormatCode());
        $this->assertSame('center', $sheet->getStyle('A10')->getAlignment()->getHorizontal());
        $this->assertSame('A10', $sheet->getFreezePane());

        $workbook->disconnectWorksheets();
    }

    public function test_tombol_export_tersedia_di_setiap_halaman_kategori_data_surat(): void
    {
        $pengguna = User::factory()->create();

        foreach (KategoriSurat::query()->resmi()->orderBy('urutan')->get() as $kategori) {
            $nilaiKategori = $kategori->slug === KategoriSurat::SLUG_LAIN_LAIN
                ? $kategori->slug
                : $kategori->id;

            $this->actingAs($pengguna)
                ->get(route('surat.index', ['kategori' => $nilaiKategori]))
                ->assertOk()
                ->assertSee('Export Excel')
                ->assertSee('excel-export-button', false)
                ->assertSee('data-confirm-title="Export Data', false)
                ->assertSee('sesuai filter yang sedang aktif.')
                ->assertSee(route('surat.export'), false);
        }
    }
}
