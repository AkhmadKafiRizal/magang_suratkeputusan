<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use App\Services\AktivitasSuratService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestSiarkaFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleted_number_can_be_reused_and_active_duplicate_remains_blocked(): void
    {
        $user = User::factory()->create();
        $category = KategoriSurat::query()->firstOrFail();
        $old = Surat::factory()->create(['nomor_surat' => '2026.0061.075', 'kategori_surat_id' => $category->id]);
        app(AktivitasSuratService::class)->suratDicatat($old, $user);
        app(AktivitasSuratService::class)->suratDihapus($old, $user);
        $old->delete();

        $payload = $this->payload($category->id, '2026.0061.075');
        $this->actingAs($user)->post(route('surat.store'), $payload)->assertRedirect();
        $this->assertSame(2, Surat::withTrashed()->where('nomor_surat', '2026.0061.075')->count());
        $this->assertSame(1, Surat::where('nomor_surat', '2026.0061.075')->count());

        $this->actingAs($user)->post(route('surat.store'), $payload)
            ->assertSessionHasErrors('nomor_surat');
        $this->assertSame(1, Surat::where('nomor_surat', '2026.0061.075')->count());
    }

    public function test_number_history_shows_real_creator_deleter_and_reuse_cycles(): void
    {
        $creator = User::factory()->create(['name' => 'Pegawai Pembuat']);
        $deleter = User::factory()->create(['name' => 'Pegawai Penghapus']);
        $category = KategoriSurat::query()->firstOrFail();
        $old = Surat::factory()->create(['nomor_surat' => '2026.0062.001', 'kategori_surat_id' => $category->id]);
        app(AktivitasSuratService::class)->suratDicatat($old, $creator);
        app(AktivitasSuratService::class)->suratDihapus($old, $deleter);
        $old->delete();
        $current = Surat::factory()->create(['nomor_surat' => '2026.0062.001', 'kategori_surat_id' => $category->id]);
        app(AktivitasSuratService::class)->suratDicatat($current, $creator);

        $response = $this->actingAs($creator)->get(route('surat.show', $current));

        $response->assertOk()
            ->assertSee('Jejak Pekerjaan')
            ->assertSee('Riwayat Surat')
            ->assertSee('detail-history-time', false)
            ->assertSee($current->created_at->format('H:i').' WIB')
            ->assertDontSee('Audit Nomor Surat')
            ->assertDontSee('Riwayat Penggunaan Nomor Surat')
            ->assertDontSee('Record #')
            ->assertSee('Pegawai Pembuat')
            ->assertSee('Pegawai Penghapus')
            ->assertSee("{$creator->name} mencatat surat {$current->nomor_surat} ke sistem.")
            ->assertSee("{$deleter->name} menghapus surat {$old->nomor_surat} dari daftar aktif.")
            ->assertDontSee('<small>Pegawai Pembuat</small>', false)
            ->assertDontSee('<small>Pegawai Penghapus</small>', false);

        $this->assertSame(2, substr_count($response->getContent(), 'mencatat surat 2026.0062.001 ke sistem.'));
        $this->assertSame(1, substr_count($response->getContent(), 'menghapus surat 2026.0062.001 dari daftar aktif.'));
    }

    public function test_bulk_status_reports_partial_result_and_records_only_valid_transition(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $valid = Surat::factory()->create(['pegawai_id' => $owner->id, 'status' => Surat::STATUS_BELUM_DITANGANI]);
        $foreign = Surat::factory()->create(['pegawai_id' => $other->id, 'status' => Surat::STATUS_BELUM_DITANGANI]);

        $response = $this->actingAs($owner)->patch(route('surat-saya.bulk-update-status'), [
            'surat_ids' => [$valid->id, $foreign->id],
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $response->assertRedirect()->assertSessionHas('bulk_result', fn ($result) => $result['success_count'] === 1 && $result['failure_count'] === 1);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $valid->fresh()->status);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $foreign->fresh()->status);
        $this->assertSame(1, $valid->aktivitas()->where('tipe', AktivitasSurat::TIPE_MULAI_DIPROSES)->count());
    }

    public function test_bulk_claim_reports_conflict_without_stealing_letter(): void
    {
        $employee = User::factory()->create();
        $other = User::factory()->create();
        $available = Surat::factory()->create();
        $taken = Surat::factory()->create(['pegawai_id' => $other->id]);

        $this->actingAs($employee)->post(route('surat-tersedia.bulk-ambil'), [
            'surat_ids' => [$available->id, $taken->id],
        ])->assertSessionHas('bulk_result', fn ($result) => $result['success_count'] === 1 && $result['failure_count'] === 1);

        $this->assertSame($employee->id, $available->fresh()->pegawai_id);
        $this->assertSame($other->id, $taken->fresh()->pegawai_id);
        $this->assertSame(1, $available->aktivitas()->where('tipe', AktivitasSurat::TIPE_SURAT_DIAMBIL)->count());
    }

    public function test_quick_description_is_authorized_validated_and_audited(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $surat = Surat::factory()->create(['pegawai_id' => $owner->id, 'keterangan' => 'Lama']);

        $this->actingAs($other)->patch(route('surat-saya.update-description', $surat), ['keterangan' => 'Tidak sah'])->assertForbidden();
        $this->actingAs($owner)->patch(route('surat-saya.update-description', $surat), ['keterangan' => "Baris satu\nBaris dua"])->assertRedirect();
        $this->assertSame("Baris satu\nBaris dua", $surat->fresh()->keterangan);
        $this->assertDatabaseHas('aktivitas_surats', ['surat_id' => $surat->id, 'tipe' => AktivitasSurat::TIPE_SURAT_DIEDIT]);

        $this->actingAs($owner)->patch(route('surat-saya.update-description', $surat), ['keterangan' => ''])->assertRedirect();
        $surat->refresh();
        $this->assertNull($surat->keterangan);
        $this->assertNotSame('-', $surat->keterangan);
        $this->assertSame(2, $surat->aktivitas()->where('tipe', AktivitasSurat::TIPE_SURAT_DIEDIT)->count());

        $this->actingAs($owner)->patch(route('surat-saya.update-description', $surat), ['keterangan' => ''])->assertRedirect();
        $this->assertSame(2, $surat->aktivitas()->where('tipe', AktivitasSurat::TIPE_SURAT_DIEDIT)->count());
    }

    public function test_description_suggestions_are_unique_limited_and_exclude_deleted_records(): void
    {
        $user = User::factory()->create();
        Surat::factory()->count(2)->create(['keterangan' => 'Menunggu kelengkapan dokumen.']);
        $deleted = Surat::factory()->create(['keterangan' => 'Rahasia yang dihapus']);
        $deleted->delete();

        $response = $this->actingAs($user)->getJson(route('surat.description-suggestions', ['q' => 'Menunggu']));
        $response->assertOk()->assertJsonCount(1, 'suggestions')->assertJsonPath('suggestions.0', 'Menunggu kelengkapan dokumen.');
        $this->actingAs($user)->getJson(route('surat.description-suggestions', ['q' => 'Rahasia']))
            ->assertJsonCount(0, 'suggestions');

        foreach (range(1, 12) as $index) {
            Surat::factory()->create(['keterangan' => "Saran aktif {$index}"]);
        }

        $this->actingAs($user)->getJson(route('surat.description-suggestions', ['q' => 'Saran aktif']))
            ->assertJsonCount(10, 'suggestions');
    }

    public function test_bulk_and_description_ui_hooks_are_rendered(): void
    {
        $user = User::factory()->create();
        Surat::factory()->create(['pegawai_id' => $user->id]);
        Surat::factory()->create(['keterangan' => 'Contoh saran']);

        $this->actingAs($user)->get(route('surat-saya.index'))
            ->assertSee('Update Status Terpilih')
            ->assertSee('<span>Edit</span>', false)
            ->assertSee('data-description-edit', false)
            ->assertSee('data-bulk-select-all', false);
        $this->actingAs($user)->get(route('surat-tersedia.index'))
            ->assertSee('Ambil Surat Terpilih')
            ->assertSee('data-bulk-select-all', false);
        $this->actingAs($user)->withSession(['bulk_result' => [
            'title' => 'Pengujian', 'variant' => 'success', 'success_count' => 1,
            'failure_count' => 0, 'success_label' => 'berhasil', 'failure_label' => 'gagal', 'reasons' => [],
        ]])->get(route('surat-saya.index'))->assertSee('data-timeout="20000"', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $javascript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('scrollbar-color: #b7c5d6 #f5f8fc', $css);
        $this->assertStringNotContainsString('background: #20242b', $css);
        $this->assertStringContainsString('max-height: 216px', $css);
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $css);
        $this->assertStringContainsString('.bulk-result-modal.is-open', $css);
        $this->assertStringContainsString("input.addEventListener('click', loadImmediately)", $javascript);
        $this->assertStringContainsString('descriptionSuggestionCache', $javascript);
        $this->assertStringContainsString('new AbortController()', $javascript);
        $this->assertStringContainsString("bulkResultModal.classList.add('is-open')", $javascript);
        $this->assertStringContainsString("bulkResultModal.classList.add('is-closing')", $javascript);
        $this->assertStringContainsString('bulk-result-stats', file_get_contents(resource_path('views/components/bulk-result-modal.blade.php')));
        $this->assertStringNotContainsString('countdown', strtolower($javascript));
    }

    /** @return array<string, mixed> */
    private function payload(int $categoryId, string $number): array
    {
        return [
            'nomor_surat' => $number,
            'tanggal_masuk' => now()->toDateString(),
            'pemohon_pengirim' => 'PT Uji Sistem',
            'kategori_surat_id' => $categoryId,
            'keterangan' => 'Pengujian',
            'submit_action' => 'save',
        ];
    }
}
