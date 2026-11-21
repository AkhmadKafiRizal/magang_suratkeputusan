<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Tests\TestCase;

class ForgotPasswordOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['mail.default' => 'array', 'cache.default' => 'array']);
        Mail::fake();
        RateLimiter::clear($this->rateKey('user@example.com'));
    }

    public function test_login_displays_forgot_password_link(): void
    {
        $this->get(route('login'))->assertSee('Lupa kata sandi?');
    }

    public function test_forgot_password_link_is_not_mailto(): void
    {
        $this->get(route('login'))->assertDontSee('mailto:', false);
    }

    public function test_forgot_password_link_targets_laravel_route(): void
    {
        $this->get(route('login'))->assertSee('href="'.route('password.request').'"', false);
    }

    public function test_guest_can_open_request_page(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Lupa Kata Sandi');
    }

    public function test_request_page_advises_email_fallback_and_developer_contact(): void
    {
        $this->get(route('password.request'))->assertSee('Email Terdaftar')->assertDontSee('username')->assertSee('hubungi developer');
    }

    public function test_registered_email_creates_otp_and_sends_mail(): void
    {
        $u = $this->user();
        $this->requestOtp($u);
        $this->assertDatabaseHas('password_reset_otps', ['user_id' => $u->id]);
        Mail::assertSent(PasswordResetOtpMail::class);
    }

    public function test_registered_username_is_rejected_without_sending_otp(): void
    {
        $u = $this->user();
        $this->post(route('password.email'), ['identity' => $u->username])->assertSessionHasErrors('identity');
        Mail::assertNothingSent();
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_email_shaped_username_cannot_select_a_different_account(): void
    {
        $u = $this->user();
        DB::table('users')->where('id', $u->id)->update(['username' => 'different@example.com']);
        $this->post(route('password.email'), ['identity' => 'different@example.com'])->assertRedirect(route('password.otp.create'));
        Mail::assertNothingSent();
    }

    public function test_email_sends_otp_to_matching_account(): void
    {
        $u = $this->user();
        $this->requestOtp($u, $u->email);
        Mail::assertSent(PasswordResetOtpMail::class, fn ($mail) => $mail->hasTo($u->email));
    }

    public function test_unknown_identity_gets_generic_response(): void
    {
        $this->post(route('password.email'), ['identity' => 'none@example.com'])->assertRedirect(route('password.otp.create'))->assertSessionHas('status', 'Jika email tersebut terdaftar, kode verifikasi akan dikirim ke email yang terkait dengan akun.');
    }

    public function test_recovery_pages_do_not_open_email_applications(): void
    {
        $this->get(route('password.request'))->assertDontSee('mailto:', false)->assertDontSee('outlook', false)->assertDontSee('gmail.com', false);
    }

    public function test_otp_field_has_numeric_input_attributes(): void
    {
        $this->get(route('password.otp.create'))->assertSee('inputmode="numeric"', false)->assertSee('maxlength="6"', false)->assertSee('pattern="[0-9]{6}"', false)->assertSee('autocomplete="one-time-code"', false);
    }

    public function test_otp_field_has_digit_sanitizer(): void
    {
        $this->get(route('password.otp.create'))->assertSee('data-digits-only', false)->assertSee("replace(/\\D/g,'').slice(0,6)", false);
    }

    public function test_server_rejects_otp_containing_letters(): void
    {
        $this->post(route('password.otp.verify'), ['otp' => 'ABC123'])->assertSessionHasErrors('otp');
    }

    public function test_server_rejects_otp_shorter_than_six_digits(): void
    {
        $this->post(route('password.otp.verify'), ['otp' => '12345'])->assertSessionHasErrors('otp');
    }

    public function test_server_rejects_otp_longer_than_six_digits(): void
    {
        $this->post(route('password.otp.verify'), ['otp' => '1234567'])->assertSessionHasErrors('otp');
    }

    public function test_leading_zero_otp_is_valid_format(): void
    {
        $validator = Validator::make(['otp' => '012345'], ['otp' => ['required', 'digits:6']]);
        $this->assertFalse($validator->fails());
    }

    public function test_log_mailer_never_sends_otp_to_log_transport(): void
    {
        config(['mail.default' => 'log']);
        $u = $this->user();
        $response = $this->post(route('password.email'), ['identity' => $u->email]);
        $response->assertSessionHas('status', 'Layanan pengiriman email belum tersedia. Tidak ada kode verifikasi yang dikirim.');
        Mail::assertNothingSent();
        $this->assertDoesNotMatchRegularExpression('/^\d{6}$/', PasswordResetOtp::firstOrFail()->otp_hash);
    }

    public function test_mail_failure_is_reported_instead_of_claiming_otp_was_sent(): void
    {
        $u = $this->user();
        Mail::shouldReceive('to')->once()->with($u->email)->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('password.email'), ['identity' => $u->email])->assertRedirect(route('password.request'))->assertSessionHasErrors(['identity' => 'Kode verifikasi belum dapat dikirim karena layanan email sedang bermasalah. Silakan coba lagi.'])->assertSessionHasInput('identity', $u->email);
        $this->assertNotNull(PasswordResetOtp::firstOrFail()->consumed_at);
    }

    public function test_mail_failure_does_not_lock_the_user_out_of_retrying(): void
    {
        $u = $this->user();
        $mailFake = Mail::getFacadeRoot();
        Mail::shouldReceive('to')->once()->with($u->email)->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('password.email'), ['identity' => $u->email]);
        Mail::swap($mailFake);
        $this->post(route('password.email'), ['identity' => $u->email])->assertRedirect(route('password.otp.create'));
        Mail::assertSent(PasswordResetOtpMail::class);
    }

    public function test_generated_otp_has_exactly_six_digits(): void
    {
        $u = $this->user();
        $this->requestOtp($u);
        Mail::assertSent(PasswordResetOtpMail::class, fn ($m) => preg_match('/^\d{6}$/', $m->otp) === 1);
    }

    public function test_recovery_pages_only_offer_email(): void
    {
        foreach (['password.request', 'password.otp.create'] as $route) {
            $this->get(route($route))->assertOk()->assertDontSee('username');
        }
    }

    public function test_otp_email_keeps_brand_images_centered_without_named_attachments(): void
    {
        $mailer = Mail::getFacadeRoot()->manager->mailer('array');
        $sent = (new PasswordResetOtpMail('123456'))->to('recipient@example.com')->send($mailer);
        $this->assertNotNull($sent);
        $raw = $sent->getSymfonySentMessage()->getOriginalMessage()->toString();
        $this->assertSame(3, substr_count($raw, 'Content-Disposition: inline'));
        $this->assertStringNotContainsString('filename=', $raw);
        $this->assertStringContainsString('Content-Type: image/png', $raw);
        $this->assertStringContainsString('Content-Type: image/webp', $raw);
        $html = (new PasswordResetOtpMail('123456'))->render();
        $this->assertSame(3, substr_count($html, '<img'));
        $this->assertStringContainsString('align="center"', $html);
    }

    public function test_database_stores_hash_not_plain_otp(): void
    {
        $u = $this->user();
        $this->requestOtp($u);
        $plain = $this->sentCode();
        $row = PasswordResetOtp::first();
        $this->assertNotSame($plain, $row->otp_hash);
        $this->assertTrue(Hash::check($plain, $row->otp_hash));
    }

    public function test_valid_otp_can_be_verified(): void
    {
        [$u,$code] = $this->flow();
        $this->post(route('password.otp.verify'), ['otp' => $code])->assertRedirect(route('password.reset.create'));
        $this->assertNotNull(PasswordResetOtp::first()->verified_at);
    }

    public function test_wrong_otp_is_rejected(): void
    {
        [$u,$code] = $this->flow();
        $this->post(route('password.otp.verify'), ['otp' => '999999'])->assertSessionHasErrors('otp');
    }

    public function test_expired_otp_is_rejected(): void
    {
        [$u,$code] = $this->flow();
        PasswordResetOtp::first()->update(['expires_at' => now()->subSecond()]);
        $this->post(route('password.otp.verify'), ['otp' => $code])->assertSessionHasErrors(['otp' => 'Kode verifikasi sudah kedaluwarsa. Silakan minta kode baru.']);
    }

    public function test_consumed_otp_is_rejected(): void
    {
        [$u,$code] = $this->flow();
        PasswordResetOtp::first()->update(['consumed_at' => now()]);
        $this->post(route('password.otp.verify'), ['otp' => $code])->assertSessionHasErrors('otp');
    }

    public function test_resend_invalidates_previous_otp(): void
    {
        [$u,$code] = $this->flow();
        RateLimiter::clear($this->rateKey($u->email));
        $old = PasswordResetOtp::first();
        $this->post(route('password.otp.resend'));
        $this->assertNotNull($old->fresh()->consumed_at);
        $this->assertSame(2, PasswordResetOtp::count());
    }

    public function test_resend_still_works_after_previous_otp_expires(): void
    {
        [$u,$code] = $this->flow();
        $old = PasswordResetOtp::first();
        $this->travel(11)->minutes();
        $this->post(route('password.otp.resend'))->assertRedirect();
        $this->assertNotNull($old->fresh()->consumed_at);
        $this->assertSame(2, PasswordResetOtp::count());
    }

    public function test_failed_resend_keeps_previous_otp_usable(): void
    {
        [$u,$code] = $this->flow();
        RateLimiter::clear($this->rateKey($u->email));
        $old = PasswordResetOtp::firstOrFail();
        Mail::shouldReceive('to')->once()->with($u->email)->andThrow(new RuntimeException('SMTP unavailable'));
        $this->post(route('password.otp.resend'))->assertSessionHasErrors('otp');
        $this->assertNull($old->fresh()->consumed_at);
        $this->assertNotNull(PasswordResetOtp::latest('id')->firstOrFail()->consumed_at);
        $this->post(route('password.otp.verify'), ['otp' => $code])->assertRedirect(route('password.reset.create'));
    }

    public function test_request_is_rate_limited_during_cooldown(): void
    {
        $u = $this->user();
        $this->requestOtp($u);
        $this->post(route('password.email'), ['identity' => $u->email])->assertSessionHas('status', 'Permintaan terlalu cepat. Silakan tunggu sebelum mencoba kembali.');
        $this->assertSame(1, PasswordResetOtp::count());
    }

    public function test_five_wrong_attempts_invalidate_otp(): void
    {
        [$u,$code] = $this->flow();
        foreach (range(1, 5) as $_) {
            $this->post(route('password.otp.verify'), ['otp' => '999999']);
        } $this->assertNotNull(PasswordResetOtp::first()->consumed_at);
    }

    public function test_reset_page_is_blocked_before_verification(): void
    {
        $this->get(route('password.reset.create'))->assertRedirect(route('password.request'));
    }

    public function test_valid_otp_grants_temporary_reset_access(): void
    {
        [$u,$code] = $this->flow();
        $this->post(route('password.otp.verify'), ['otp' => $code]);
        $this->get(route('password.reset.create'))->assertOk();
    }

    public function test_password_confirmation_must_match(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->post(route('password.reset.store'), ['password' => 'new-password', 'password_confirmation' => 'different'])->assertSessionHasErrors('password');
    }

    public function test_new_password_is_stored_hashed(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword();
        $this->assertTrue(Hash::check('new-password', $u->fresh()->password));
        $this->assertNotSame('new-password', $u->fresh()->password);
    }

    public function test_mismatched_confirmation_can_be_corrected_without_another_otp(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->post(route('password.reset.store'), [
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors([
            'password' => 'Konfirmasi kata sandi belum sama. Isi kedua kolom dengan kata sandi yang persis sama, termasuk huruf besar, huruf kecil, dan spasi.',
        ]);
        $this->assertNull(PasswordResetOtp::firstOrFail()->consumed_at);
        $this->get(route('password.reset.create'))->assertOk();
        $this->resetPassword();
        $this->assertTrue(Hash::check('new-password', $u->fresh()->password));
        $this->assertNotSame('new-password', $u->fresh()->password);
    }

    public function test_otp_is_invalid_after_password_changes(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword();
        $this->assertNotNull(PasswordResetOtp::first()->fresh()->consumed_at);
    }

    public function test_recovery_session_is_cleared_after_success(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword()->assertSessionMissing('password_reset');
    }

    public function test_user_can_login_with_username_and_new_password(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword();
        $this->post(route('login.store'), ['username' => $u->username, 'password' => 'new-password'])->assertRedirect(route('dashboard'));
    }

    public function test_old_password_no_longer_works(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword();
        $this->post(route('login.store'), ['username' => $u->username, 'password' => 'old-password']);
        $this->assertGuest();
    }

    public function test_user_can_login_with_email_and_new_password(): void
    {
        [$u,$code] = $this->verifiedFlow();
        $this->resetPassword();
        $this->post(route('login.store'), ['username' => $u->email, 'password' => 'new-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($u);
    }

    public function test_email_reset_allows_login_with_legacy_username(): void
    {
        $u = $this->user();
        DB::table('users')->where('id', $u->id)->update(['username' => '  RecoveryUser  ']);

        $this->requestOtp($u, $u->email);
        $code = $this->sentCode();
        $this->post(route('password.otp.verify'), ['otp' => $code]);
        $this->resetPassword();

        $this->post(route('login.store'), [
            'username' => ' recoveryuser ',
            'password' => 'new-password',
        ])->assertRedirect(route('dashboard'));
    }

    private function user(): User
    {
        return User::factory()->create(['username' => 'recoveryuser', 'email' => 'user@example.com', 'password' => 'old-password']);
    }

    private function requestOtp(User $user, ?string $identity = null): void
    {
        $this->post(route('password.email'), ['identity' => $identity ?? $user->email]);
    }

    private function sentCode(): string
    {
        $code = '';
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use (&$code) {
            $code = $mail->otp;

            return true;
        });

        return $code;
    }

    private function flow(): array
    {
        $u = $this->user();
        $this->requestOtp($u);

        return [$u, $this->sentCode()];
    }

    private function verifiedFlow(): array
    {
        [$u,$code] = $this->flow();
        $this->post(route('password.otp.verify'), ['otp' => $code]);

        return [$u, $code];
    }

    private function resetPassword()
    {
        return $this->post(route('password.reset.store'), ['password' => 'new-password', 'password_confirmation' => 'new-password']);
    }

    private function rateKey(string $email): string
    {
        $key = hash('sha256', strtolower($email).'|127.0.0.1');

        return "password-reset:cooldown:$key";
    }
}
