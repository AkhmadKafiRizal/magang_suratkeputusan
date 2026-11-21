<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AmbilSuratTest extends TestCase
{
    use RefreshDatabase;

    private function pegawai(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
        ], $attributes));
    }

    public function test_guest_dialihkan_dari_halaman_surat_tersedia(): void
    {
        $this->get(route('surat-tersedia.index'))->assertRedirect(route('login'));
    }

    public function test_pegawai_dapat_melihat_daftar_surat_yang_belum_diambil(): void
    {
        $pegawai = $this->pegawai();

        $suratTersedia = Surat::factory()->create([
            'nomor_surat' => 'TERSEDIA-001',
            'pegawai_id' => null,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $suratSudahDiambil = Surat::factory()->create([
            'nomor_surat' => 'SUDAH-ADA-PETUGAS',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        Surat::factory()->create([
            'nomor_surat' => 'STATUS-SUDAH-DIPROSES',
            'pegawai_id' => null,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($pegawai)
            ->get(route('surat-tersedia.index'))
            ->assertOk()
            ->assertSee('TERSEDIA-001')
            ->assertDontSee('SUDAH-ADA-PETUGAS')
            ->assertDontSee('STATUS-SUDAH-DIPROSES')
            ->assertDontSee('Ditugaskan');
    }

    public function test_pegawai_dapat_mengambil_surat_yang_tersedia(): void
    {
        $pegawai = $this->pegawai(['name' => 'Ahmad Pegawai']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'KLAIM-001',
            'pegawai_id' => null,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $response = $this->actingAs($pegawai)
            ->post(route('surat-tersedia.ambil', $surat));

        $surat->refresh();
        $this->assertSame($pegawai->id, $surat->pegawai_id);
        $this->assertNotNull($surat->ditugaskan_pada);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);

        $response
            ->assertRedirect(route('surat-saya.show', $surat))
            ->assertSessionHas('success', "Surat {$surat->nomor_surat} berhasil diambil. Surat sekarang masuk ke menu Surat Saya dan siap diproses.");

        $this->assertDatabaseHas('aktivitas_surats', [
            'surat_id' => $surat->id,
            'user_id' => $pegawai->id,
            'tipe' => AktivitasSurat::TIPE_SURAT_DIAMBIL,
        ]);
    }

    public function test_daftar_surat_tersedia_menampilkan_dialog_konfirmasi_pengambilan(): void
    {
        $pegawai = $this->pegawai();
        Surat::factory()->create(['nomor_surat' => 'KONFIRMASI-001']);

        $this->actingAs($pegawai)
            ->get(route('surat-tersedia.index'))
            ->assertOk()
            ->assertSee('data-confirm-title="Apakah Anda Yakin?"', false)
            ->assertSee('Apakah Anda yakin ingin mengambil surat KONFIRMASI-001?')
            ->assertSee('data-confirm-label="Ya, Ambil Surat"', false);
    }

    public function test_pegawai_dapat_membatalkan_pengambilan_dan_surat_kembali_tersedia(): void
    {
        $pegawai = $this->pegawai(['name' => 'Pegawai Pertama']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'BATAL-001',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'ditugaskan_pada' => now()->subHour(),
            'mulai_diproses_pada' => now()->subMinutes(30),
        ]);

        $this->actingAs($pegawai)
            ->get(route('surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('Batalkan Pengambilan')
            ->assertSee('data-confirm-title="Batalkan Pengambilan Surat?"', false)
            ->assertSee('data-confirm-variant="danger"', false);

        $response = $this->actingAs($pegawai)
            ->patch(route('surat-saya.cancel', $surat));

        $response
            ->assertRedirect(route('surat-tersedia.index'))
            ->assertSessionHas('success');

        $surat->refresh();
        $this->assertNull($surat->pegawai_id);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertNull($surat->ditugaskan_pada);
        $this->assertNull($surat->mulai_diproses_pada);
        $this->assertNull($surat->selesai_pada);

        $this->assertDatabaseHas('aktivitas_surats', [
            'surat_id' => $surat->id,
            'user_id' => $pegawai->id,
            'tipe' => AktivitasSurat::TIPE_PENGAMBILAN_DIBATALKAN,
        ]);

        $this->actingAs($pegawai)
            ->get(route('surat-tersedia.index'))
            ->assertOk()
            ->assertSee('BATAL-001');
    }

    public function test_riwayat_pengambilan_tetap_utuh_setelah_dibatalkan_dan_diambil_pegawai_lain(): void
    {
        $pegawaiPertama = $this->pegawai(['name' => 'Pegawai Pertama']);
        $pegawaiKedua = $this->pegawai(['name' => 'Pegawai Kedua']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'PINDAH-001',
            'pegawai_id' => null,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $this->actingAs($pegawaiPertama)
            ->post(route('surat-tersedia.ambil', $surat))
            ->assertRedirect(route('surat-saya.show', $surat));

        $this->actingAs($pegawaiPertama)
            ->patch(route('surat-saya.cancel', $surat->fresh()))
            ->assertRedirect(route('surat-tersedia.index'));

        $this->actingAs($pegawaiKedua)
            ->post(route('surat-tersedia.ambil', $surat->fresh()))
            ->assertRedirect(route('surat-saya.show', $surat));

        $surat->refresh();
        $this->assertSame($pegawaiKedua->id, $surat->pegawai_id);

        $riwayat = AktivitasSurat::query()
            ->where('surat_id', $surat->id)
            ->orderBy('id')
            ->get(['user_id', 'tipe'])
            ->map(fn (AktivitasSurat $aktivitas) => [$aktivitas->user_id, $aktivitas->tipe])
            ->all();

        $this->assertSame([
            [$pegawaiPertama->id, AktivitasSurat::TIPE_SURAT_DIAMBIL],
            [$pegawaiPertama->id, AktivitasSurat::TIPE_PENGAMBILAN_DIBATALKAN],
            [$pegawaiKedua->id, AktivitasSurat::TIPE_SURAT_DIAMBIL],
        ], $riwayat);
    }

    public function test_pegawai_lain_tidak_dapat_membatalkan_pengambilan_surat(): void
    {
        $pemilik = $this->pegawai();
        $pegawaiLain = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pemilik->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
            'ditugaskan_pada' => now(),
        ]);

        $this->actingAs($pegawaiLain)
            ->patch(route('surat-saya.cancel', $surat))
            ->assertForbidden();

        $this->assertSame($pemilik->id, $surat->fresh()->pegawai_id);
        $this->assertDatabaseMissing('aktivitas_surats', [
            'surat_id' => $surat->id,
            'tipe' => AktivitasSurat::TIPE_PENGAMBILAN_DIBATALKAN,
        ]);
    }

    public function test_surat_selesai_tidak_dapat_dibatalkan_pengambilannya(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'ditugaskan_pada' => now()->subDay(),
            'mulai_diproses_pada' => now()->subHours(2),
            'selesai_pada' => now()->subHour(),
        ]);

        $this->actingAs($pegawai)
            ->patch(route('surat-saya.cancel', $surat))
            ->assertForbidden();

        $this->assertSame(Surat::STATUS_SELESAI, $surat->fresh()->status);
    }

    public function test_pegawai_tidak_dapat_mengambil_surat_yang_sudah_diambil_pegawai_lain(): void
    {
        $pegawai1 = $this->pegawai(['name' => 'Pegawai Satu']);
        $pegawai2 = $this->pegawai(['name' => 'Pegawai Dua']);

        $surat = Surat::factory()->create([
            'nomor_surat' => 'SUDAH-DIAMBIL',
            'pegawai_id' => $pegawai1->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $response = $this->actingAs($pegawai2)
            ->post(route('surat-tersedia.ambil', $surat));

        $response->assertRedirect(route('surat-tersedia.index'));
        $response->assertSessionHasErrors('ambil');

        // Pastikan penangan surat tidak berubah.
        $surat->refresh();
        $this->assertSame($pegawai1->id, $surat->pegawai_id);
    }

    public function test_pegawai_tidak_dapat_mengambil_surat_yang_bukan_status_belum_ditangani(): void
    {
        $pegawai = $this->pegawai();

        $surat = Surat::factory()->create([
            'nomor_surat' => 'SEDANG-PROSES',
            'pegawai_id' => null,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $response = $this->actingAs($pegawai)
            ->post(route('surat-tersedia.ambil', $surat));

        $response->assertRedirect(route('surat-tersedia.index'));
        $response->assertSessionHasErrors('ambil');

        $surat->refresh();
        $this->assertNull($surat->pegawai_id);
    }
}
