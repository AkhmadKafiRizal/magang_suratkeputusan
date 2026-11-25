<?php

namespace Tests\Feature;

use App\Models\AktivitasSurat;
use App\Models\KategoriSurat;
use App\Models\Surat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratInputEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_nomor_surat_uses_standard_browser_autocomplete_without_custom_suggestions(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('id="nomor_surat" name="nomor_surat" type="text"', false)
            ->assertSee('autocomplete="on"', false)
            ->assertDontSee('<datalist', false)
            ->assertDontSee('list="', false);

        $this->assertMatchesRegularExpression(
            '/<input id="nomor_surat"[^>]*autocomplete="on"[^>]*>/',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input id="nomor_surat"[^>]*autocomplete="off"[^>]*>/',
            $response->getContent()
        );
    }

    public function test_nomor_surat_preserves_dots_and_leading_zeroes_as_a_string(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '2026.0060.010',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('surats', ['nomor_surat' => '2026.0060.010']);
        $this->assertSame('2026.0060.010', Surat::query()->value('nomor_surat'));
    }

    public function test_frontend_validation_hooks_preserve_autocomplete_and_handle_input_without_silent_rewriting(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('autocomplete="on" inputmode="decimal"', false)
            ->assertSee('data-surat-number-input', false)
            ->assertSee('data-applicant-input', false)
            ->assertSee('data-surat-number-feedback', false)
            ->assertSee('data-applicant-feedback', false);

        $javascript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("input.addEventListener('beforeinput'", $javascript);
        $this->assertStringContainsString("input.addEventListener('paste'", $javascript);
        $this->assertStringContainsString('event.isComposing', $javascript);
        $this->assertStringContainsString('input.setCustomValidity(message)', $javascript);
        $this->assertStringContainsString("const applicantAllowedPattern = /^[\\p{L}0-9 .,'’&()\\/\\-]+$/u;", $javascript);
        $this->assertStringContainsString('const unicodeLetterPattern = /\\p{L}/u;', $javascript);
        $this->assertStringContainsString('validateApplicant(input.value)', $javascript);
        $this->assertStringContainsString('Pemohon/Pengirim mengandung karakter yang tidak diperbolehkan.', $javascript);
        $this->assertStringContainsString('Nomor surat hanya boleh berisi angka dan tanda titik (.).', $javascript);
        $this->assertStringNotContainsString("input.value = input.value.replace(/[^0-9.]/g, '')", $javascript);
    }

    public function test_nomor_surat_rejects_letters_spaces_slashes_dashes_and_other_symbols(): void
    {
        $user = User::factory()->create();
        $invalidNumbers = [
            '2026.ABC.075',
            '2026-0061-075',
            '2026/0061/075',
            '2026 0061 075',
            'SURAT2026',
            '2026@0061',
        ];

        foreach ($invalidNumbers as $invalidNumber) {
            $this->actingAs($user)
                ->post(route('surat.store'), $this->validPayload(['nomor_surat' => $invalidNumber]))
                ->assertSessionHasErrors([
                    'nomor_surat' => 'Nomor surat hanya boleh berisi angka dan tanda titik (.).',
                ])
                ->assertSessionMissing('success');
        }

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_nomor_surat_must_contain_at_least_one_digit(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('surat.store'), $this->validPayload(['nomor_surat' => '...']))
            ->assertSessionHasErrors([
                'nomor_surat' => 'Nomor surat wajib mengandung setidaknya satu angka.',
            ]);

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_person_and_organization_names_with_unicode_numbers_and_reasonable_punctuation_are_valid(): void
    {
        $user = User::factory()->create();
        $validApplicants = [
            'Akhmad Kafi Rizal',
            'Siti Nur Aisyah',
            'PT 3 Serangkai',
            'CV Maju Jaya 2026',
            'PT. Sumber Rezeki',
            'Nurul-Huda',
            'PT Maju & Sejahtera',
            'PT Maju, Tbk.',
            "O'Connor",
            'D’Angelo/Jember',
            'Koperasi Unit Desa 5',
            'Société Élan (Jember)',
        ];

        foreach ($validApplicants as $index => $validApplicant) {
            $this->actingAs($user)
                ->post(route('surat.store'), $this->validPayload([
                    'nomor_surat' => '2026.1000.'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'pemohon_pengirim' => $validApplicant,
                ]))
                ->assertSessionDoesntHaveErrors();

            $this->assertDatabaseHas('surats', ['pemohon_pengirim' => $validApplicant]);
        }

        $this->assertDatabaseCount('surats', count($validApplicants));
    }

    public function test_applicant_rejects_values_without_any_unicode_letter(): void
    {
        $user = User::factory()->create();

        foreach (['123456', '2026.001', '---'] as $invalidApplicant) {
            $this->actingAs($user)
                ->post(route('surat.store'), $this->validPayload([
                    'pemohon_pengirim' => $invalidApplicant,
                ]))
                ->assertSessionHasErrors([
                    'pemohon_pengirim' => 'Pemohon/Pengirim harus berisi nama orang atau badan usaha yang valid, bukan hanya angka atau simbol.',
                ]);
        }

        $this->actingAs($user)
            ->post(route('surat.store'), $this->validPayload(['pemohon_pengirim' => '@@@@']))
            ->assertSessionHasErrors([
                'pemohon_pengirim' => 'Pemohon/Pengirim mengandung karakter yang tidak diperbolehkan. Gunakan huruf, angka, spasi, dan tanda baca nama yang wajar.',
            ]);

        $this->actingAs($user)
            ->post(route('surat.store'), $this->validPayload(['pemohon_pengirim' => '   ']))
            ->assertSessionHasErrors('pemohon_pengirim');

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_applicant_rejects_disallowed_characters_even_when_it_contains_letters(): void
    {
        $user = User::factory()->create();
        $invalidApplicants = [
            'Ahmad@@@@',
            'Ahmad#123',
            'Kafi_123',
            'Ahmad<script>',
        ];

        foreach ($invalidApplicants as $index => $invalidApplicant) {
            $this->actingAs($user)
                ->post(route('surat.store'), $this->validPayload([
                    'nomor_surat' => '2026.1100.'.str_pad((string) $index, 3, '0', STR_PAD_LEFT),
                    'pemohon_pengirim' => $invalidApplicant,
                ]))
                ->assertSessionHasErrors([
                    'pemohon_pengirim' => 'Pemohon/Pengirim mengandung karakter yang tidak diperbolehkan. Gunakan huruf, angka, spasi, dan tanda baca nama yang wajar.',
                ])
                ->assertSessionMissing('success');
        }

        $this->assertDatabaseCount('surats', 0);
    }

    public function test_create_and_edit_apply_the_same_number_and_applicant_rules(): void
    {
        $user = User::factory()->create();
        $surat = Surat::factory()->create([
            'nomor_surat' => '2026.2000.001',
            'pemohon_pengirim' => 'Pemohon Awal',
            'kategori_surat_id' => $this->officialCategoryId(),
        ]);

        $this->actingAs($user)
            ->put(route('surat.update', $surat), $this->validPayload([
                'nomor_surat' => '2026/2000/002',
                'pemohon_pengirim' => 'Pemohon Baru',
            ]))
            ->assertSessionHasErrors('nomor_surat');

        $this->actingAs($user)
            ->put(route('surat.update', $surat), $this->validPayload([
                'nomor_surat' => '2026.2000.002',
                'pemohon_pengirim' => 'Ahmad@@@@',
            ]))
            ->assertSessionHasErrors([
                'pemohon_pengirim' => 'Pemohon/Pengirim mengandung karakter yang tidak diperbolehkan. Gunakan huruf, angka, spasi, dan tanda baca nama yang wajar.',
            ]);

        $this->actingAs($user)
            ->put(route('surat.update', $surat), $this->validPayload([
                'nomor_surat' => '2026.2000.002',
                'pemohon_pengirim' => 'PT 3 Serangkai',
            ]))
            ->assertRedirect(route('surat.show', $surat));

        $this->assertDatabaseHas('surats', [
            'id' => $surat->id,
            'nomor_surat' => '2026.2000.002',
            'pemohon_pengirim' => 'PT 3 Serangkai',
        ]);
    }

    public function test_legacy_number_with_disallowed_characters_must_be_corrected_before_editing(): void
    {
        $surat = Surat::factory()->create([
            'nomor_surat' => '003/BPK/IX/2026',
            'kategori_surat_id' => $this->officialCategoryId(),
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('surat.update', $surat), $this->validPayload([
                'nomor_surat' => $surat->nomor_surat,
                'pemohon_pengirim' => 'Pemohon Tetap Valid',
            ]))
            ->assertSessionHasErrors('nomor_surat');

        $this->assertSame('003/BPK/IX/2026', $surat->fresh()->nomor_surat);
    }

    public function test_keterangan_remains_free_text_multiline_length_limited_and_escaped_on_output(): void
    {
        $user = User::factory()->create();
        $keterangan = "Berkas nomor 2026/075 diterima pada 22-11-2026.\nPemohon membawa 3 dokumen (KTP, KK & SPPT)! <script>alert('xss')</script>";

        $response = $this->actingAs($user)
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '2026.3000.001',
                'keterangan' => $keterangan,
            ]));

        $surat = Surat::query()->firstOrFail();
        $response->assertRedirect(route('surat.index'));
        $this->assertSame($keterangan, $surat->keterangan);

        $this->actingAs($user)
            ->get(route('surat.show', $surat))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;', false)
            ->assertDontSee("<script>alert('xss')</script>", false)
            ->assertSee('preserve-lines', false);

        $this->actingAs($user)
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '2026.3000.002',
                'keterangan' => str_repeat('a', 10001),
            ]))
            ->assertSessionHasErrors('keterangan');
    }

    public function test_every_official_category_context_is_selected_and_can_use_an_id_or_slug(): void
    {
        $user = User::factory()->create();
        $categories = KategoriSurat::query()->resmi()->orderBy('urutan')->get();

        $this->assertCount(6, $categories);

        foreach ($categories as $category) {
            $selectedOption = '<option value="'.$category->id.'" selected>'.$category->nama.'</option>';

            $this->actingAs($user)
                ->get(route('surat.create', ['kategori' => $category->id]))
                ->assertOk()
                ->assertSee($selectedOption, false);

            $this->actingAs($user)
                ->get(route('surat.create', ['kategori' => $category->slug]))
                ->assertOk()
                ->assertSee($selectedOption, false);
        }
    }

    public function test_create_without_valid_category_context_keeps_the_placeholder_selected(): void
    {
        $user = User::factory()->create();
        $placeholder = '<option value="" disabled selected>— Pilih Kategori Surat —</option>';

        $this->actingAs($user)
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee($placeholder, false);

        $this->actingAs($user)
            ->get(route('surat.create', ['kategori' => 'kategori-tidak-valid']))
            ->assertOk()
            ->assertSee($placeholder, false)
            ->assertDontSee('Kategori surat: Surat Lain-lain');
    }

    public function test_old_category_input_takes_priority_over_navigation_context(): void
    {
        $navigationCategory = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)->firstOrFail();
        $changedCategory = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PBJT)->firstOrFail();

        $this->actingAs(User::factory()->create())
            ->withSession(['_old_input' => ['kategori_surat_id' => (string) $changedCategory->id]])
            ->get(route('surat.create', ['kategori' => $navigationCategory->id]))
            ->assertOk()
            ->assertSee('<option value="'.$changedCategory->id.'" selected>'.$changedCategory->nama.'</option>', false)
            ->assertSee('Kategori surat: '.$changedCategory->nama);
    }

    public function test_save_and_add_again_stores_once_logs_activity_and_returns_to_a_clean_form(): void
    {
        $user = User::factory()->create(['name' => 'Pegawai Input']);
        $category = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_KEBERATAN_PBB)->firstOrFail();

        $response = $this->actingAs($user)
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '2026.0061.075',
                'kategori_surat_id' => $category->id,
                'submit_action' => 'save_and_add',
            ]));

        $response
            ->assertRedirect(route('surat.create', ['kategori' => $category->id]))
            ->assertSessionHas('success', 'Surat berhasil disimpan. Silakan tambahkan surat berikutnya.');

        $this->assertDatabaseCount('surats', 1);
        $surat = Surat::query()->firstOrFail();
        $this->assertSame(Surat::STATUS_BELUM_DITANGANI, $surat->status);
        $this->assertSame($category->id, $surat->kategori_surat_id);
        $this->assertDatabaseHas('aktivitas_surats', [
            'surat_id' => $surat->id,
            'user_id' => $user->id,
            'tipe' => AktivitasSurat::TIPE_SURAT_DICATAT,
        ]);

        $this->get(route('surat.create', ['kategori' => $category->id]))
            ->assertOk()
            ->assertSee('value="" maxlength="255" required autofocus autocomplete="on"', false)
            ->assertSee('<textarea id="keterangan"', false)
            ->assertSee('<option value="'.$category->id.'" selected>'.$category->nama.'</option>', false)
            ->assertSee('name="submit_action" value="save"', false);
    }

    public function test_save_and_add_button_and_existing_save_button_use_the_same_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('surat.create'))
            ->assertOk()
            ->assertSee('action="'.route('surat.store').'"', false)
            ->assertSee('data-submit-action-field', false)
            ->assertSee('data-submit-action-value="save"', false)
            ->assertSee('data-submit-action-value="save_and_add"', false)
            ->assertSee('Simpan Surat')
            ->assertSee('Simpan & Tambah Lagi', false)
            ->assertSee('Batal / Kembali');
    }

    public function test_failed_save_and_add_preserves_inputs_and_the_selected_action(): void
    {
        $category = KategoriSurat::query()->where('slug', KategoriSurat::SLUG_PBJT)->firstOrFail();
        Surat::factory()->create(['nomor_surat' => '2026.0059.029']);

        $response = $this->actingAs(User::factory()->create())
            ->from(route('surat.create', ['kategori' => $category->id]))
            ->post(route('surat.store'), $this->validPayload([
                'nomor_surat' => '2026.0059.029',
                'pemohon_pengirim' => 'Pemohon tetap tersimpan',
                'kategori_surat_id' => $category->id,
                'keterangan' => 'Keterangan tetap tersimpan',
                'submit_action' => 'save_and_add',
            ]));

        $response
            ->assertRedirect(route('surat.create', ['kategori' => $category->id]))
            ->assertSessionHasErrors('nomor_surat')
            ->assertSessionMissing('success')
            ->assertSessionHasInput('nomor_surat', '2026.0059.029')
            ->assertSessionHasInput('pemohon_pengirim', 'Pemohon tetap tersimpan')
            ->assertSessionHasInput('kategori_surat_id', $category->id)
            ->assertSessionHasInput('keterangan', 'Keterangan tetap tersimpan')
            ->assertSessionHasInput('submit_action', 'save_and_add');

        $this->assertDatabaseCount('surats', 1);

        $this->get(route('surat.create', ['kategori' => $category->id]))
            ->assertOk()
            ->assertSee('value="2026.0059.029"', false)
            ->assertSee('value="Pemohon tetap tersimpan"', false)
            ->assertSee('Keterangan tetap tersimpan')
            ->assertSee('name="submit_action" value="save_and_add"', false);
    }

    public function test_unknown_submit_action_is_rejected_without_creating_a_letter(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('surat.store'), $this->validPayload([
                'submit_action' => 'unknown_action',
            ]))
            ->assertSessionHasErrors('submit_action')
            ->assertSessionMissing('success');

        $this->assertDatabaseCount('surats', 0);
    }

    /** @param array<string, mixed> $overrides */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nomor_surat' => '2026.0061.075',
            'tanggal_masuk' => '2026-11-22',
            'pemohon_pengirim' => 'Wajib Pajak Contoh',
            'kategori_surat_id' => KategoriSurat::query()
                ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
                ->value('id'),
            'keterangan' => null,
        ], $overrides);
    }

    private function officialCategoryId(): int
    {
        return (int) KategoriSurat::query()
            ->where('slug', KategoriSurat::SLUG_PENGURANGAN_PBB)
            ->value('id');
    }
}
