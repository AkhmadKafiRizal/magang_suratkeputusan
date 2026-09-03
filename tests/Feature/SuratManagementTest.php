<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_both_surat_areas(): void
    {
        $this->get(route('kepala-bidang.surat.index'))->assertRedirect(route('login'));
        $this->get(route('pegawai.surat-saya.index'))->assertRedirect(route('login'));
    }

    public function test_pegawai_cannot_access_kepala_bidang_surat_management(): void
    {
        $pegawai = $this->pegawai();

        $this->actingAs($pegawai)->get(route('kepala-bidang.surat.index'))->assertForbidden();
        $this->actingAs($pegawai)->get(route('kepala-bidang.surat.create'))->assertForbidden();
    }

    public function test_kepala_bidang_can_open_data_surat(): void
    {
        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.index'))
            ->assertOk()
            ->assertSee('Daftar Surat')
            ->assertSee('Tambah Surat');
    }

    public function test_kepala_bidang_can_create_a_surat_and_status_is_always_belum_ditangani(): void
    {
        $response = $this->actingAs($this->kepalaBidang())
            ->post(route('kepala-bidang.surat.store'), $this->validPayload());

        $surat = Surat::firstOrFail();
        $response->assertRedirect(route('kepala-bidang.surat.show', $surat));
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertNull($surat->pegawai_id);
        $this->assertNull($surat->ditugaskan_pada);
        $this->assertNull($surat->mulai_diproses_pada);
        $this->assertNull($surat->selesai_pada);
    }

    public function test_create_form_has_no_status_input_and_shows_initial_status_information(): void
    {
        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.create'))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertSee('Status awal surat otomatis Belum Ditangani.');
    }

    public function test_kepala_bidang_may_assign_a_pegawai_during_creation(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($this->kepalaBidang())
            ->post(route('kepala-bidang.surat.store'), $this->validPayload([
                'pegawai_id' => $pegawai->id,
            ]))
            ->assertRedirect();

        $surat = Surat::firstOrFail();
        $this->assertSame($pegawai->id, $surat->pegawai_id);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertTrue($surat->ditugaskan_pada->equalTo(now()));
        $this->assertNull($surat->mulai_diproses_pada);
    }

    public function test_unassigned_surat_detail_shows_a_clear_assignment_action(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.show', $surat))
            ->assertOk()
            ->assertSee('Belum Ditugaskan')
            ->assertSee('Tugaskan Pegawai')
            ->assertSee(route('kepala-bidang.surat.edit', $surat).'#pegawai_id');
    }

    public function test_catatan_administratif_is_saved_to_keterangan(): void
    {
        $this->actingAs($this->kepalaBidang())
            ->post(route('kepala-bidang.surat.store'), $this->validPayload([
                'keterangan' => 'Berkas KTP perlu diperiksa kembali.',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('surats', [
            'keterangan' => 'Berkas KTP perlu diperiksa kembali.',
        ]);
    }

    public function test_detail_without_catatan_uses_a_human_readable_empty_label(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'keterangan' => null,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.show', $surat))
            ->assertOk()
            ->assertSee('Catatan Administratif')
            ->assertSee('Tidak ada catatan administratif');

        $this->actingAs($pegawai)
            ->get(route('pegawai.surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('Tidak ada catatan administratif');
    }

    public function test_kepala_bidang_cannot_send_sedang_diproses_status_manually(): void
    {
        $this->actingAs($this->kepalaBidang())
            ->post(route('kepala-bidang.surat.store'), $this->validPayload([
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_kepala_bidang_cannot_send_selesai_status_manually(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload([
                'status' => Surat::STATUS_SELESAI,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->fresh()->status);
    }

    public function test_kepala_bidang_can_edit_administrative_data_without_changing_status(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload([
                'nomor_surat' => '456/EDIT/2026',
                'perihal' => 'Perihal yang diperbarui',
                'pegawai_id' => $pegawai->id,
            ]))
            ->assertRedirect(route('kepala-bidang.surat.show', $surat));

        $surat->refresh();
        $this->assertSame('456/EDIT/2026', $surat->nomor_surat);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->status);
    }

    public function test_assigning_a_pegawai_fills_assignment_time_without_changing_status(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create();
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload([
                'pegawai_id' => $pegawai->id,
            ]));

        $surat->refresh();
        $this->assertSame($pegawai->id, $surat->pegawai_id);
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertTrue($surat->ditugaskan_pada->equalTo(now()));
        $this->assertNull($surat->mulai_diproses_pada);
        $this->assertNull($surat->selesai_pada);
    }

    public function test_reassigning_a_pegawai_updates_assignment_time_without_changing_status(): void
    {
        $pegawaiPertama = $this->pegawai();
        $pegawaiKedua = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawaiPertama->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'ditugaskan_pada' => now()->subDay(),
        ]);
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload([
                'pegawai_id' => $pegawaiKedua->id,
            ]));

        $surat->refresh();
        $this->assertSame($pegawaiKedua->id, $surat->pegawai_id);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->status);
        $this->assertTrue($surat->ditugaskan_pada->equalTo(now()));
    }

    public function test_assignment_can_be_removed_only_while_status_is_belum_ditangani(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'ditugaskan_pada' => now()->subHour(),
        ]);

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload())
            ->assertRedirect();

        $surat->refresh();
        $this->assertNull($surat->pegawai_id);
        $this->assertNull($surat->ditugaskan_pada);
    }

    public function test_assignment_cannot_be_removed_from_a_processed_or_completed_surat(): void
    {
        $kabid = $this->kepalaBidang();

        foreach ([Surat::STATUS_SEDANG_DIPROSES, Surat::STATUS_SELESAI] as $status) {
            $pegawai = $this->pegawai();
            $surat = Surat::factory()->create([
                'pegawai_id' => $pegawai->id,
                'status' => $status,
                'ditugaskan_pada' => now()->subHour(),
            ]);

            $this->actingAs($kabid)
                ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload())
                ->assertSessionHasErrors([
                    'pegawai_id' => 'Penugasan tidak dapat dihapus karena surat sedang diproses atau sudah selesai.',
                ]);

            $this->assertSame($pegawai->id, $surat->fresh()->pegawai_id);
        }
    }

    public function test_kepala_bidang_cannot_assign_a_kepala_bidang_as_pegawai(): void
    {
        $kabid = $this->kepalaBidang();

        $this->actingAs($kabid)
            ->post(route('kepala-bidang.surat.store'), $this->validPayload([
                'pegawai_id' => $kabid->id,
            ]))
            ->assertSessionHasErrors('pegawai_id');
    }

    public function test_pegawai_sees_only_their_own_letters(): void
    {
        $revina = $this->pegawai(['name' => 'Revina']);
        $milikRevina = Surat::factory()->create([
            'nomor_surat' => 'MILIK-REVINA',
            'pegawai_id' => $revina->id,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'MILIK-ORANG-LAIN',
            'pegawai_id' => $this->pegawai()->id,
        ]);

        $this->actingAs($revina)
            ->get(route('pegawai.surat-saya.index'))
            ->assertOk()
            ->assertSee($milikRevina->nomor_surat)
            ->assertDontSee('MILIK-ORANG-LAIN')
            ->assertSee('Surat Saya');

        $this->actingAs($revina)
            ->get(route('pegawai.surat-saya.show', $milikRevina))
            ->assertOk()
            ->assertSee('Mulai Proses')
            ->assertDontSee('Edit Surat');
    }

    public function test_pegawai_cannot_view_another_employees_surat_by_url(): void
    {
        $revina = $this->pegawai();
        $suratPegawaiLain = Surat::factory()->create([
            'pegawai_id' => $this->pegawai()->id,
        ]);

        $this->actingAs($revina)
            ->get(route('pegawai.surat-saya.show', $suratPegawaiLain))
            ->assertForbidden();

        $this->actingAs($revina)
            ->patch(route('pegawai.surat-saya.update-status', $suratPegawaiLain), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertForbidden();

        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $suratPegawaiLain->fresh()->status);
    }

    public function test_pegawai_can_advance_belum_ditangani_to_sedang_diproses_only(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'nomor_surat' => 'ADMIN-TETAP',
            'pegawai_id' => $pegawai->id,
        ]);
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
                'nomor_surat' => 'TIDAK-BOLEH-BERUBAH',
            ])
            ->assertRedirect(route('pegawai.surat-saya.show', $surat));

        $surat->refresh();
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->status);
        $this->assertSame('ADMIN-TETAP', $surat->nomor_surat);
        $this->assertTrue($surat->mulai_diproses_pada->equalTo(now()));
        $this->assertNull($surat->selesai_pada);
    }

    public function test_pegawai_can_advance_sedang_diproses_to_selesai_and_completion_time_is_filled(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'mulai_diproses_pada' => now()->subHour(),
        ]);
        $waktuMulai = $surat->mulai_diproses_pada->copy();
        $this->travelTo(now()->startOfSecond());

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SELESAI,
            ]);

        $surat->refresh();
        $this->assertSame(Surat::STATUS_SELESAI, $surat->status);
        $this->assertTrue($surat->mulai_diproses_pada->equalTo($waktuMulai));
        $this->assertTrue($surat->selesai_pada->equalTo(now()));
    }

    public function test_historical_completed_surat_without_start_time_can_still_be_viewed(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'mulai_diproses_pada' => null,
            'selesai_pada' => now()->subHour(),
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.show', $surat))
            ->assertOk()
            ->assertSee('Mulai Diproses')
            ->assertSee('Belum Tercatat');

        $this->actingAs($pegawai)
            ->get(route('pegawai.surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('Belum Tercatat');
    }

    public function test_kepala_bidang_cannot_manipulate_process_timestamps(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->validPayload([
                'mulai_diproses_pada' => '2026-09-03 18:00:00',
            ]))
            ->assertSessionHasErrors('mulai_diproses_pada');

        $this->assertNull($surat->fresh()->mulai_diproses_pada);
    }

    public function test_pegawai_cannot_manipulate_process_timestamps(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
                'mulai_diproses_pada' => '2026-01-01 00:00:00',
                'selesai_pada' => '2026-01-01 00:00:00',
            ])
            ->assertSessionHasErrors(['mulai_diproses_pada', 'selesai_pada']);

        $surat->refresh();
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertNull($surat->mulai_diproses_pada);
        $this->assertNull($surat->selesai_pada);
    }

    public function test_pegawai_cannot_skip_from_belum_ditangani_directly_to_selesai(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SELESAI,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->fresh()->status);
    }

    public function test_pegawai_cannot_move_selesai_back_to_sedang_diproses(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => now()->subHour(),
        ]);

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Surat::STATUS_SELESAI, $surat->fresh()->status);
    }

    public function test_kepala_bidang_cannot_use_pegawai_status_endpoint(): void
    {
        $surat = Surat::factory()->create(['pegawai_id' => $this->pegawai()->id]);

        $this->actingAs($this->kepalaBidang())
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertForbidden();
    }

    public function test_dashboard_kepala_bidang_uses_actual_status_counts(): void
    {
        Surat::factory()->count(2)->create(['status' => Surat::STATUS_BELUM_DITANGANI]);
        Surat::factory()->count(3)->create(['status' => Surat::STATUS_SEDANG_DIPROSES]);
        Surat::factory()->create(['status' => Surat::STATUS_SELESAI]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertViewHas('ringkasan', [
                'total' => 6,
                'diproses' => 3,
                'selesai' => 1,
                'belum_ditangani' => 2,
            ]);
    }

    public function test_dashboard_activity_is_derived_from_surat_timestamps_and_sorted_newest_first(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create([
            'nomor_surat' => '003/BPK/IX/2026',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'ditugaskan_pada' => now()->subMinutes(30),
            'mulai_diproses_pada' => now()->subMinutes(20),
            'selesai_pada' => now()->subMinutes(10),
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertSeeInOrder([
                "Revina menyelesaikan surat {$surat->nomor_surat}",
                "Revina mulai memproses surat {$surat->nomor_surat}",
                "Surat {$surat->nomor_surat} ditugaskan kepada Revina",
            ])
            ->assertDontSee('Belum ada aktivitas');
    }

    public function test_missing_timestamps_do_not_create_fake_dashboard_activities(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        Surat::factory()->create([
            'nomor_surat' => 'TANPA-AKTIVITAS',
            'pegawai_id' => $pegawai->id,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertViewHas('aktivitasTerbaru', fn ($aktivitas): bool => $aktivitas->isEmpty())
            ->assertSee('Belum ada aktivitas')
            ->assertDontSee('Revina mulai memproses surat TANPA-AKTIVITAS');
    }

    public function test_dashboard_activity_is_limited_to_six_events(): void
    {
        $pegawai = $this->pegawai();
        Surat::factory()->count(3)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'ditugaskan_pada' => now()->subMinutes(30),
            'mulai_diproses_pada' => now()->subMinutes(20),
            'selesai_pada' => now()->subMinutes(10),
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertViewHas('aktivitasTerbaru', fn ($aktivitas): bool => $aktivitas->count() === 6);
    }

    public function test_dashboard_pegawai_counts_and_lists_only_their_own_letters(): void
    {
        $revina = $this->pegawai(['name' => 'Revina']);
        Surat::factory()->count(2)->create([
            'pegawai_id' => $revina->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'SELESAI-REVINA',
            'pegawai_id' => $revina->id,
            'status' => Surat::STATUS_SELESAI,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'BUKAN-MILIK-REVINA',
            'pegawai_id' => $this->pegawai()->id,
        ]);

        $this->actingAs($revina)
            ->get(route('dashboard.pegawai'))
            ->assertOk()
            ->assertViewHas('ringkasan', [
                'ditugaskan' => 3,
                'diproses' => 2,
                'selesai' => 1,
            ])
            ->assertSee('SELESAI-REVINA')
            ->assertDontSee('BUKAN-MILIK-REVINA')
            ->assertSee('Surat Saya');
    }

    public function test_monitoring_pegawai_still_uses_actual_counts(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        Surat::factory()->count(2)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->count(3)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
        ]);
        Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertViewHas('pegawai', function ($pegawaiCollection): bool {
                $revina = $pegawaiCollection->firstWhere('name', 'Revina');

                return $revina !== null
                    && $revina->sedang_diproses_count === 2
                    && $revina->selesai_count === 3
                    && $revina->total_ditangani_count === 6;
            });
    }

    public function test_monitoring_detail_link_opens_employee_monitoring_detail(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertSee(route('kepala-bidang.monitoring-pegawai.show', $pegawai), false);
    }

    public function test_employee_filter_only_displays_letters_for_the_selected_employee(): void
    {
        $revina = $this->pegawai(['name' => 'Revina']);
        $pegawaiLain = $this->pegawai();
        Surat::factory()->create([
            'nomor_surat' => 'SURAT-REVINA',
            'pegawai_id' => $revina->id,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'SURAT-PEGAWAI-LAIN',
            'pegawai_id' => $pegawaiLain->id,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.index', ['pegawai' => $revina->id]))
            ->assertOk()
            ->assertSee('SURAT-REVINA')
            ->assertDontSee('SURAT-PEGAWAI-LAIN');
    }

    public function test_search_form_explains_enter_submission_and_reset_clears_query_parameters(): void
    {
        $url = route('kepala-bidang.surat.index', [
            'search' => '003',
            'status' => Surat::STATUS_SELESAI,
            'pegawai' => 'belum_ditugaskan',
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get($url)
            ->assertOk()
            ->assertSee('method="GET"', false)
            ->assertSee('enterkeyhint="search"', false)
            ->assertSee('Cari nomor surat, perihal, atau pemohon')
            ->assertSee('Ketik kata kunci, lalu tekan Enter atau klik Terapkan.')
            ->assertSee('href="'.route('kepala-bidang.surat.index').'"', false);
    }

    public function test_search_filters_and_pagination_still_work(): void
    {
        $revina = $this->pegawai();
        Surat::factory()->count(11)->create([
            'perihal' => 'Permohonan khusus',
            'pegawai_id' => $revina->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->create(['nomor_surat' => 'TIDAK-COCOK']);

        $response = $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.index', [
                'search' => 'Permohonan khusus',
                'status' => Surat::STATUS_SEDANG_DIPROSES,
                'pegawai' => $revina->id,
            ]))
            ->assertOk()
            ->assertDontSee('TIDAK-COCOK')
            ->assertViewHas('surats', fn ($surats): bool => $surats->count() === 10 && $surats->total() === 11);

        parse_str((string) parse_url($response->viewData('surats')->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('Permohonan khusus', $query['search']);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $query['status']);
    }

    public function test_application_timezone_defaults_to_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezoneName);
    }

    private function kepalaBidang(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_KEPALA_BIDANG,
        ], $attributes));
    }

    private function pegawai(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => User::ROLE_PEGAWAI,
        ], $attributes));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '123/ABC/2026',
            'tanggal_masuk' => '2026-09-03',
            'perihal' => 'Permohonan penetapan pajak daerah',
            'pemohon_pengirim' => 'PT XYZ',
            'pegawai_id' => null,
            'keterangan' => 'Catatan pengujian.',
        ], $overrides);
    }
}
