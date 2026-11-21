<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class UsernameLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_normalized_username_and_password(): void
    {
        $user = User::factory()->create([
            'username' => 'kafi',
            'password' => 'rahasia',
        ]);

        $response = $this->post(route('login.store'), [
            'username' => '  KAFI  ',
            'password' => 'rahasia',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_username_is_rejected_with_generic_message(): void
    {
        User::factory()->create(['username' => 'kafi', 'password' => 'rahasia']);

        $this->from(route('login'))->post(route('login.store'), [
            'username' => 'tidakada',
            'password' => 'rahasia',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'Username/email atau kata sandi tidak sesuai.']);

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected_and_password_is_not_flashed(): void
    {
        User::factory()->create(['username' => 'kafi', 'password' => 'rahasia']);

        $this->from(route('login'))->post(route('login.store'), [
            'username' => 'kafi',
            'password' => 'keliru',
        ])->assertRedirect(route('login'))
            ->assertSessionHasInput('username', 'kafi')
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_email_can_be_used_to_login(): void
    {
        $user = User::factory()->create([
            'username' => 'kafi',
            'email' => 'kafi@example.com',
            'password' => 'rahasia',
        ]);

        $this->post(route('login.store'), [
            'username' => $user->email,
            'password' => 'rahasia',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_me_sets_the_recaller_cookie(): void
    {
        $user = User::factory()->create(['username' => 'kafi', 'password' => 'rahasia']);

        $this->post(route('login.store'), [
            'username' => 'kafi',
            'password' => 'rahasia',
            'remember' => '1',
        ])->assertCookie(Auth::guard()->getRecallerName());

        $this->assertAuthenticatedAs($user);
    }

    public function test_remember_cookie_restores_session_and_logout_revokes_it(): void
    {
        $user = User::factory()->create(['password' => 'rahasia']);
        $name = Auth::guard()->getRecallerName();
        $response = $this->post(route('login.store'), [
            'username' => $user->username, 'password' => 'rahasia', 'remember' => '1',
        ]);
        $cookie = $response->getCookie($name);
        $this->assertGreaterThan(time(), $cookie->getExpiresTime());
        $this->app['session']->flush();
        Auth::forgetGuards();
        $this->withCookie($name, $cookie->getValue())->get(route('dashboard'))->assertOk();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Auth::viaRemember());
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->app['session']->flush();
        Auth::forgetGuards();
        $this->withCookie($name, $cookie->getValue())->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_without_remember_cookie_session_loss_requires_login(): void
    {
        $user = User::factory()->create(['password' => 'rahasia']);
        $this->post(route('login.store'), [
            'username' => $user->username, 'password' => 'rahasia',
        ])->assertCookieMissing(Auth::guard()->getRecallerName());
        $this->app['session']->flush();
        Auth::forgetGuards();
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_successful_login_regenerates_the_session(): void
    {
        User::factory()->create(['username' => 'kafi', 'password' => 'rahasia']);
        $this->startSession();
        $oldSessionId = session()->getId();

        $this->post(route('login.store'), [
            'username' => 'kafi',
            'password' => 'rahasia',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotSame($oldSessionId, session()->getId());
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['username' => 'kafi', 'password' => 'rahasia']);
        $key = 'kafi|127.0.0.1';
        RateLimiter::clear($key);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'username' => 'kafi',
                'password' => 'keliru',
            ])->assertSessionHasErrors('username');
        }

        $response = $this->post(route('login.store'), [
            'username' => 'kafi',
            'password' => 'keliru',
        ])->assertSessionHasErrors('username');

        $this->assertStringStartsWith(
            'Terlalu banyak percobaan login.',
            $response->getSession()->get('errors')->first('username')
        );
    }

    public function test_factory_generates_unique_usernames(): void
    {
        $users = User::factory()->count(10)->create();

        $this->assertCount(10, $users->pluck('username')->unique());
    }

    public function test_username_has_a_database_unique_constraint(): void
    {
        User::factory()->create(['username' => 'kafi']);

        $this->expectException(QueryException::class);
        User::factory()->create(['username' => 'kafi']);
    }
}
