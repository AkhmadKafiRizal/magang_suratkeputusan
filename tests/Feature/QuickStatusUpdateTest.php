<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_saya_renders_interactive_statuses_and_noninteractive_completed_badge(): void
    {
        $pegawai = User::factory()->create();

        Surat::factory()->create([
            'nomor_surat' => 'QUICK-BELUM',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'QUICK-PROSES',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'mulai_diproses_pada' => now()->subHour(),
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'QUICK-SELESAI',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($pegawai)
            ->get(route('surat-saya.index'))
            ->assertOk()
            ->assertSee('data-current-status="belum_ditangani"', false)
            ->assertSee('data-current-status="sedang_diproses"', false)
            ->assertSee('Konfirmasi Penyelesaian Surat')
            ->assertSee('Ya, Selesaikan')
            ->assertSee('Nomor Surat: QUICK-PROSES')
            ->assertSee('aria-label="Mulai proses surat QUICK-BELUM"', false)
            ->assertSee('aria-label="Selesaikan surat QUICK-PROSES"', false)
            ->assertSee('status-badge status-selesai', false)
            ->assertSee('data-status-updated-at', false)
            ->assertSee('Detail');

        $this->assertSame(2, substr_count($response->getContent(), 'data-quick-status-form'));
        $this->assertSame(Surat::STATUS_SELESAI, Surat::query()->where('nomor_surat', 'QUICK-SELESAI')->value('status'));
    }

    public function test_quick_update_starts_processing_and_returns_server_state(): void
    {
        $pegawai = User::factory()->create(['name' => 'Revina']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'QUICK-START',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($pegawai)
            ->patchJson(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Surat berhasil mulai diproses.')
            ->assertJsonPath('surat.status', Surat::STATUS_SEDANG_DIPROSES)
            ->assertJsonPath('surat.status_label', 'Sedang Diproses')
            ->assertJsonStructure(['surat' => ['updated_at', 'updated_at_label']]);

        $surat->refresh();
        $this->assertTrue($surat->mulai_diproses_pada->equalTo(now()));
        $this->assertNull($surat->selesai_pada);

        $aktivitas = AktivitasSurat::sole();
        $this->assertSame(AktivitasSurat::TIPE_MULAI_DIPROSES, $aktivitas->tipe);
        $this->assertSame($pegawai->id, $aktivitas->user_id);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $aktivitas->metadata['status_sebelumnya']);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $aktivitas->metadata['status_baru']);
    }

    public function test_quick_update_completes_surat_and_sets_completion_time(): void
    {
        $pegawai = User::factory()->create(['name' => 'Revina']);
        $mulai = now()->subHour()->startOfSecond();
        $surat = Surat::factory()->create([
            'nomor_surat' => 'QUICK-FINISH',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'mulai_diproses_pada' => $mulai,
        ]);
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($pegawai)
            ->patchJson(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SELESAI,
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Surat berhasil diselesaikan.')
            ->assertJsonPath('surat.status', Surat::STATUS_SELESAI)
            ->assertJsonPath('surat.status_label', 'Selesai');

        $surat->refresh();
        $this->assertTrue($surat->mulai_diproses_pada->equalTo($mulai));
        $this->assertTrue($surat->selesai_pada->equalTo(now()));

        $aktivitas = AktivitasSurat::sole();
        $this->assertSame(AktivitasSurat::TIPE_SELESAI, $aktivitas->tipe);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $aktivitas->metadata['status_sebelumnya']);
        $this->assertSame(Surat::STATUS_SELESAI, $aktivitas->metadata['status_baru']);
    }

    public function test_duplicate_or_stale_request_is_rejected_without_duplicate_activity(): void
    {
        $pegawai = User::factory()->create();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $this->actingAs($pegawai)->patchJson(route('surat-saya.update-status', $surat), [
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ])->assertOk();

        $this->actingAs($pegawai)->patchJson(route('surat-saya.update-status', $surat), [
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ])
            ->assertConflict()
            ->assertJsonPath('surat.status', Surat::STATUS_SEDANG_DIPROSES);

        $this->assertDatabaseCount('aktivitas_surats', 1);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->fresh()->status);
    }

    public function test_quick_update_rejects_invalid_transition_other_owner_and_guest(): void
    {
        $pemilik = User::factory()->create();
        $pegawaiLain = User::factory()->create();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pemilik->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $this->actingAs($pemilik)
            ->patchJson(route('surat-saya.update-status', $surat), ['status' => Surat::STATUS_SELESAI])
            ->assertConflict()
            ->assertJsonPath('surat.status', Surat::STATUS_BELUM_DITANGANI);

        $this->actingAs($pegawaiLain)
            ->patchJson(route('surat-saya.update-status', $surat), ['status' => Surat::STATUS_SEDANG_DIPROSES])
            ->assertForbidden();

        auth()->logout();
        $this->patchJson(route('surat-saya.update-status', $surat), [
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('aktivitas_surats', 0);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->fresh()->status);
    }
}
