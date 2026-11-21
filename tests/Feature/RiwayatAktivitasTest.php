<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RiwayatAktivitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_activity_history(): void
    {
        $this->get(route('riwayat-aktivitas.index'))
            ->assertRedirect(route('login'));
    }

    public function test_pegawai_can_open_activity_history(): void
    {
        $this->actingAs($this->pegawai())
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk();
    }

    public function test_pengguna_can_open_activity_history(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk()
            ->assertSee('Riwayat Aktivitas')
            ->assertSee('Jejak Pekerjaan')
            ->assertDontSee('<span>Riwayat / Aktivitas</span>', false);
    }

    public function test_opening_read_only_pages_does_not_create_activity(): void
    {
        $pengguna = $this->pengguna();
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->actingAs($pengguna)->get(route('dashboard'))->assertOk();
        $this->actingAs($pengguna)->get(route('surat.show', $surat))->assertOk();
        $this->actingAs($pengguna)->get(route('monitoring-pegawai.index'))->assertOk();
        $this->actingAs($pengguna)->get(route('riwayat-aktivitas.index'))->assertOk();

        $this->assertDatabaseCount('aktivitas_surats', 0);
    }

    public function test_new_surat_records_surat_dicatat_with_the_correct_actor(): void
    {
        $pengguna = $this->pengguna(['name' => 'Kafi']);

        $this->actingAs($pengguna)
            ->post(route('surat.store'), $this->validPayload())
            ->assertRedirect();

        $surat = Surat::firstOrFail();
        $aktivitas = AktivitasSurat::firstOrFail();

        $this->assertSame(AktivitasSurat::TIPE_SURAT_DICATAT, $aktivitas->tipe);
        $this->assertSame($pengguna->id, $aktivitas->user_id);
        $this->assertSame($surat->id, $aktivitas->surat_id);
        $this->assertSame("Kafi mencatat surat {$surat->nomor_surat} ke sistem.", $aktivitas->deskripsi);
    }

    public function test_real_administrative_edit_records_changed_fields(): void
    {
        $pengguna = $this->pengguna(['name' => 'Kafi']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'LAMA/2026',
            'pemohon_pengirim' => 'Pemohon lama',
            'kategori_surat_id' => $this->kategoriUtamaId(),
        ]);

        $this->actingAs($pengguna)
            ->put(route('surat.update', $surat), $this->payloadFor($surat, [
                'nomor_surat' => 'BARU/2026',
                'pemohon_pengirim' => 'Pemohon baru',
            ]))
            ->assertRedirect();

        $aktivitas = AktivitasSurat::firstOrFail();
        $this->assertSame(AktivitasSurat::TIPE_SURAT_DIEDIT, $aktivitas->tipe);
        $this->assertSame(['nomor_surat', 'pemohon_pengirim'], $aktivitas->metadata['fields']);
        $this->assertSame('Kafi memperbarui data administratif surat BARU/2026.', $aktivitas->deskripsi);
    }

    public function test_saving_without_changes_does_not_record_surat_diedit(): void
    {
        $surat = Surat::factory()->create(['kategori_surat_id' => $this->kategoriUtamaId()]);

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->payloadFor($surat))
            ->assertRedirect();

        $this->assertDatabaseCount('aktivitas_surats', 0);
    }

    public function test_starting_process_records_activity_with_employee_actor(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->actingAs($pegawai)
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertRedirect();

        $aktivitas = AktivitasSurat::firstOrFail();
        $this->assertSame(AktivitasSurat::TIPE_MULAI_DIPROSES, $aktivitas->tipe);
        $this->assertSame($pegawai->id, $aktivitas->user_id);
        $this->assertSame("Revina mulai memproses surat {$surat->nomor_surat}.", $aktivitas->deskripsi);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $aktivitas->metadata['status_sebelumnya']);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $aktivitas->metadata['status_baru']);
    }

    public function test_completing_surat_records_selesai_activity(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'mulai_diproses_pada' => now()->subHour(),
        ]);

        $this->actingAs($pegawai)
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SELESAI,
            ])
            ->assertRedirect();

        $aktivitas = AktivitasSurat::firstOrFail();
        $this->assertSame(AktivitasSurat::TIPE_SELESAI, $aktivitas->tipe);
        $this->assertSame($pegawai->id, $aktivitas->user_id);
        $this->assertSame("Revina menyelesaikan surat {$surat->nomor_surat}.", $aktivitas->deskripsi);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $aktivitas->metadata['status_sebelumnya']);
        $this->assertSame(Surat::STATUS_SELESAI, $aktivitas->metadata['status_baru']);
    }

    public function test_activity_relationships_are_available(): void
    {
        $actor = $this->pengguna();
        $surat = Surat::factory()->create();
        $aktivitas = $this->aktivitas($surat, $actor, AktivitasSurat::TIPE_SURAT_DICATAT);

        $this->assertTrue($aktivitas->surat->is($surat));
        $this->assertTrue($aktivitas->actor->is($actor));
        $this->assertTrue($surat->aktivitas()->firstOrFail()->is($aktivitas));
        $this->assertTrue($actor->aktivitasSurat()->firstOrFail()->is($aktivitas));
    }

    public function test_activities_are_ordered_from_newest_to_oldest(): void
    {
        $surat = Surat::factory()->create();
        $lama = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DICATAT, '2026-09-01 08:00:00');
        $baru = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DIEDIT, '2026-09-03 08:00:00');

        $this->actingAs($this->pengguna())
            ->get(route('riwayat-aktivitas.index'))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [
                $baru->id,
                $lama->id,
            ]);
    }

    public function test_activity_category_filter_groups_letter_pickup_types(): void
    {
        $surat = Surat::factory()->create();
        $pengambilan = [
            $this->aktivitas($surat, null, AktivitasSurat::TIPE_PENANGAN_MULAI),
            $this->aktivitas($surat, null, AktivitasSurat::TIPE_PENANGAN_DIGANTI),
            $this->aktivitas($surat, null, AktivitasSurat::TIPE_PENGAMBILAN_DIBATALKAN),
        ];
        $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DIEDIT);

        $this->actingAs($this->pengguna())
            ->get(route('riwayat-aktivitas.index', ['jenis' => 'pengambilan']))
            ->assertOk()
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->sort()->values()->all()
                === collect($pengambilan)->pluck('id')->sort()->values()->all());
    }

    public function test_actor_filter_and_missing_actor_label_work(): void
    {
        $pengguna = $this->pengguna();
        $actor = $this->pegawai(['name' => 'Revina']);
        $actorLain = $this->pegawai(['name' => 'Pegawai Lain']);
        $surat = Surat::factory()->create();
        $milikRevina = $this->aktivitas($surat, $actor, AktivitasSurat::TIPE_MULAI_DIPROSES);
        $this->aktivitas($surat, $actorLain, AktivitasSurat::TIPE_SURAT_DIEDIT);

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', ['pengguna' => $actor->id]))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$milikRevina->id]);

        $actor->delete();

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk()
            ->assertSee('Pengguna tidak tersedia');
    }

    public function test_start_end_and_range_date_filters_work_independently(): void
    {
        $surat = Surat::factory()->create();
        $awal = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DICATAT, '2026-09-01 08:00:00');
        $tengah = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DIEDIT, '2026-09-03 08:00:00');
        $akhir = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SELESAI, '2026-09-05 08:00:00');
        $pengguna = $this->pengguna();

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', ['dari' => '2026-09-03']))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$akhir->id, $tengah->id]);

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', ['sampai' => '2026-09-03']))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$tengah->id, $awal->id]);

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', [
                'dari' => '2026-09-02',
                'sampai' => '2026-09-04',
            ]))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$tengah->id]);
    }

    public function test_invalid_date_range_is_rejected_safely(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('riwayat-aktivitas.index', [
                'dari' => '2026-09-05',
                'sampai' => '2026-09-01',
            ]))
            ->assertSessionHasErrors([
                'sampai' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.',
            ]);
    }

    public function test_search_by_letter_number_and_applicant_works(): void
    {
        $suratNomor = Surat::factory()->create([
            'nomor_surat' => 'KHUSUS/2026',
            'pemohon_pengirim' => 'Pemohon biasa',
        ]);
        $suratPemohon = Surat::factory()->create([
            'nomor_surat' => 'LAIN/2026',
            'pemohon_pengirim' => 'Pemohon pajak hotel',
        ]);
        $aktivitasNomor = $this->aktivitas($suratNomor, null, AktivitasSurat::TIPE_SURAT_DICATAT);
        $aktivitasPemohon = $this->aktivitas($suratPemohon, null, AktivitasSurat::TIPE_SURAT_DICATAT);
        $pengguna = $this->pengguna();

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', ['q' => 'KHUSUS']))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$aktivitasNomor->id]);

        $this->actingAs($pengguna)
            ->get(route('riwayat-aktivitas.index', ['q' => 'pajak hotel']))
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$aktivitasPemohon->id]);
    }

    public function test_letter_query_parameter_filters_history_and_shows_chip(): void
    {
        $surat = Surat::factory()->create(['nomor_surat' => 'FILTER/2026']);
        $suratLain = Surat::factory()->create();
        $milikSurat = $this->aktivitas($surat, null, AktivitasSurat::TIPE_SURAT_DICATAT);
        $this->aktivitas($suratLain, null, AktivitasSurat::TIPE_SURAT_DICATAT);

        $this->actingAs($this->pengguna())
            ->get(route('riwayat-aktivitas.index', ['surat' => $surat->id]))
            ->assertOk()
            ->assertSee('Menampilkan riwayat surat')
            ->assertSee('FILTER/2026')
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->pluck('id')->all() === [$milikSurat->id]);
    }

    public function test_pagination_preserves_all_query_parameters(): void
    {
        $actor = $this->pengguna();
        $surat = Surat::factory()->create([
            'nomor_surat' => 'PAGINASI/2026',
        ]);

        foreach (range(1, 16) as $urutan) {
            $this->aktivitas(
                $surat,
                $actor,
                AktivitasSurat::TIPE_SURAT_DIEDIT,
                "2026-09-03 08:{$urutan}:00"
            );
        }

        $response = $this->actingAs($actor)
            ->get(route('riwayat-aktivitas.index', [
                'q' => 'PAGINASI',
                'jenis' => 'perubahan_data',
                'pengguna' => $actor->id,
                'dari' => '2026-09-01',
                'sampai' => '2026-09-05',
                'surat' => $surat->id,
            ]))
            ->assertOk()
            ->assertViewHas('aktivitas', fn ($aktivitas): bool => $aktivitas->count() === 15
                && $aktivitas->total() === 16);

        parse_str((string) parse_url($response->viewData('aktivitas')->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('PAGINASI', $query['q']);
        $this->assertSame('perubahan_data', $query['jenis']);
        $this->assertSame((string) $actor->id, (string) $query['pengguna']);
        $this->assertSame((string) $surat->id, (string) $query['surat']);
    }

    public function test_dashboard_does_not_load_activity_history(): void
    {
        $suratTimestampSaja = Surat::factory()->create([
            'nomor_surat' => 'TANPA-LOG',
            'ditugaskan_pada' => now()->subMinutes(30),
            'mulai_diproses_pada' => now()->subMinutes(20),
            'selesai_pada' => now()->subMinutes(10),
        ]);
        $suratResmi = Surat::factory()->create(['nomor_surat' => 'DENGAN-LOG']);
        $resmi = $this->aktivitas($suratResmi, null, AktivitasSurat::TIPE_SURAT_DICATAT);

        $response = $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('DENGAN-LOG')
            ->assertDontSee('Aktivitas Terbaru')
            ->assertDontSee($resmi->deskripsi_tampilan);

        $this->assertArrayNotHasKey('aktivitasTerbaru', $response->original->getData());
    }

    public function test_surat_detail_shows_only_five_latest_activities_and_history_link(): void
    {
        $surat = Surat::factory()->create();
        $aktivitas = collect();

        foreach (range(1, 6) as $urutan) {
            $aktivitas->push($this->aktivitas(
                $surat,
                null,
                AktivitasSurat::TIPE_SURAT_DIEDIT,
                "2026-09-03 08:0{$urutan}:00"
            ));
        }

        $this->actingAs($this->pengguna())
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Riwayat Surat')
            ->assertSee('Lihat Semua Riwayat')
            ->assertSee(route('riwayat-aktivitas.index', ['surat' => $surat->id]), false)
            ->assertViewHas('aktivitasTerbaru', fn ($riwayat): bool => $riwayat->count() === 5
                && $riwayat->first()->is($aktivitas->last())
                && ! $riwayat->contains($aktivitas->first()));
    }

    private function pengguna(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
        ], $attributes));
    }

    private function pegawai(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
        ], $attributes));
    }

    private function kategoriUtamaId(): int
    {
        return (int) KategoriSurat::query()
            ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
            ->value('id');
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '004/BPK/IX/2026',
            'tanggal_masuk' => '2026-09-03',
            'pemohon_pengirim' => 'PT XYZ',
            'kategori_surat_id' => KategoriSurat::query()
                ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
                ->value('id'),
            'keterangan' => 'Catatan pengujian.',
        ], $overrides);
    }

    /** @param array<string, mixed> $overrides */
    private function payloadFor(Surat $surat, array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_masuk' => $surat->tanggal_masuk->format('Y-m-d'),
            'pemohon_pengirim' => $surat->pemohon_pengirim,
            'kategori_surat_id' => $surat->kategori_surat_id
                ?? KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)->value('id'),
            'keterangan' => $surat->keterangan,
        ], $overrides);
    }

    private function aktivitas(
        Surat $surat,
        ?User $actor,
        string $tipe,
        string $waktu = '2026-09-03 08:00:00'
    ): AktivitasSurat {
        $aktivitas = new AktivitasSurat([
            'user_id' => $actor?->id,
            'tipe' => $tipe,
            'deskripsi' => "Aktivitas {$tipe} untuk surat {$surat->nomor_surat}.",
        ]);
        $aktivitas->created_at = Carbon::parse($waktu);
        $aktivitas->updated_at = Carbon::parse($waktu);
        $surat->aktivitas()->save($aktivitas);

        return $aktivitas;
    }
}
