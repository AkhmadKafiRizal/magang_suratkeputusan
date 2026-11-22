<?php

namespace Tests\Feature;

use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractionFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_has_personalized_feedback(): void
    {
        $user = $this->pengguna(['name' => 'Kafi', 'password' => 'password']);

        $response = $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('auth_feedback', [
                'title' => 'Login Berhasil',
                'message' => 'Selamat datang, Kafi.',
            ]);

        $this->followRedirects($response)
            ->assertSee('data-auth-feedback', false)
            ->assertSee('class="auth-feedback-card"', false)
            ->assertSeeInOrder(['data-auth-feedback', '<main class="dashboard-main'], false)
            ->assertSee('Login Berhasil')
            ->assertSee('Selamat datang, Kafi.');
    }

    public function test_failed_login_has_human_friendly_error_and_keeps_username(): void
    {
        $user = $this->pegawai(['password' => 'password']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'keliru',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'Username/email atau kata sandi tidak sesuai.'])
            ->assertSessionHasInput('username', $user->username);
    }

    public function test_logout_uses_post_and_has_success_feedback(): void
    {
        $user = $this->pengguna();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'))
            ->assertSessionHas('auth_feedback', [
                'title' => 'Logout Berhasil',
                'message' => 'Anda berhasil keluar dari sistem.',
            ]);

        $this->assertGuest();

        $this->followRedirects($response)
            ->assertSee('data-auth-feedback', false)
            ->assertSee('Logout Berhasil')
            ->assertSee('Anda berhasil keluar dari sistem.');
    }

    public function test_global_interaction_components_and_login_loading_hooks_are_rendered(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-loading-form', false)
            ->assertSee('data-loading-label="Memproses..."', false)
            ->assertSee('class="toggle-password"', false)
            ->assertSee('id="stars-container"', false);

        $this->actingAs($this->pengguna())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-confirmation-modal', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('data-confirm-title="Keluar dari SIARKA?"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('data-sidebar-close', false)
            ->assertSee('data-page-transition', false)
            ->assertSee(route('account.edit'), false);
    }

    public function test_global_toast_supports_all_feedback_types_and_escapes_messages(): void
    {
        $this->actingAs($this->pengguna())
            ->withSession([
                'success' => 'Data berhasil disimpan.',
                'error' => '<script>alert("xss")</script>',
                'warning' => 'Periksa kembali data.',
                'info' => 'Informasi terbaru tersedia.',
            ])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-toast-region', false)
            ->assertSee('app-toast-success', false)
            ->assertSee('app-toast-error', false)
            ->assertSee('app-toast-warning', false)
            ->assertSee('app-toast-info', false)
            ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_store_surat_has_success_feedback_and_dirty_loading_hooks(): void
    {
        $pengguna = $this->pengguna();

        $this->actingAs($pengguna)
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('data-dirty-form', false)
            ->assertSee('data-loading-label="Menyimpan..."', false)
            ->assertSee('id="tanggal_masuk"', false)
            ->assertSee('data-open-date-picker', false)
            ->assertSee('id="kategori_surat_id"', false)
            ->assertSee('data-field-click-feedback', false);

        $response = $this->actingAs($pengguna)
            ->post(route('surat.store'), $this->payload());

        $response->assertSessionHas('success', 'Surat berhasil dicatat.');
    }

    public function test_administrative_update_has_success_feedback(): void
    {
        $surat = Surat::factory()->create(['nomor_surat' => '2026.4000.001']);

        $this->actingAs($this->pengguna())
            ->put(route('surat.update', $surat), $this->payload([
                'nomor_surat' => $surat->nomor_surat,
                'keterangan' => 'Catatan administratif diperbarui',
            ]))
            ->assertSessionHas('success', 'Data surat berhasil diperbarui.');
    }

    public function test_attempts_to_choose_another_handler_are_rejected_without_success_feedback(): void
    {
        $pengguna = $this->pengguna();
        $revina = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create();

        $this->actingAs($pengguna)
            ->put(route('surat.update', $surat), $this->payloadFor($surat, [
                'pegawai_id' => $revina->id,
            ]))
            ->assertSessionHasErrors('pegawai_id')
            ->assertSessionMissing('success');
    }

    public function test_edit_form_omits_handler_selection_and_finish_form_keeps_confirmation_hooks(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($this->pengguna())
            ->get(route('surat.edit', $surat))
            ->assertOk()
            ->assertDontSee('data-assignment-confirm', false)
            ->assertDontSee('name="pegawai_id"', false)
            ->assertSee('data-dirty-form', false)
            ->assertSee('data-loading-label="Memperbarui..."', false);

        $this->actingAs($pegawai)
            ->get(route('surat-saya.show', $surat))
            ->assertOk()
            ->assertSee('data-confirm-title="Tandai Surat Selesai?"', false)
            ->assertSee('data-confirm-label="Ya, Tandai Selesai"', false)
            ->assertSee('data-loading-label="Menyelesaikan..."', false);
    }

    public function test_employee_workflow_actions_have_specific_feedback(): void
    {
        $pegawai = $this->pegawai();
        $surat = Surat::factory()->create(['pegawai_id' => $pegawai->id]);

        $this->actingAs($pegawai)
            ->patch(route('surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertSessionHas('success', 'Surat berhasil mulai diproses.');

        $this->actingAs($pegawai)
            ->patch(route('surat-saya.update-status', $surat->fresh()), [
                'status' => Surat::STATUS_SELESAI,
            ])
            ->assertSessionHas('success', 'Surat berhasil diselesaikan.');
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

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '2026.4000.002',
            'tanggal_masuk' => '2026-09-04',
            'pemohon_pengirim' => 'PT Contoh',
            'kategori_surat_id' => KategoriSurat::query()
                ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
                ->value('id'),
            'keterangan' => null,
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function payloadFor(Surat $surat, array $overrides = []): array
    {
        return $this->payload(array_merge([
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_masuk' => $surat->tanggal_masuk->format('Y-m-d'),
            'pemohon_pengirim' => $surat->pemohon_pengirim,
            'kategori_surat_id' => $surat->kategori_surat_id
                ?? KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)->value('id'),
            'keterangan' => $surat->keterangan,
        ], $overrides));
    }
}
