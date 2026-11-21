<?php

namespace Tests\Feature;

use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriSuratTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(): User
    {
        return User::factory()->create([
        ]);
    }

    private function pegawai(): User
    {
        return User::factory()->create([
        ]);
    }

    private function seedKategori(): void
    {
        $kategori = [
            ['nama' => 'Pengurangan PBB',   'slug' => 'pengurangan-pbb',   'urutan' => 1],
            ['nama' => 'Pembatalan PBB',    'slug' => 'pembatalan-pbb',    'urutan' => 2],
            ['nama' => 'Keberatan PBB',     'slug' => 'keberatan-pbb',     'urutan' => 3],
            ['nama' => 'Pengurangan BPHTB', 'slug' => 'pengurangan-bphtb', 'urutan' => 4],
            ['nama' => 'PBJT',              'slug' => 'pbjt',              'urutan' => 5],
            ['nama' => 'Surat Lain-lain',   'slug' => 'lain-lain',         'urutan' => 6],
        ];

        foreach ($kategori as $item) {
            KategoriSurat::query()->firstOrCreate(['slug' => $item['slug']], $item);
        }
    }

    public function test_enam_kategori_tersedia_di_sistem(): void
    {
        $this->seedKategori();

        $this->assertDatabaseCount('kategori_surats', 6);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'Pengurangan PBB']);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'Pembatalan PBB']);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'Keberatan PBB']);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'Pengurangan BPHTB']);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'PBJT']);
        $this->assertDatabaseHas('kategori_surats', ['nama' => 'Surat Lain-lain']);
    }

    public function test_pengguna_dapat_membuat_surat_dengan_kategori_valid(): void
    {
        $this->seedKategori();
        $kategori = KategoriSurat::query()->first();

        $response = $this->actingAs($this->pengguna())
            ->post(route('surat.store'), [
                'nomor_surat' => '001/PBB/2026',
                'tanggal_masuk' => '2026-10-15',
                'pemohon_pengirim' => 'Wajib Pajak A',
                'kategori_surat_id' => $kategori->id,
            ]);

        $surat = Surat::query()->where('nomor_surat', '001/PBB/2026')->first();
        $this->assertNotNull($surat);
        $this->assertSame($kategori->id, $surat->kategori_surat_id);
        $response->assertRedirect(route('surat.show', $surat));
    }

    public function test_validasi_menolak_kategori_yang_tidak_ada(): void
    {
        $this->seedKategori();

        $response = $this->actingAs($this->pengguna())
            ->post(route('surat.store'), [
                'nomor_surat' => '002/INVALID/2026',
                'tanggal_masuk' => '2026-10-15',
                'pemohon_pengirim' => 'Wajib Pajak B',
                'kategori_surat_id' => 99999,
            ]);

        $response->assertSessionHasErrors('kategori_surat_id');
        $this->assertDatabaseMissing('surats', ['nomor_surat' => '002/INVALID/2026']);
    }

    public function test_kategori_wajib_dipilih_saat_menambah_surat(): void
    {
        $this->seedKategori();

        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), [
                'nomor_surat' => '003/TANPA-KATEGORI/2026',
                'tanggal_masuk' => '2026-10-15',
                'pemohon_pengirim' => 'Wajib Pajak C',
            ])
            ->assertSessionHasErrors([
                'kategori_surat_id' => 'Kategori surat wajib dipilih.',
            ]);

        $this->assertDatabaseMissing('surats', ['nomor_surat' => '003/TANPA-KATEGORI/2026']);
    }

    public function test_form_tambah_memakai_dropdown_enam_kategori_resmi(): void
    {
        $this->seedKategori();
        KategoriSurat::query()->create([
            'nama' => 'Kategori Tidak Resmi',
            'slug' => 'kategori-tidak-resmi',
            'urutan' => 99,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('<select id="kategori_surat_id" name="kategori_surat_id" required', false)
            ->assertDontSee('<input id="kategori_surat_id"', false)
            ->assertSeeInOrder([
                'Pengurangan PBB',
                'Pembatalan PBB',
                'Keberatan PBB',
                'Pengurangan BPHTB',
                'PBJT',
                'Surat Lain-lain',
            ])
            ->assertDontSee('Kategori Tidak Resmi')
            ->assertSee('Pilih Surat Lain-lain apabila surat tidak termasuk lima kategori utama.');
    }

    public function test_surat_lama_tanpa_kategori_tetap_dapat_dibaca_dengan_aman(): void
    {
        $surat = Surat::factory()->create([
            'nomor_surat' => 'SURAT-LAMA-001',
            'kategori_surat_id' => null,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Surat Lain-lain');
    }

    public function test_filter_kategori_hanya_menampilkan_surat_dari_kategori_tersebut(): void
    {
        $this->seedKategori();
        $kategoriA = KategoriSurat::query()->where('slug', 'pengurangan-pbb')->first();
        $kategoriB = KategoriSurat::query()->where('slug', 'pbjt')->first();

        Surat::factory()->create([
            'nomor_surat' => 'SURAT-PBB-1',
            'kategori_surat_id' => $kategoriA->id,
        ]);

        Surat::factory()->create([
            'nomor_surat' => 'SURAT-PBJT-1',
            'kategori_surat_id' => $kategoriB->id,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index', ['kategori' => $kategoriA->id]))
            ->assertOk()
            ->assertSee('SURAT-PBB-1')
            ->assertDontSee('SURAT-PBJT-1');
    }

    public function test_pencarian_bekerja_bersama_filter_kategori(): void
    {
        $this->seedKategori();
        $kategoriA = KategoriSurat::query()->where('slug', 'pengurangan-pbb')->first();

        Surat::factory()->create([
            'nomor_surat' => '111/TARGET/2026',
            'pemohon_pengirim' => 'Pak Budi',
            'kategori_surat_id' => $kategoriA->id,
        ]);

        Surat::factory()->create([
            'nomor_surat' => '222/LAIN/2026',
            'pemohon_pengirim' => 'Pak Joko',
            'kategori_surat_id' => $kategoriA->id,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index', [
                'kategori' => $kategoriA->id,
                'search' => 'TARGET',
            ]))
            ->assertOk()
            ->assertSee('111/TARGET/2026')
            ->assertDontSee('222/LAIN/2026');
    }

    public function test_menu_surat_lain_lain_memuat_kategori_lain_lain_dan_surat_tanpa_kategori(): void
    {
        $this->seedKategori();
        $kategoriLainLain = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_LAIN_LAIN)->firstOrFail();
        $kategoriPbb = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)->firstOrFail();
        $kategoriTidakResmi = KategoriSurat::query()->create([
            'nama' => 'Kategori Lama Tidak Resmi',
            'slug' => 'kategori-lama-tidak-resmi',
            'urutan' => 99,
        ]);

        Surat::factory()->create(['nomor_surat' => 'LAIN-RESMI', 'kategori_surat_id' => $kategoriLainLain->id]);
        Surat::factory()->create(['nomor_surat' => 'LAIN-TANPA-KATEGORI', 'kategori_surat_id' => null]);
        Surat::factory()->create(['nomor_surat' => 'LAIN-KATEGORI-TIDAK-RESMI', 'kategori_surat_id' => $kategoriTidakResmi->id]);
        Surat::factory()->create(['nomor_surat' => 'BUKAN-LAIN', 'kategori_surat_id' => $kategoriPbb->id]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index', ['kategori' => KategoriSurat::SLUG_LAIN_LAIN]))
            ->assertOk()
            ->assertSee('LAIN-RESMI')
            ->assertSee('LAIN-TANPA-KATEGORI')
            ->assertSee('LAIN-KATEGORI-TIDAK-RESMI')
            ->assertDontSee('BUKAN-LAIN');
    }
}
