<?php

namespace Tests\Feature;

use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiUxPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_places_ambil_surat_before_surat_saya_without_changing_routes_or_active_state(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $html = $response->getContent();

        $ambilPosition = strpos($html, '<span>Ambil Surat</span>');
        $suratSayaPosition = strpos($html, '<span>Surat Saya</span>');

        $this->assertNotFalse($ambilPosition);
        $this->assertNotFalse($suratSayaPosition);
        $this->assertLessThan($suratSayaPosition, $ambilPosition);
        $response->assertSee('href="'.route('surat-tersedia.index').'"', false);
        $response->assertSee('href="'.route('surat-saya.index').'"', false);

        $ambilActiveHtml = $this->actingAs($user)
            ->get(route('surat-tersedia.index'))
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression(
            '/<a[^>]*class="sidebar-link is-active"[^>]*href="'.preg_quote(route('surat-tersedia.index'), '/').'"[^>]*aria-current="page"/s',
            $ambilActiveHtml
        );

        $suratSayaActiveHtml = $this->actingAs($user)
            ->get(route('surat-saya.index'))
            ->assertOk()
            ->getContent();
        $this->assertMatchesRegularExpression(
            '/<a[^>]*class="sidebar-link is-active"[^>]*href="'.preg_quote(route('surat-saya.index'), '/').'"[^>]*aria-current="page"/s',
            $suratSayaActiveHtml
        );
    }

    public function test_account_settings_displays_accessible_developer_whatsapp_contact_below_recovery(): void
    {
        $message = 'Halo Dev Kafi, saya ingin menyampaikan kritik atau saran terkait aplikasi SIARKA.';
        $whatsappUrl = 'https://wa.me/6289654265231?text='.rawurlencode($message);
        $response = $this->actingAs(User::factory()->create())
            ->get(route('account.edit'))
            ->assertOk()
            ->assertSee('Punya Kritik, Saran, atau Masukan?')
            ->assertSee('Dev Kafi')
            ->assertSee('089654265231')
            ->assertSee('Hubungi via WhatsApp')
            ->assertSee('href="'.$whatsappUrl.'"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer"', false)
            ->assertSee('aria-label="Hubungi Dev Kafi melalui WhatsApp"', false);

        $html = $response->getContent();
        $this->assertLessThan(
            strpos($html, 'developer-contact-panel'),
            strpos($html, 'account-recovery-panel')
        );
    }

    public function test_dashboard_uses_scoped_proportional_action_column_without_changing_data_surat_table(): void
    {
        Surat::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('class="letters-table dashboard-letters-table"', false)
            ->assertSee('class="dashboard-letter-action-column"', false)
            ->assertSee('class="table-actions dashboard-letter-actions"', false)
            ->assertSee('Lihat Detail')
            ->assertSee('class="table-delete-action"', false)
            ->assertSee('data-confirm-title="Konfirmasi Hapus Surat"', false);

        $this->actingAs($user)
            ->get(route('surat.index'))
            ->assertOk()
            ->assertDontSee('dashboard-letters-table', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.dashboard-letters-table\s*\{[^}]*min-width:\s*1180px;[^}]*table-layout:\s*fixed;/s', $css);
        $this->assertMatchesRegularExpression('/\.dashboard-letters-table \.dashboard-letter-applicant-column\s*\{[^}]*width:\s*25%;/s', $css);
        $this->assertMatchesRegularExpression('/\.dashboard-letters-table \.dashboard-letter-status-column\s*\{[^}]*width:\s*19%;/s', $css);
        $this->assertStringContainsString('.dashboard-letters-table .dashboard-letter-action-column', $css);
        $this->assertStringContainsString('.dashboard-letters-table th:last-child', $css);
        $this->assertMatchesRegularExpression('/\.dashboard-letters-table \.dashboard-letter-action-column\s*\{[^}]*width:\s*17%;/s', $css);
        $this->assertStringNotContainsString('width: 34%;', $css);
        $this->assertStringNotContainsString('width: 1%;', $css);
        $this->assertStringContainsString('.dashboard-letter-actions {', $css);
        $this->assertStringContainsString('gap: 12px;', $css);
    }
}
