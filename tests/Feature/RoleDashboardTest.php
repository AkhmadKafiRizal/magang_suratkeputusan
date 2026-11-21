<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_uses_username_and_password_only(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('action="'.route('login.store').'"', false)
            ->assertSee('name="username"', false)
            ->assertDontSee('name="email"', false)
            ->assertSee('name="password"', false);
    }

    public function test_guest_cannot_open_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_is_redirected_to_the_single_dashboard_after_login(): void
    {
        $user = User::factory()->create([
            'name' => 'Kafi',
            'username' => 'kafi',
            'email' => 'kafi@example.com',
            'password' => 'password',
        ]);

        $this->post(route('login.store'), [
            'username' => $user->username,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_open_dashboard_and_all_primary_navigation(): void
    {
        $user = User::factory()->create(['name' => 'Kafi']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Data Surat')
            ->assertSee('Surat Saya')
            ->assertSee('Ambil Surat')
            ->assertSee('Laporan')
            ->assertDontSee('<span>Monitoring Pegawai</span>', false)
            ->assertDontSee('<span>Riwayat / Aktivitas</span>', false);
    }
}
