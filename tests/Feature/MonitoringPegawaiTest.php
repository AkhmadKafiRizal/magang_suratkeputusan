<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringPegawaiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_monitoring_index_and_detail(): void
    {
        $pegawai = $this->pegawai();

        $this->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertRedirect(route('login'));
        $this->get(route('kepala-bidang.monitoring-pegawai.show', $pegawai))
            ->assertRedirect(route('login'));
    }

    public function test_pegawai_is_forbidden_from_monitoring_index_and_detail(): void
    {
        $pegawai = $this->pegawai();

        $this->actingAs($pegawai)
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertForbidden();
        $this->actingAs($pegawai)
            ->get(route('kepala-bidang.monitoring-pegawai.show', $pegawai))
            ->assertForbidden();
    }

    public function test_kepala_bidang_can_open_monitoring_and_only_pegawai_are_listed(): void
    {
        $kabid = $this->kepalaBidang(['name' => 'Kafi Kabid']);
        $revina = $this->pegawai(['name' => 'Revina Pegawai']);

        $this->actingAs($kabid)
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertOk()
            ->assertSee('Monitoring Pegawai')
            ->assertSee('Revina Pegawai')
            ->assertSee('aria-current="page"', false)
            ->assertViewHas('pegawai', function ($pegawai) use ($revina, $kabid): bool {
                return $pegawai->pluck('id')->all() === [$revina->id]
                    && ! $pegawai->pluck('id')->contains($kabid->id);
            });
    }

    public function test_team_summary_counts_only_currently_assigned_employee_work(): void
    {
        $pegawai = $this->pegawai();
        Surat::factory()->count(2)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        Surat::factory()->count(3)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->count(4)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
        ]);
        Surat::factory()->count(5)->create([
            'pegawai_id' => null,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertOk()
            ->assertViewHas('ringkasan', [
                'total_pegawai' => 1,
                'belum_ditangani' => 2,
                'sedang_diproses' => 3,
                'selesai' => 4,
            ]);
    }

    public function test_employee_counts_total_active_and_workload_are_calculated_correctly(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $this->buatSuratAktif($pegawai, 2, 1);
        Surat::factory()->count(4)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertViewHas('pegawai', function ($pegawaiCollection): bool {
                $revina = $pegawaiCollection->firstWhere('name', 'Revina');

                return $revina !== null
                    && (int) $revina->belum_ditangani_count === 2
                    && (int) $revina->sedang_diproses_count === 1
                    && (int) $revina->selesai_count === 4
                    && (int) $revina->total_ditangani_count === 7
                    && $revina->surat_aktif_count === 3
                    && $revina->beban_kerja_label === 'Sedang';
            });
    }

    public function test_workload_thresholds_are_ringan_sedang_and_tinggi(): void
    {
        $ringan = $this->pegawai(['name' => 'Ringan']);
        $sedang = $this->pegawai(['name' => 'Sedang']);
        $tinggi = $this->pegawai(['name' => 'Tinggi']);
        $this->buatSuratAktif($ringan, 2);
        $this->buatSuratAktif($sedang, 3);
        $this->buatSuratAktif($tinggi, 6);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertViewHas('pegawai', function ($pegawai): bool {
                return $pegawai->firstWhere('name', 'Ringan')->beban_kerja_label === 'Ringan'
                    && $pegawai->firstWhere('name', 'Sedang')->beban_kerja_label === 'Sedang'
                    && $pegawai->firstWhere('name', 'Tinggi')->beban_kerja_label === 'Tinggi';
            })
            ->assertSee('workload-ringan', false)
            ->assertSee('workload-sedang', false)
            ->assertSee('workload-tinggi', false);
    }

    public function test_employees_are_sorted_by_active_work_ascending_then_name(): void
    {
        $zeta = $this->pegawai(['name' => 'Zeta']);
        $alfa = $this->pegawai(['name' => 'Alfa']);
        $beta = $this->pegawai(['name' => 'Beta']);
        $this->buatSuratAktif($beta, 3);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.index'))
            ->assertViewHas('pegawai', fn ($pegawai): bool => $pegawai->pluck('id')->all() === [
                $alfa->id,
                $zeta->id,
                $beta->id,
            ]);
    }

    public function test_detail_rejects_a_user_who_is_not_a_pegawai(): void
    {
        $kabid = $this->kepalaBidang();

        $this->actingAs($kabid)
            ->get(route('kepala-bidang.monitoring-pegawai.show', $kabid))
            ->assertNotFound();
    }

    public function test_detail_only_contains_the_selected_employees_letters_and_existing_detail_links(): void
    {
        $revina = $this->pegawai(['name' => 'Revina']);
        $pegawaiLain = $this->pegawai();
        $suratRevina = Surat::factory()->create([
            'nomor_surat' => 'SURAT-REVINA',
            'pegawai_id' => $revina->id,
            'ditugaskan_pada' => '2026-09-03 19:29:00',
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'SURAT-PEGAWAI-LAIN',
            'pegawai_id' => $pegawaiLain->id,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.show', $revina))
            ->assertOk()
            ->assertSee('SURAT-REVINA')
            ->assertDontSee('SURAT-PEGAWAI-LAIN')
            ->assertSee('03 Sep 2026, 19:29 WIB')
            ->assertSee('Belum Diproses')
            ->assertSee('Belum Selesai')
            ->assertSee(route('kepala-bidang.surat.show', $suratRevina), false)
            ->assertViewHas('surats', fn ($surats): bool => $surats->total() === 1
                && $surats->first()->pegawai_id === $revina->id);
    }

    public function test_detail_prioritizes_status_then_newest_letter_within_status(): void
    {
        $pegawai = $this->pegawai();
        $selesai = $this->surat($pegawai, Surat::STATUS_SELESAI, 'SELESAI', '2026-09-03');
        $proses = $this->surat($pegawai, Surat::STATUS_SEDANG_DIPROSES, 'PROSES', '2026-09-01');
        $belumLama = $this->surat($pegawai, Surat::STATUS_BELUM_DITANGANI, 'BELUM-LAMA', '2026-08-01');
        $belumBaru = $this->surat($pegawai, Surat::STATUS_BELUM_DITANGANI, 'BELUM-BARU', '2026-09-02');

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.show', $pegawai))
            ->assertViewHas('surats', fn ($surats): bool => $surats->pluck('id')->all() === [
                $belumBaru->id,
                $belumLama->id,
                $proses->id,
                $selesai->id,
            ]);
    }

    public function test_status_filter_and_search_by_number_or_subject_work(): void
    {
        $pegawai = $this->pegawai();
        $nomor = $this->surat($pegawai, Surat::STATUS_BELUM_DITANGANI, 'NOMOR-KHUSUS', '2026-09-01');
        $perihal = Surat::factory()->create([
            'nomor_surat' => 'LAINNYA',
            'perihal' => 'Permohonan pajak hotel',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
        Surat::factory()->create([
            'nomor_surat' => 'PERIHAL-SAMA-STATUS-LAIN',
            'perihal' => 'Permohonan pajak hotel',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SELESAI,
        ]);
        $this->surat($pegawai, Surat::STATUS_SELESAI, 'TIDAK-COCOK', '2026-09-02');

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.show', [
                'pegawai' => $pegawai,
                'q' => 'NOMOR-KHUSUS',
            ]))
            ->assertViewHas('surats', fn ($surats): bool => $surats->pluck('id')->all() === [$nomor->id]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.show', [
                'pegawai' => $pegawai,
                'q' => 'pajak hotel',
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ]))
            ->assertOk()
            ->assertSee('method="GET"', false)
            ->assertSee('enterkeyhint="search"', false)
            ->assertSee('Ketik kata kunci, lalu tekan Enter atau klik Terapkan.')
            ->assertViewHas('surats', fn ($surats): bool => $surats->pluck('id')->all() === [$perihal->id]);
    }

    public function test_detail_has_helpful_empty_states_with_and_without_filters(): void
    {
        $pegawai = $this->pegawai();
        $kabid = $this->kepalaBidang();

        $this->actingAs($kabid)
            ->get(route('kepala-bidang.monitoring-pegawai.show', $pegawai))
            ->assertOk()
            ->assertSee('Belum ada surat yang ditugaskan kepada pegawai ini.');

        $this->actingAs($kabid)
            ->get(route('kepala-bidang.monitoring-pegawai.show', [
                'pegawai' => $pegawai,
                'q' => 'tidak-ada',
            ]))
            ->assertOk()
            ->assertSee('Tidak ada surat yang sesuai dengan filter.');
    }

    public function test_pagination_preserves_search_and_status_query_string(): void
    {
        $pegawai = $this->pegawai();
        Surat::factory()->count(11)->create([
            'perihal' => 'Permohonan khusus',
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $response = $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.monitoring-pegawai.show', [
                'pegawai' => $pegawai,
                'q' => 'Permohonan khusus',
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ]))
            ->assertOk()
            ->assertViewHas('surats', fn ($surats): bool => $surats->count() === 10 && $surats->total() === 11);

        parse_str((string) parse_url($response->viewData('surats')->nextPageUrl(), PHP_URL_QUERY), $query);
        $this->assertSame('Permohonan khusus', $query['q']);
        $this->assertSame(Surat::STATUS_SEDANG_DIPROSES, $query['status']);
    }

    public function test_dashboard_and_sidebar_link_to_the_new_monitoring_pages(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $kabid = $this->kepalaBidang();

        $this->actingAs($kabid)
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertSee(route('kepala-bidang.monitoring-pegawai.index'), false)
            ->assertSee(route('kepala-bidang.monitoring-pegawai.show', $pegawai), false);
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

    private function buatSuratAktif(User $pegawai, int $belumDitangani, int $sedangDiproses = 0): void
    {
        Surat::factory()->count($belumDitangani)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_BELUM_DITANGANI,
        ]);
        Surat::factory()->count($sedangDiproses)->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);
    }

    private function surat(User $pegawai, string $status, string $nomor, string $tanggalMasuk): Surat
    {
        return Surat::factory()->create([
            'nomor_surat' => $nomor,
            'tanggal_masuk' => $tanggalMasuk,
            'pegawai_id' => $pegawai->id,
            'status' => $status,
        ]);
    }
}
