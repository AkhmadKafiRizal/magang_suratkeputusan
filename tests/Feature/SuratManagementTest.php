<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_both_surat_areas(): void
    {
        $this->get(route('surat.index'))->assertRedirect(route('login'));
        $this->get(route('surat-saya.index'))->assertRedirect(route('login'));
    }

    public function test_pegawai_can_access_surat_management(): void
    {
        $pegawai = $this->pegawai();

        $this->actingAs($pegawai)->get(route('surat.index'))->assertOk();
        $this->actingAs($pegawai)
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('letter-form-card', false);
    }

    public function test_pengguna_can_open_data_surat(): void
    {
        Surat::factory()->create([
            'nomor_surat' => '2026.0002.095',
            'pemohon_pengirim' => 'ALBERTUS ANDIKA GUNAWAN',
            'tanggal_masuk' => '2026-02-09',
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => null,
            'keterangan' => 'Catatan administratif contoh.',
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('Daftar Surat')
            ->assertSee('Tambah Surat')
            ->assertSee('<th>No.</th>', false)
            ->assertSee('<th>Nomor Surat</th>', false)
            ->assertSee('<th>Nama Pemohon</th>', false)
            ->assertSee('<th>Status</th>', false)
            ->assertSee('<th>Tanggal Masuk</th>', false)
            ->assertSee('<th>Tanggal Selesai</th>', false)
            ->assertSee('<th>Keterangan</th>', false)
            ->assertSee('<th>Detail</th>', false)
            ->assertSee('2026.0002.095')
            ->assertSee('ALBERTUS ANDIKA GUNAWAN')
            ->assertSee('status-badge status-selesai', false)
            ->assertSee('Selesai')
            ->assertSee('Tidak tercatat')
            ->assertSee('Catatan administratif contoh.')
            ->assertSee('Lihat Detail')
            ->assertSee('href="'.route('surat.show', Surat::firstOrFail()).'"', false)
            ->assertDontSee('<th>Perihal</th>', false)
            ->assertDontSee('<th>Pegawai yang Menangani</th>', false)
            ->assertDontSee('<th>Aksi</th>', false);
    }

    public function test_data_surat_menampilkan_semua_label_status(): void
    {
        Surat::factory()->create([
            'nomor_surat' => 'STATUS-BELUM',
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'STATUS-PROSES',
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'STATUS-SELESAI',
            'status' => Surat::STATUS_SELESAI,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('status-badge status-belum_ditangani', false)
            ->assertSee('Belum Ditangani')
            ->assertSee('status-badge status-sedang_diproses', false)
            ->assertSee('Dalam Proses')
            ->assertSee('status-badge status-selesai', false)
            ->assertSee('Selesai');
    }

    public function test_data_surat_displays_times_below_entry_and_completion_dates(): void
    {
        Surat::factory()->create([
            'nomor_surat' => 'JAM-DATA-SURAT/2026',
            'tanggal_masuk' => '2026-11-12',
            'status' => Surat::STATUS_SELESAI,
            'selesai_pada' => '2026-11-13 16:20:00',
            'created_at' => '2026-11-12 08:45:00',
            'updated_at' => '2026-11-13 16:20:00',
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.index'))
            ->assertOk()
            ->assertSee('12 Nov 2026')
            ->assertSee('08:45 WIB')
            ->assertSee('13 Nov 2026')
            ->assertSee('16:20 WIB')
            ->assertSee('class="table-date-time"', false);
    }

    public function test_pengguna_can_create_a_surat_and_status_is_always_belum_ditangani(): void
    {
        $response = $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload());

        $surat = Surat::firstOrFail();
        $response->assertRedirect(route('surat.show', $surat));
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertNull($surat->pegawai_id);
        $this->assertNull($surat->ditugaskan_pada);
        $this->assertNull($surat->mulai_diproses_pada);
        $this->assertNull($surat->selesai_pada);
    }

    public function test_nomor_surat_yang_sudah_ada_ditolak_dengan_pesan_yang_jelas(): void
    {
        Surat::factory()->create([
            'nomor_surat' => '001/UNIK/2026',
            'pemohon_pengirim' => 'PEMOHON YANG SAMA',
        ]);

        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '  001/unik/2026  ',
                'pemohon_pengirim' => 'PEMOHON YANG SAMA',
            ]))
            ->assertSessionHasErrors([
                'nomor_surat' => 'Nomor surat sudah ada. Koreksi kembali input nomor surat Anda.',
            ]);

        $this->assertDatabaseCount('surats', 1);
    }

    public function test_nama_pemohon_boleh_sama_selama_nomor_surat_berbeda(): void
    {
        Surat::factory()->create([
            'nomor_surat' => '001/NAMA-SAMA/2026',
            'pemohon_pengirim' => 'NAMA PEMOHON SAMA',
        ]);

        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '  002/NAMA-SAMA/2026  ',
                'pemohon_pengirim' => 'NAMA PEMOHON SAMA',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('surats', [
            'nomor_surat' => '002/NAMA-SAMA/2026',
            'pemohon_pengirim' => 'NAMA PEMOHON SAMA',
        ]);
        $this->assertDatabaseCount('surats', 2);
    }

    public function test_edit_boleh_mempertahankan_nomor_sendiri_tetapi_tidak_boleh_memakai_nomor_surat_lain(): void
    {
        $suratPertama = Surat::factory()->create(['nomor_surat' => '001/EDIT/2026']);
        $suratKedua = Surat::factory()->create(['nomor_surat' => '002/EDIT/2026']);
        $pengguna = $this->pengguna();

        $this->actingAs($pengguna)
            ->put(route('surat.update', $suratKedua), $this->validPayload([
                'nomor_surat' => '002/EDIT/2026',
            ]))
            ->assertRedirect(route('surat.show', $suratKedua));

        $this->actingAs($pengguna)
            ->put(route('surat.update', $suratKedua), $this->validPayload([
                'nomor_surat' => $suratPertama->nomor_surat,
            ]))
            ->assertSessionHasErrors([
                'nomor_surat' => 'Nomor surat sudah ada. Koreksi kembali input nomor surat Anda.',
            ]);

        $this->assertSame('002/EDIT/2026', $suratKedua->fresh()->nomor_surat);
    }

    public function test_create_form_has_no_status_input_and_shows_initial_status_information(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertSee('Status awal surat otomatis Belum Ditangani.');
    }

    public function test_perihal_is_not_shown_and_is_not_required_when_recording_a_letter(): void
    {
        $this->actingAs($this->pengguna())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertDontSee('name="perihal"', false)
            ->assertDontSee('>Perihal<', false);

        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertNull(Surat::firstOrFail()->perihal);
    }

    public function test_pengguna_tidak_dapat_memilih_penangan_lain_saat_mencatat_surat(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);

        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload([
                'pegawai_id' => $pegawai->id,
            ]))
            ->assertSessionHasErrors([
                'pegawai_id' => 'Penangan surat tidak dapat dipilih melalui formulir ini. Setiap pegawai harus mengambil sendiri surat melalui menu Ambil Surat.',
            ]);

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_halaman_surat_tidak_menyediakan_kontrol_untuk_memilih_penangan(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->pengguna())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertDontSee('name="pegawai_id"', false)
            ->assertDontSee('Tugaskan Pegawai');

        $this->actingAs($this->pengguna())
            ->get(route('surat.edit', $surat))
            ->assertOk()
            ->assertDontSee('name="pegawai_id"', false)
            ->assertDontSee('data-assignment-confirm', false);

        $this->actingAs($this->pengguna())
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Belum Diambil')
            ->assertDontSee('Ditugaskan')
            ->assertDontSee('Tugaskan Pegawai')
            ->assertDontSee('#pegawai_id');
    }

    public function test_catatan_administratif_is_saved_to_keterangan(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload([
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

        $this->actingAs($this->pengguna())
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Keterangan')
            ->assertSee('Tidak ada keterangan');

        $this->actingAs($pegawai)
            ->get(route('surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('Tidak ada keterangan');
    }

    public function test_pengguna_cannot_send_sedang_diproses_status_manually(): void
    {
        $this->actingAs($this->pengguna())
            ->post(route('surat.store'), $this->validPayload([
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_pengguna_cannot_send_selesai_status_manually(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
                'status' => Surat::STATUS_SELESAI,
            ]))
            ->assertSessionHasErrors('status');

        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->fresh()->status);
    }

    public function test_pengguna_can_edit_administrative_data_without_changing_status(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
                'nomor_surat' => '456/EDIT/2026',
            ]))
            ->assertRedirect(route('surat.show', $surat));

        $surat->refresh();
        $this->assertSame('456/EDIT/2026', $surat->nomor_surat);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->status);
    }

    public function test_pengguna_cannot_assign_an_employee_through_update_request(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create();

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
                'pegawai_id' => $pegawai->id,
            ]))
            ->assertSessionHasErrors('pegawai_id');

        $surat->refresh();
        $this->assertNull($surat->pegawai_id);
        $this->assertNull($surat->ditugaskan_pada);
    }

    public function test_pengguna_cannot_reassign_an_employee_through_update_request(): void
    {
        $pegawaiPertama = $this->pegawai();
        $pegawaiKedua = $this->pegawai();
        $waktuPengambilan = now()->subDay()->startOfSecond();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawaiPertama->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
            'ditugaskan_pada' => $waktuPengambilan,
        ]);

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
                'pegawai_id' => $pegawaiKedua->id,
            ]))
            ->assertSessionHasErrors('pegawai_id');

        $surat->refresh();
        $this->assertSame($pegawaiPertama->id, $surat->pegawai_id);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $surat->status);
        $this->assertTrue($surat->ditugaskan_pada->equalTo($waktuPengambilan));
    }

    public function test_pengguna_cannot_remove_an_employee_through_update_request(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'ditugaskan_pada' => now()->subHour(),
        ]);

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
                'pegawai_id' => null,
            ]))
            ->assertSessionHasErrors('pegawai_id');

        $surat->refresh();
        $this->assertSame($pegawai->id, $surat->pegawai_id);
        $this->assertNotNull($surat->ditugaskan_pada);
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
            ->get(route('surat-saya.index'))
            ->assertOk()
            ->assertSee($milikRevina->nomor_surat)
            ->assertDontSee('MILIK-ORANG-LAIN')
            ->assertSee('Surat Saya');

        $this->actingAs($revina)
            ->get(route('surat-saya.show', $milikRevina))
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
            ->get(route('surat-saya.show', $suratPegawaiLain))
            ->assertForbidden();

        $this->actingAs($revina)
            ->patch(route('surat-saya.update-status', $suratPegawaiLain), [
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
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
                'nomor_surat' => 'TIDAK-BOLEH-BERUBAH',
            ])
            ->assertRedirect(route('surat-saya.show', $surat));

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
            ->patch(route('surat-saya.update-status', $surat), [
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

        $this->actingAs($this->pengguna())
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Mulai Diproses')
            ->assertSee('Belum Tercatat');

        $this->actingAs($pegawai)
            ->get(route('surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('Belum Tercatat');
    }

    public function test_pengguna_cannot_manipulate_process_timestamps(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->validPayload([
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
            ->patch(route('surat-saya.update-status', $surat), [
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
            ->patch(route('surat-saya.update-status', $surat), [
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
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Surat::STATUS_SELESAI, $surat->fresh()->status);
    }

    public function test_user_cannot_update_status_of_another_users_surat(): void
    {
        $surat = Surat::factory()->create(['pegawai_id' => $this->pegawai()->id]);

        $this->actingAs($this->pengguna())
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertForbidden();
    }

    public function test_dashboard_pengguna_uses_actual_status_counts(): void
    {
        Surat::factory()->count(2)->create(['status' => Surat::STATUS_BELUM_DITANGANI]);
        Surat::factory()->count(3)->create(['status' => Surat::STATUS_SEDANG_DIPROSES]);
        Surat::factory()->create(['status' => Surat::STATUS_SELESAI]);

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('ringkasan', [
                'total' => 6,
                'diproses' => 3,
                'selesai' => 1,
                'belum_ditangani' => 2,
            ]);
    }

    public function test_dashboard_displays_the_recorded_time_below_tanggal_masuk(): void
    {
        $pegawai = $this->pegawai();
        Surat::factory()->create([
            'nomor_surat' => 'JAM-MASUK/2026',
            'tanggal_masuk' => '2026-09-23',
            'pegawai_id' => $pegawai->id,
            'created_at' => '2026-09-23 08:45:00',
            'updated_at' => '2026-09-23 08:45:00',
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('23 Sep 2026')
            ->assertSee('08:45 WIB')
            ->assertSee('class="table-date-time"', false);

        $this->actingAs($pegawai)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('23 Sep 2026')
            ->assertSee('08:45 WIB')
            ->assertSee('class="table-date-time"', false);
    }

    public function test_dashboard_does_not_show_recent_activity_section(): void
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
        $surat->aktivitas()->createMany([
            [
                'user_id' => $pegawai->id,
                'tipe' => AktivitasSurat::TIPE_PENANGAN_MULAI,
                'deskripsi' => "Catatan lama untuk surat {$surat->nomor_surat}",
                'created_at' => now()->subMinutes(30),
                'updated_at' => now()->subMinutes(30),
            ],
            [
                'user_id' => $pegawai->id,
                'tipe' => AktivitasSurat::TIPE_MULAI_DIPROSES,
                'deskripsi' => "Revina mulai memproses surat {$surat->nomor_surat}",
                'created_at' => now()->subMinutes(20),
                'updated_at' => now()->subMinutes(20),
            ],
            [
                'user_id' => $pegawai->id,
                'tipe' => AktivitasSurat::TIPE_SELESAI,
                'deskripsi' => "Revina menyelesaikan surat {$surat->nomor_surat}",
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(10),
            ],
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Aktivitas Terbaru')
            ->assertDontSee("Revina menyelesaikan surat {$surat->nomor_surat}")
            ->assertDontSee("Revina mulai memproses surat {$surat->nomor_surat}");
    }

    public function test_dashboard_does_not_receive_activity_collection(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        Surat::factory()->create([
            'nomor_surat' => 'TANPA-AKTIVITAS',
            'pegawai_id' => $pegawai->id,
        ]);

        $response = $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Revina mulai memproses surat TANPA-AKTIVITAS');

        $this->assertArrayNotHasKey('aktivitasTerbaru', $response->original->getData());
    }

    public function test_dashboard_ignores_activity_records(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
            'ditugaskan_pada' => now()->subMinutes(30),
            'mulai_diproses_pada' => now()->subMinutes(20),
            'selesai_pada' => now()->subMinutes(10),
        ]);

        foreach (range(1, 7) as $urutan) {
            $surat->aktivitas()->create([
                'user_id' => $pegawai->id,
                'tipe' => AktivitasSurat::TIPE_SURAT_DIEDIT,
                'deskripsi' => "Aktivitas resmi {$urutan}",
                'created_at' => now()->subMinutes($urutan),
                'updated_at' => now()->subMinutes($urutan),
            ]);
        }

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Aktivitas resmi 1')
            ->assertDontSee('Aktivitas Terbaru');
    }

    public function test_single_dashboard_shows_global_counts_and_latest_letters(): void
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
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('ringkasan', [
                'total' => 4,
                'diproses' => 2,
                'selesai' => 1,
                'belum_ditangani' => 1,
            ])
            ->assertSee('SELESAI-REVINA')
            ->assertSee('BUKAN-MILIK-REVINA');
    }

    public function test_dashboard_does_not_show_monitoring_pegawai_section(): void
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

        $response = $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Monitoring Pegawai');

        $this->assertArrayNotHasKey('pegawai', $response->original->getData());
    }

    public function test_dashboard_does_not_link_to_employee_monitoring_detail(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('monitoring-pegawai.show', $pegawai), false);
    }

    public function test_handler_filter_is_hidden_and_handler_query_is_ignored(): void
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

        $this->actingAs($this->pengguna())
            ->get(route('surat.index', ['penangan' => $revina->id]))
            ->assertOk()
            ->assertSee('SURAT-REVINA')
            ->assertSee('SURAT-PEGAWAI-LAIN')
            ->assertDontSee('name="penangan"', false)
            ->assertDontSee('Semua Penangan');
    }

    public function test_handler_name_is_hidden_from_lists_and_only_shown_on_letter_detail(): void
    {
        $penangan = $this->pegawai(['name' => 'PEGAWAI PENANGAN RAHASIA']);
        $surat = Surat::factory()->create([
            'nomor_surat' => 'DETAIL-PENANGAN/2026',
            'pegawai_id' => $penangan->id,
            'status' => Surat::STATUS_SELESAI,
            'tanggal_masuk' => now()->toDateString(),
            'selesai_pada' => now(),
        ]);
        $pengguna = $this->pengguna(['name' => 'Pengguna Pemeriksa']);

        $this->actingAs($pengguna)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('PEGAWAI PENANGAN RAHASIA');

        $this->actingAs($pengguna)
            ->get(route('surat.index'))
            ->assertOk()
            ->assertDontSee('PEGAWAI PENANGAN RAHASIA');

        $this->actingAs($pengguna)
            ->get(route('laporan.index', ['tahun' => now()->year]))
            ->assertOk()
            ->assertDontSee('PEGAWAI PENANGAN RAHASIA');

        $this->actingAs($pengguna)
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Pegawai yang Menangani')
            ->assertSee('PEGAWAI PENANGAN RAHASIA');
    }

    public function test_search_form_explains_enter_submission_and_reset_clears_query_parameters(): void
    {
        $url = route('surat.index', [
            'search' => '003',
            'status' => Surat::STATUS_SELESAI,
        ]);

        $this->actingAs($this->pengguna())
            ->get($url)
            ->assertOk()
            ->assertSee('method="GET"', false)
            ->assertSee('enterkeyhint="search"', false)
            ->assertSee('Cari nomor surat, pemohon, atau keterangan')
            ->assertSee('Ketik kata kunci, lalu tekan Enter atau klik Terapkan.')
            ->assertSee('href="'.route('surat.index').'"', false);
    }

    public function test_search_filters_and_pagination_still_work(): void
    {
        $revina = $this->pegawai();
        Surat::factory()->count(11)->create([
            'pemohon_pengirim' => 'Pemohon khusus',
            'pegawai_id' => $revina->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->create(['nomor_surat' => 'TIDAK-COCOK']);

        $response = $this->actingAs($this->pengguna())
            ->get(route('surat.index', [
                'search' => 'Pemohon khusus',
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ]))
            ->assertOk()
            ->assertDontSee('TIDAK-COCOK')
            ->assertViewHas('surats', fn ($surats): bool => $surats->count() === 10 && $surats->total() === 11);

        parse_str((string) parse_url($response->viewData('surats')->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('Pemohon khusus', $query['search']);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $query['status']);
    }

    public function test_application_timezone_defaults_to_asia_jakarta(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Asia/Jakarta', now()->timezoneName);
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

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '123/ABC/2026',
            'tanggal_masuk' => '2026-09-03',
            'pemohon_pengirim' => 'PT XYZ',
            'kategori_surat_id' => KategoriSurat::query()
                ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
                ->value('id'),
            'keterangan' => 'Catatan pengujian.',
        ], $overrides);
    }
}
