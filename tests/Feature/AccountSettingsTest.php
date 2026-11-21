<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_recovery_sends_to_own_email_and_resets_with_otp(): void
    {
        Mail::fake();
        config(['mail.default' => 'array', 'cache.default' => 'array']);
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->post(route('account.recovery'), ['identity' => $other->email])
            ->assertRedirect(route('password.otp.create'));
        $code = '';
        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use ($user, &$code) {
            $code = $mail->otp;

            return $mail->hasTo($user->email);
        });
        $this->get(route('password.otp.create'))->assertOk()->assertSee('Kembali ke Pengaturan Akun');
        $this->get(route('password.reset.create'))->assertRedirect(route('password.request'));
        $this->post(route('password.otp.verify'), ['otp' => $code])->assertRedirect(route('password.reset.create'));
        $this->get(route('password.reset.create'))->assertOk();
        $this->post(route('password.reset.store'), ['password' => 'recovered-password', 'password_confirmation' => 'recovered-password'])
            ->assertRedirect(route('account.edit'))->assertSessionHas('success');
        $this->assertTrue(Hash::check('recovered-password', $user->fresh()->password));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
        $this->assertNotNull(PasswordResetOtp::firstOrFail()->consumed_at);
        $this->assertAuthenticatedAs($user);
        $this->post(route('logout'));
        Auth::forgetGuards();
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'recovered-password'])->assertRedirect(route('dashboard'));
    }

    public function test_account_recovery_rejects_another_users_verified_otp(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $otp = PasswordResetOtp::create(['user_id' => $other->id, 'otp_hash' => Hash::make('123456'), 'expires_at' => now()->addMinutes(10), 'verified_at' => now()]);
        $this->actingAs($user)->withSession(['password_reset' => ['otp_id' => $otp->id, 'verified_until' => now()->addMinutes(10)->timestamp]])
            ->post(route('password.reset.store'), ['password' => 'recovered-password', 'password_confirmation' => 'recovered-password'])
            ->assertRedirect(route('password.request'));
        $this->assertTrue(Hash::check('password', $other->fresh()->password));
    }

    public function test_account_recovery_is_rate_limited_and_mail_failure_preserves_login(): void
    {
        Mail::fake();
        config(['mail.default' => 'array', 'cache.default' => 'array']);
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('account.recovery'))->assertRedirect(route('password.otp.create'));
        $this->from(route('account.edit'))->post(route('account.recovery'))->assertSessionHas('status');
        Mail::assertSentCount(1);
        $this->assertAuthenticatedAs($user);
        $this->travel(11)->seconds();
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Test SMTP unavailable'));
        $this->post(route('account.recovery'))->assertRedirect(route('password.request'))->assertSessionHasErrors('identity');
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_guest_cannot_access_settings_or_photo(): void
    {
        $this->get(route('account.edit'))->assertRedirect(route('login'));
        $this->put(route('account.update'), [])->assertRedirect(route('login'));
        $this->get(route('account.photo'))->assertRedirect(route('login'));
    }

    public function test_settings_displays_actual_username(): void
    {
        $user = User::factory()->create(['name' => 'Nama Berbeda', 'username' => 'username.asli']);
        $this->actingAs($user)->get(route('account.edit'))
            ->assertOk()
            ->assertSee('username.asli')
            ->assertSee('Foto Profil')
            ->assertSee('<h1>Pengaturan Akun</h1>', false)
            ->assertDontSee('<h2>Pengaturan Akun</h2>', false)
            ->assertSee('<span class="topbar-eyebrow">SIARKA</span>', false)
            ->assertSee('Informasi akun')
            ->assertSee('Ubah kata sandi');
    }

    public function test_updated_username_can_login_with_existing_password(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $other = User::factory()->create();
        $this->actingAs($user)->put(route('account.update'), [
            'name' => 'Nama Baru', 'username' => '  USER.BARU ', 'current_password' => 'correct-password',
            'id' => $other->id, 'role' => 'admin', 'email' => 'attacker@example.com',
        ])->assertSessionHasNoErrors()->assertRedirect(route('account.edit'));
        $this->assertSame('user.baru', $user->fresh()->username);
        $this->assertSame($other->username, $other->fresh()->username);
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertSame($user->role, $user->fresh()->role);
        $this->post(route('logout'));
        Auth::forgetGuards();
        $this->post(route('login.store'), ['username' => 'USER.BARU', 'password' => 'correct-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_username_change_requires_password_and_rejects_duplicate(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($user)->put(route('account.update'), ['name' => $user->name, 'username' => 'newuser'])->assertSessionHasErrors('current_password');
        $this->put(route('account.update'), ['name' => $user->name, 'username' => $other->username, 'current_password' => 'password'])->assertSessionHasErrors('username');
    }

    public function test_photo_upload_display_replace_and_removal(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $payload = ['name' => $user->name, 'username' => $user->username];
        $this->actingAs($user)->put(route('account.update'), $payload + ['photo' => UploadedFile::fake()->image('photo.jpg')])->assertSessionHasNoErrors();
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($first);
        $this->get(route('account.photo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put(route('account.update'), $payload + ['photo' => UploadedFile::fake()->image('new.png')])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($first);
        $second = $user->fresh()->profile_photo_path;
        $this->put(route('account.update'), $payload + ['remove_photo' => '1'])->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($second);
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_invalid_photo_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name, 'username' => $user->username,
            'photo' => UploadedFile::fake()->create('bad.php', 1, 'text/plain'),
        ])->assertSessionHasErrors('photo');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_password_change_requires_current_password_and_accepts_new_login(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('account.password'), [
            'current_password' => 'wrong', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasErrorsIn('passwordChange', 'current_password');
        $this->put(route('account.password'), [
            'current_password' => 'password', 'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('account.edit'));
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->post(route('logout'));
        Auth::forgetGuards();
        $this->post(route('login.store'), ['username' => $user->username, 'password' => 'new-password'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
