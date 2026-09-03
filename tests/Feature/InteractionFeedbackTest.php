<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteractionFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_has_personalized_feedback(): void
    {
        $user = $this->kepalaBidang(['name' => 'Kafi', 'password' => 'password']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard.kepala-bidang'))
            ->assertSessionHas('auth_feedback', [
                'title' => 'Login Berhasil',
                'message' => 'Selamat datang, Kafi.',
            ]);

        $this->followRedirects($response)
            ->assertSee('data-auth-feedback', false)
            ->assertSee('class="auth-feedback-card"', false)
            ->assertSee('Login Berhasil')
            ->assertSee('Selamat datang, Kafi.');
    }

    public function test_failed_login_has_human_friendly_error_and_keeps_email(): void
    {
        $user = $this->pegawai(['password' => 'password']);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'keliru',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Email atau password tidak sesuai.'])
            ->assertSessionHasInput('email', $user->email);
    }

    public function test_logout_uses_post_and_has_success_feedback(): void
    {
        $user = $this->kepalaBidang();

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

        $this->actingAs($this->kepalaBidang())
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertSee('data-confirmation-modal', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('data-confirm-title="Keluar dari Sistem?"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('data-sidebar-close', false);
    }

    public function test_global_toast_supports_all_feedback_types_and_escapes_messages(): void
    {
        $this->actingAs($this->kepalaBidang())
            ->withSession([
                'success' => 'Data berhasil disimpan.',
                'error' => '<script>alert("xss")</script>',
                'warning' => 'Periksa kembali data.',
                'info' => 'Informasi terbaru tersedia.',
            ])
            ->get(route('dashboard.kepala-bidang'))
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
        $kabid = $this->kepalaBidang();

        $this->actingAs($kabid)
            ->get(route('kepala-bidang.surat.create'))
            ->assertOk()
            ->assertSee('data-dirty-form', false)
            ->assertSee('data-loading-label="Menyimpan..."', false);

        $response = $this->actingAs($kabid)
            ->post(route('kepala-bidang.surat.store'), $this->payload());

        $response->assertSessionHas('success', 'Surat berhasil dicatat.');
    }

    public function test_administrative_update_has_success_feedback(): void
    {
        $surat = Surat::factory()->create();

        $this->actingAs($this->kepalaBidang())
            ->put(route('kepala-bidang.surat.update', $surat), $this->payload([
                'nomor_surat' => $surat->nomor_surat,
                'perihal' => 'Perihal administratif diperbarui',
            ]))
            ->assertSessionHas('success', 'Data surat berhasil diperbarui.');
    }

    public function test_assignment_reassignment_and_unassignment_have_specific_feedback(): void
    {
        $kabid = $this->kepalaBidang();
        $revina = $this->pegawai(['name' => 'Revina']);
        $diah = $this->pegawai(['name' => 'Diah']);
        $surat = Surat::factory()->create();

        $this->actingAs($kabid)
            ->put(route('kepala-bidang.surat.update', $surat), $this->payloadFor($surat, [
                'pegawai_id' => $revina->id,
            ]))
            ->assertSessionHas('success', 'Surat berhasil ditugaskan kepada Revina.');

        $this->actingAs($kabid)
            ->put(route('kepala-bidang.surat.update', $surat->fresh()), $this->payloadFor($surat->fresh(), [
                'pegawai_id' => $diah->id,
            ]))
            ->assertSessionHas('success', 'Penugasan berhasil dipindahkan kepada Diah.');

        $this->actingAs($kabid)
            ->put(route('kepala-bidang.surat.update', $surat->fresh()), $this->payloadFor($surat->fresh(), [
                'pegawai_id' => null,
            ]))
            ->assertSessionHas('success', 'Penugasan surat berhasil dihapus.');
    }

    public function test_edit_and_finish_forms_render_the_required_confirmation_hooks(): void
    {
        $pegawai = $this->pegawai(['name' => 'Revina']);
        $surat = Surat::factory()->create([
            'pegawai_id' => $pegawai->id,
            'status' => Surat::STATUS_SEDANG_DIPROSES,
        ]);

        $this->actingAs($this->kepalaBidang())
            ->get(route('kepala-bidang.surat.edit', $surat))
            ->assertOk()
            ->assertSee('data-assignment-confirm', false)
            ->assertSee('data-initial-pegawai-id="'.$pegawai->id.'"', false)
            ->assertSee('data-initial-pegawai-name="Revina"', false)
            ->assertSee('data-dirty-form', false)
            ->assertSee('data-loading-label="Memperbarui..."', false);

        $this->actingAs($pegawai)
            ->get(route('pegawai.surat-saya.show', $surat))
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
            ->patch(route('pegawai.surat-saya.update-status', $surat), [
                'status' => Surat::STATUS_SEDANG_DIPROSES,
            ])
            ->assertSessionHas('success', 'Surat mulai diproses.');

        $this->actingAs($pegawai)
            ->patch(route('pegawai.surat-saya.update-status', $surat->fresh()), [
                'status' => Surat::STATUS_SELESAI,
            ])
            ->assertSessionHas('success', 'Surat berhasil ditandai selesai.');
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

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => 'UI/001/2026',
            'tanggal_masuk' => '2026-09-04',
            'perihal' => 'Pengujian feedback antarmuka',
            'pemohon_pengirim' => 'PT Contoh',
            'pegawai_id' => null,
            'keterangan' => null,
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function payloadFor(Surat $surat, array $overrides = []): array
    {
        return $this->payload(array_merge([
            'nomor_surat' => $surat->nomor_surat,
            'tanggal_masuk' => $surat->tanggal_masuk->format('Y-m-d'),
            'perihal' => $surat->perihal,
            'pemohon_pengirim' => $surat->pemohon_pengirim,
            'pegawai_id' => $surat->pegawai_id,
            'keterangan' => $surat->keterangan,
        ], $overrides));
    }
}
