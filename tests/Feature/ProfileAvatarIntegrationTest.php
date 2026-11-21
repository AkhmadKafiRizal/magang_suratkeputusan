<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAvatarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_another_employees_private_profile_photo(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create();
        $actor = User::factory()->create(['profile_photo_path' => 'profile-photos/actor.jpg']);
        Storage::disk('local')->put($actor->profile_photo_path, 'actor-photo-content');

        $response = $this->actingAs($viewer)
            ->get(route('users.photo', $actor))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame('actor-photo-content', $response->streamedContent());
    }

    public function test_profile_photo_endpoint_requires_login_and_missing_file_returns_not_found(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create(['profile_photo_path' => 'profile-photos/missing.jpg']);

        $this->get(route('users.photo', $actor))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())
            ->get(route('users.photo', $actor))
            ->assertNotFound();
    }

    public function test_surat_detail_uses_the_handler_photo_not_the_logged_in_users_photo(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create([
            'name' => 'Viewer Berbeda',
            'profile_photo_path' => 'profile-photos/viewer.jpg',
        ]);
        $handler = User::factory()->create([
            'name' => 'Kak Faisol',
            'profile_photo_path' => 'profile-photos/faisol.jpg',
        ]);
        Storage::disk('local')->put($viewer->profile_photo_path, 'viewer-photo');
        Storage::disk('local')->put($handler->profile_photo_path, 'handler-photo');
        $surat = Surat::factory()->create(['pegawai_id' => $handler->id]);

        $response = $this->actingAs($viewer)
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('Pegawai yang Menangani')
            ->assertSee('Kak Faisol')
            ->assertSee('Penanggung Jawab Surat')
            ->assertSee(route('users.photo', $handler), false)
            ->assertDontSee(route('users.photo', $viewer), false)
            ->assertSee('user-avatar-medium', false);

        $this->assertTrue($response->viewData('surat')->relationLoaded('pegawai'));
        $this->assertSame('profile-photos/faisol.jpg', $response->viewData('surat')->pegawai->profile_photo_path);
    }

    public function test_handler_without_photo_uses_initials_and_unassigned_surat_stays_unassigned(): void
    {
        $viewer = User::factory()->create();
        $handler = User::factory()->create(['name' => 'Rina Fajar', 'profile_photo_path' => null]);
        $assigned = Surat::factory()->create(['pegawai_id' => $handler->id]);
        $unassigned = Surat::factory()->create(['pegawai_id' => null]);

        $this->actingAs($viewer)
            ->get(route('surat.show', $assigned))
            ->assertOk()
            ->assertSee('user-avatar-fallback', false)
            ->assertSee('>RF</span>', false)
            ->assertDontSee(route('users.photo', $handler), false);

        $this->actingAs($viewer)
            ->get(route('surat.show', $unassigned))
            ->assertOk()
            ->assertSee('Belum Diambil')
            ->assertDontSee('detail-handler-identity', false);
    }

    public function test_missing_photo_file_has_initial_fallback_markup(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create();
        $handler = User::factory()->create([
            'name' => 'Dian Putri',
            'profile_photo_path' => 'profile-photos/not-on-disk.jpg',
        ]);
        $surat = Surat::factory()->create(['pegawai_id' => $handler->id]);

        $this->actingAs($viewer)
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('data-user-avatar-image', false)
            ->assertSee('data-user-avatar-fallback', false)
            ->assertSee('>DP</span>', false);
    }

    public function test_activity_history_uses_each_actor_avatar_and_safe_system_fallback(): void
    {
        Storage::fake('local');
        $viewer = User::factory()->create();
        $withPhoto = User::factory()->create([
            'name' => 'Aktor Foto',
            'profile_photo_path' => 'profile-photos/aktor.jpg',
        ]);
        $withInitials = User::factory()->create(['name' => 'Aktor Inisial']);
        Storage::disk('local')->put($withPhoto->profile_photo_path, 'actor-photo');
        $surat = Surat::factory()->create();

        $this->activity($surat, $withPhoto, 'Aktivitas oleh aktor foto.', '2026-11-22 09:02:00');
        $this->activity($surat, $withInitials, 'Aktivitas oleh aktor inisial.', '2026-11-22 09:01:00');
        $this->activity($surat, null, 'Aktivitas sistem tanpa pelaku.', '2026-11-22 09:00:00');

        $response = $this->actingAs($viewer)
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk()
            ->assertSee(route('users.photo', $withPhoto), false)
            ->assertSee('>AI</span>', false)
            ->assertSee('user-avatar-system', false)
            ->assertSee('Pengguna tidak tersedia')
            ->assertSee('Aktivitas oleh aktor foto.')
            ->assertSee('Aktivitas oleh aktor inisial.')
            ->assertSee('Aktivitas sistem tanpa pelaku.')
            ->assertSee('22 Nov 2026, 09:02 WIB');

        foreach ($response->viewData('aktivitas') as $activity) {
            $this->assertTrue($activity->relationLoaded('actor'));
        }
    }

    public function test_deleted_actor_keeps_historical_description_and_uses_neutral_fallback(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Pegawai Lama']);
        $surat = Surat::factory()->create();
        $activity = $this->activity($surat, $actor, 'Pegawai Lama menyelesaikan surat historis.');
        $actor->delete();

        $this->assertNull($activity->fresh()->user_id);

        $this->actingAs($viewer)
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk()
            ->assertSee('Pegawai Lama menyelesaikan surat historis.')
            ->assertSee('user-avatar-system', false)
            ->assertSee('Pengguna tidak tersedia');
    }

    public function test_surat_history_summary_eager_loads_actor_profile_data(): void
    {
        $viewer = User::factory()->create();
        $actor = User::factory()->create([
            'name' => 'Petugas Riwayat',
            'profile_photo_path' => 'profile-photos/riwayat.jpg',
        ]);
        $surat = Surat::factory()->create();
        $this->activity($surat, $actor, 'Petugas Riwayat mulai memproses surat.');

        $response = $this->actingAs($viewer)
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee(route('users.photo', $actor), false)
            ->assertSee('Petugas Riwayat mulai memproses surat.')
            ->assertSee('loading="lazy"', false);

        $activity = $response->viewData('aktivitasTerbaru')->first();
        $this->assertTrue($activity->relationLoaded('actor'));
        $this->assertSame('profile-photos/riwayat.jpg', $activity->actor->profile_photo_path);
    }

    public function test_activity_page_does_not_query_each_actor_separately(): void
    {
        $viewer = User::factory()->create();
        $surat = Surat::factory()->create();

        foreach (range(1, 8) as $index) {
            $actor = User::factory()->create(['name' => "Aktor {$index}"]);
            $this->activity($surat, $actor, "Aktivitas {$index}.", "2026-11-22 08:0{$index}:00");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($viewer)
            ->get(route('riwayat-aktivitas.index'))
            ->assertOk();

        $userQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => preg_match('/from [`"]?users[`"]?/i', $query) === 1)
            ->count();

        $this->assertLessThanOrEqual(2, $userQueries);
    }

    private function activity(
        Surat $surat,
        ?User $actor,
        string $description,
        string $time = '2026-11-22 09:00:00'
    ): AktivitasSurat {
        $activity = new AktivitasSurat([
            'user_id' => $actor?->id,
            'tipe' => AktivitasSurat::TIPE_SURAT_DIEDIT,
            'deskripsi' => $description,
        ]);
        $activity->created_at = Carbon::parse($time);
        $activity->updated_at = Carbon::parse($time);
        $surat->aktivitas()->save($activity);

        return $activity;
    }
}
