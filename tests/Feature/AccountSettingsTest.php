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

    public function test_profile_photo_preview_controls_are_present_without_uploading_any_file(): void
    {
        $user = User::factory()->create(['profile_photo_path' => null]);

        $this->actingAs($user)->get(route('account.edit'))
            ->assertOk()
            ->assertSee('data-profile-photo-form', false)
            ->assertSee('data-profile-photo-preview', false)
            ->assertSee('data-profile-photo-input', false)
            ->assertSee('data-profile-photo-save', false)
            ->assertSee('data-profile-photo-cancel', false)
            ->assertSee('data-profile-crop-modal', false)
            ->assertSee('data-profile-crop-canvas', false)
            ->assertSee('width="512" height="512"', false)
            ->assertSee('data-profile-crop-zoom', false)
            ->assertSee('Terapkan Crop')
            ->assertSee('Atur Crop Foto Profil')
            ->assertSee('Simpan Foto')
            ->assertSee('Batalkan Pilihan')
            ->assertDontSee('Hapus Foto Profil');

        $this->assertNull($user->fresh()->profile_photo_path);
        $javascript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString('URL.createObjectURL(file)', $javascript);
        $this->assertStringContainsString('URL.revokeObjectURL(previewUrl)', $javascript);
        $this->assertStringContainsString("cropCanvas.addEventListener('pointerdown'", $javascript);
        $this->assertStringContainsString("cropCanvas.addEventListener('pointermove'", $javascript);
        $this->assertStringContainsString('cropCanvas.toBlob', $javascript);
        $this->assertStringContainsString('const outputSize = 512', $javascript);
        $this->assertStringContainsString('new DataTransfer()', $javascript);
    }

    public function test_existing_photo_displays_clear_delete_action_and_confirmation_copy(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/pegawai.jpg']);
        Storage::disk('local')->put($user->profile_photo_path, 'photo-content');

        $this->actingAs($user)->get(route('account.edit'))
            ->assertOk()
            ->assertSee(route('account.photo'))
            ->assertSee('Hapus Foto Profil')
            ->assertSee('class="primary-button danger-button account-photo-delete-button"', false)
            ->assertSee('data-confirm-title="Hapus Foto Profil?"', false)
            ->assertSee('Apakah Anda yakin ingin menghapus foto profil? Foto akan diganti dengan avatar inisial nama Anda.')
            ->assertSee('data-confirm-label="Ya, Hapus Foto"', false)
            ->assertSee('data-confirm-variant="danger"', false);

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertMatchesRegularExpression('/\.danger-button\s*\{[^}]*background:\s*#b42318/s', $css);
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
        $other = User::factory()->create(['profile_photo_path' => 'profile-photos/other.jpg']);
        Storage::disk('local')->put($other->profile_photo_path, 'other-photo');
        $payload = ['name' => $user->name, 'username' => $user->username];
        $this->actingAs($user)->put(route('account.update'), $payload + ['photo' => UploadedFile::fake()->image('photo.jpg')])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Foto profil berhasil diperbarui.');
        $first = $user->fresh()->profile_photo_path;
        Storage::disk('local')->assertExists($first);
        $this->get(route('account.photo'))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->put(route('account.update'), $payload + ['photo' => UploadedFile::fake()->image('new.png')])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Foto profil berhasil diperbarui.');
        Storage::disk('local')->assertMissing($first);
        $second = $user->fresh()->profile_photo_path;
        $this->put(route('account.update'), $payload + ['name' => 'Nama yang tidak boleh diterapkan', 'remove_photo' => '1'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Foto profil berhasil dihapus.');
        Storage::disk('local')->assertMissing($second);
        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertSame($payload['name'], $user->fresh()->name);
        $this->assertSame('profile-photos/other.jpg', $other->fresh()->profile_photo_path);
        Storage::disk('local')->assertExists('profile-photos/other.jpg');
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('users', ['id' => $other->id]);
    }

    public function test_invalid_photo_is_rejected(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name, 'username' => $user->username,
            'photo' => UploadedFile::fake()->create('bad.php', 1, 'text/plain'),
        ])->assertSessionHasErrors('photo')->assertSessionMissing('success');
        $this->assertNull($user->fresh()->profile_photo_path);
    }

    public function test_cropped_512_pixel_photo_is_accepted_by_existing_upload_endpoint(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('account.update'), [
            'name' => $user->name,
            'username' => $user->username,
            'photo' => UploadedFile::fake()->image('foto-profil-crop.jpg', 512, 512),
        ])->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Foto profil berhasil diperbarui.');

        $storedPhoto = $user->fresh()->profile_photo_path;
        $this->assertNotNull($storedPhoto);
        Storage::disk('local')->assertExists($storedPhoto);
    }

    public function test_delete_photo_does_not_claim_success_when_photo_is_missing_or_path_is_unmanaged(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['profile_photo_path' => null]);
        $payload = ['name' => $user->name, 'username' => $user->username, 'remove_photo' => '1'];

        $this->actingAs($user)->put(route('account.update'), $payload)
            ->assertSessionHas('warning', 'Foto profil belum tersedia untuk dihapus.')
            ->assertSessionMissing('success');

        Storage::disk('local')->put('private-document.txt', 'must-not-be-deleted');
        $user->forceFill(['profile_photo_path' => 'private-document.txt'])->saveOrFail();

        $this->put(route('account.update'), $payload)
            ->assertSessionHas('error', 'Foto profil belum dapat dihapus. Silakan coba lagi.')
            ->assertSessionMissing('success');

        $this->assertSame('private-document.txt', $user->fresh()->profile_photo_path);
        Storage::disk('local')->assertExists('private-document.txt');
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
