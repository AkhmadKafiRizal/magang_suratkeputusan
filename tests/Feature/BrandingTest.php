<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_complete_siarka_identity(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>Masuk | SIARKA</title>', false)
            ->assertSee('SIARKA')
            ->assertSee('Sistem Informasi Arsip dan Register Keberatan')
            ->assertSee('Badan Pendapatan Daerah Kabupaten Jember')
            ->assertSee('Masuk ke SIARKA')
            ->assertSee('width: 8px;', false)
            ->assertSee('height: 3px;', false);
    }

    public function test_authenticated_layout_uses_siarka_in_tab_sidebar_header_and_footer(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<title>Dashboard | SIARKA</title>', false)
            ->assertSee('<strong>SIARKA</strong>', false)
            ->assertSee('Sistem Informasi Arsip dan Register Keberatan')
            ->assertDontSee('Bapenda Kabupaten Jember')
            ->assertSee('<span class="topbar-eyebrow">SIARKA</span>', false)
            ->assertSee('&copy; '.date('Y').' SIARKA', false)
            ->assertSee('Badan Pendapatan Daerah Kabupaten Jember');
    }

    public function test_application_and_mail_metadata_use_siarka(): void
    {
        $this->assertSame('SIARKA', config('app.name'));
        $this->assertSame('SIARKA Bapenda Jember', config('mail.from.name'));

        $mail = new PasswordResetOtpMail('123456');
        $this->assertSame('SIARKA Bapenda Jember', config('branding.mail_name'));
        $this->assertStringContainsString('SIARKA Bapenda Jember', $mail->envelope()->subject);
        $this->assertStringContainsString('Sistem Informasi Arsip dan Register Keberatan', $mail->render());
        $this->assertStringContainsString('Badan Pendapatan Daerah Kabupaten Jember', $mail->render());
    }

    public function test_error_page_offers_a_return_to_siarka_dashboard(): void
    {
        $this->view('errors.404')
            ->assertSee('<title>Halaman Tidak Ditemukan | SIARKA</title>', false)
            ->assertSee('Kembali ke Dashboard SIARKA')
            ->assertSee('Sistem Informasi Arsip dan Register Keberatan')
            ->assertSee('Badan Pendapatan Daerah Kabupaten Jember');
    }
}
