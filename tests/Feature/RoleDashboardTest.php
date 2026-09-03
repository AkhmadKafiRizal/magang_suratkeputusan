<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_keeps_the_login_form_and_renders_the_star_effect_layer(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('id="stars-container"', false)
            ->assertSee('class="sky-effects"', false)
            ->assertSee("star.className = 'twinkle-star'", false)
            ->assertSee("star.className = 'shooting-star'", false)
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_guest_cannot_open_any_dashboard_directly(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard.kepala-bidang'))->assertRedirect(route('login'));
        $this->get(route('dashboard.pegawai'))->assertRedirect(route('login'));
    }

    public function test_kepala_bidang_is_redirected_to_the_correct_dashboard_after_login(): void
    {
        $kafi = User::factory()->create([
            'name' => 'Kafi',
            'email' => 'kafi@example.com',
            'password' => 'password',
            'role' => User::ROLE_KEPALA_BIDANG,
        ]);

        $this->post(route('login.store'), [
            'email' => $kafi->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.kepala-bidang'));

        $this->assertAuthenticatedAs($kafi);
    }

    public function test_pegawai_is_redirected_to_the_correct_dashboard_after_login(): void
    {
        $revina = User::factory()->create([
            'name' => 'Revina',
            'email' => 'revina@example.com',
            'password' => 'password',
            'role' => User::ROLE_PEGAWAI,
        ]);

        $this->post(route('login.store'), [
            'email' => $revina->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard.pegawai'));

        $this->assertAuthenticatedAs($revina);
    }

    public function test_kepala_bidang_can_open_their_dashboard_and_see_pegawai(): void
    {
        $kafi = User::factory()->create([
            'name' => 'Kafi',
            'role' => User::ROLE_KEPALA_BIDANG,
        ]);

        User::factory()->create([
            'name' => 'Revina',
            'role' => User::ROLE_PEGAWAI,
        ]);

        $this->actingAs($kafi)
            ->get(route('dashboard.kepala-bidang'))
            ->assertOk()
            ->assertSee('Dashboard Kepala Bidang')
            ->assertSee('Selamat datang, Kafi')
            ->assertSee('Revina')
            ->assertSee('Belum ada data surat');
    }

    public function test_pegawai_cannot_open_kepala_bidang_dashboard(): void
    {
        $revina = User::factory()->create([
            'name' => 'Revina',
            'role' => User::ROLE_PEGAWAI,
        ]);

        $this->actingAs($revina)
            ->get(route('dashboard.kepala-bidang'))
            ->assertForbidden();
    }

    public function test_each_role_is_restricted_to_its_own_dashboard(): void
    {
        $kafi = User::factory()->create(['role' => User::ROLE_KEPALA_BIDANG]);
        $revina = User::factory()->create(['role' => User::ROLE_PEGAWAI]);

        $this->actingAs($kafi)
            ->get(route('dashboard.pegawai'))
            ->assertForbidden();

        $this->actingAs($revina)
            ->get(route('dashboard.pegawai'))
            ->assertOk()
            ->assertSee('Dashboard Pegawai');
    }
}
