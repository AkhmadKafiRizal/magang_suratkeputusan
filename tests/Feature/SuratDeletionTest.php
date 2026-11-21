<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SuratDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_delete_button_and_confirmation_data(): void
    {
        $user = User::factory()->create();
        $surat = Surat::factory()->create([
            'nomor_surat' => 'DELETE-UI/2026',
            'perihal' => 'Permohonan penghapusan data uji',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Lihat Detail')
            ->assertSee('Hapus')
            ->assertSee('Konfirmasi Hapus Surat')
            ->assertSee('DELETE-UI/2026')
            ->assertSee('Permohonan penghapusan data uji')
            ->assertSee('Ya, Hapus Surat')
            ->assertSee('method="POST"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertSee('name="_token"', false);

        $this->assertNotNull($surat->fresh());
    }

    public function test_authenticated_user_can_soft_delete_surat_and_audit_is_preserved(): void
    {
        $user = User::factory()->create(['name' => 'Pegawai Penghapus']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'SOFT-DELETE/2026',
            'perihal' => 'Perihal yang diaudit',
        ]);
        $suratLain = Surat::factory()->create(['nomor_surat' => 'TETAP-ADA/2026']);

        $this->actingAs($user)
            ->delete(route('surat.destroy', $surat))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', 'Surat berhasil dihapus.');

        $this->assertSoftDeleted('surats', ['id' => $surat->id]);
        $this->assertDatabaseHas('surats', [
            'id' => $suratLain->id,
            'deleted_at' => null,
        ]);

        $aktivitas = AktivitasSurat::query()
            ->where('surat_id', $surat->id)
            ->where('tipe', AktivitasSurat::TIPE_SURAT_DIHAPUS)
            ->firstOrFail();

        $this->assertSame($user->id, $aktivitas->user_id);
        $this->assertSame('SOFT-DELETE/2026', $aktivitas->metadata['nomor_surat']);
        $this->assertSame('Perihal yang diaudit', $aktivitas->metadata['perihal']);
        $this->assertSame($surat->id, $aktivitas->surat->id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('SOFT-DELETE/2026')
            ->assertSee('TETAP-ADA/2026')
            ->assertViewHas('ringkasan', fn (array $ringkasan): bool => $ringkasan['total'] === 1);

        $this->actingAs($user)
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk()
            ->assertSee('Surat Dihapus')
            ->assertSee('SOFT-DELETE/2026')
            ->assertDontSee('href="'.route('surat.show', $surat).'"', false);
    }

    public function test_guest_cannot_delete_surat(): void
    {
        $surat = Surat::factory()->create();

        $this->delete(route('surat.destroy', $surat))
            ->assertRedirect(route('login'));

        $this->assertNotSoftDeleted('surats', ['id' => $surat->id]);
    }

    public function test_soft_deleted_surat_number_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $surat = Surat::factory()->create(['nomor_surat' => 'NOMOR-TETAP-UNIK/2026']);
        $surat->delete();

        $kategoriId = KategoriSurat::query()
            ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
            ->value('id');

        $this->actingAs($user)
            ->post(route('surat.store'), [
                'nomor_surat' => 'nomor-tetap-unik/2026',
                'tanggal_masuk' => '2026-11-05',
                'pemohon_pengirim' => 'Pemohon Uji',
                'kategori_surat_id' => $kategoriId,
            ])
            ->assertSessionHasErrors('nomor_surat');

        $this->assertSame(1, Surat::withTrashed()->count());
    }

    public function test_destroy_route_requires_delete_method_and_web_auth_middleware(): void
    {
        $route = Route::getRoutes()->getByName('surat.destroy');

        $this->assertNotNull($route);
        $this->assertSame(['DELETE'], $route->methods());
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth', $route->gatherMiddleware());
    }
}
